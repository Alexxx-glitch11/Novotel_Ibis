<?php

namespace App\Http\Controllers;

use App\Models\CancellationRequest;
use App\Models\Reservation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class GuestReservationController extends Controller
{
    // public: juga dipakai TableSelectionController agar pelanggan yang baru memesan langsung terverifikasi
    public const SESSION_KEY = 'guest_email';

    // Status yang tampil sebagai "reservasi aktif"
    private const ACTIVE = ['pending_payment', 'confirmed', 'partially_arrived', 'checked_in'];

    // Status yang boleh diajukan pembatalan
    private const CANCELLABLE = ['pending_payment', 'confirmed'];

    // Status yang tampil di riwayat
    private const HISTORY = ['completed', 'cancelled', 'expired', 'no_show'];

    // Pengajuan harus dikirim paling lambat sekian jam sebelum jadwal mulai.
    // 0 = selama jadwal belum mulai. PRD belum menetapkan kebijakan ini: ubah sesuai keputusan hotel.
    private const CANCEL_MIN_HOURS = 0;

    // Verifikasi email (OTP)
    private const OTP_TTL_MINUTES    = 10;
    private const OTP_MAX_ATTEMPTS   = 5;
    private const OTP_RESEND_SECONDS = 60;

    // ---------------------------------------------------------------- Reservasi Saya

    public function index()
    {
        $email = session(self::SESSION_KEY);

        if (! $email) {
            $otpEmail = session('guest_otp_email');

            return view('guest.reservations', [
                'verified' => false,
                'otpEmail' => $otpEmail ? $this->maskEmail($otpEmail) : null,
            ]);
        }

        $base = Reservation::query()
            ->where('customer_email', $email)
            ->with([
                'outlet',
                'tables.area',
                'cancellationRequests' => fn ($q) => $q->latest('cancellation_requests_id'),
            ]);

        $active = (clone $base)
            ->whereIn('reservation_status', self::ACTIVE)
            ->orderBy('reservation_date')->orderBy('start_time')
            ->get()
            ->each(fn (Reservation $r) => $this->decorate($r));

        $history = (clone $base)
            ->whereIn('reservation_status', self::HISTORY)
            ->orderByDesc('reservation_date')->orderByDesc('start_time')
            ->limit(50)
            ->get()
            ->each(fn (Reservation $r) => $this->decorate($r));

        return view('guest.reservations', [
            'verified'    => true,
            'maskedEmail' => $this->maskEmail($email),
            'active'      => $active,
            'history'     => $history,
        ]);
    }

    // Cara masuk alternatif: kode reservasi + email atau nomor telepon
    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code'    => ['required', 'string', 'max:32'],
            'contact' => ['required', 'string', 'max:100'],
        ]);

        $contact     = trim($data['contact']);
        $reservation = Reservation::where('reservation_code', strtoupper(trim($data['code'])))->first();

        $match = false;
        if ($reservation) {
            $match = str_contains($contact, '@')
                ? strcasecmp($reservation->customer_email, $contact) === 0
                : $this->digits($contact) !== '' && $this->digits($reservation->customer_phone) === $this->digits($contact);
        }

        // Pesan sama untuk semua kegagalan, supaya tidak bisa dipakai menebak data orang lain.
        if (! $match) {
            throw ValidationException::withMessages(['code' => __('history.lookup_failed')]);
        }

        // Sengaja tanpa session()->regenerate(): hold meja terikat ke ID session.
        $request->session()->put(self::SESSION_KEY, mb_strtolower($reservation->customer_email));

        return redirect()->route('guest.reservations');
    }

    public function forget(Request $request): RedirectResponse
    {
        $wasVerified = $request->session()->has(self::SESSION_KEY);

        $request->session()->forget([self::SESSION_KEY, 'guest_otp_email']);
        $request->session()->regenerateToken();

        $redirect = redirect()->route('guest.reservations');

        return $wasVerified ? $redirect->with('success', __('history.logged_out')) : $redirect;
    }

    // ---------------------------------------------------------------- Verifikasi email (OTP)

    public function sendOtp(Request $request): RedirectResponse
    {
        $data  = $request->validate(['email' => ['required', 'email', 'max:100']]);
        $email = mb_strtolower(trim($data['email']));

        // Email disimpan di sesi agar langkah berikutnya cukup meminta kode.
        $request->session()->put('guest_otp_email', $email);

        // Respons selalu sama, terdaftar atau tidak, supaya tidak membocorkan siapa yang punya reservasi.
        // Email hanya dikirim jika memang ada reservasi dengan email itu, dengan jeda 60 detik.
        if (
            Reservation::where('customer_email', $email)->exists()
            && Cache::add($this->otpCooldownKey($email), 1, self::OTP_RESEND_SECONDS)
        ) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            Cache::put($this->otpKey($email), [
                'hash'     => hash_hmac('sha256', $code, (string) config('app.key')),
                'attempts' => 0,
                'expires'  => now()->addMinutes(self::OTP_TTL_MINUTES)->timestamp,
            ], now()->addMinutes(self::OTP_TTL_MINUTES));

            try {
                Mail::raw(
                    __('history.otp_mail_body', ['code' => $code, 'minutes' => self::OTP_TTL_MINUTES]),
                    fn ($message) => $message->to($email)->subject(__('history.otp_mail_subject'))
                );
            } catch (\Throwable $e) {
                report($e);   // gagal kirim dicatat di log, tidak ditampilkan ke pengunjung
            }
        }

        return redirect()->route('guest.reservations')->with('success', __('history.otp_sent_notice'));
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $data  = $request->validate(['otp' => ['required', 'digits:6']]);
        $email = $request->session()->get('guest_otp_email');

        if (! $email) {
            return redirect()->route('guest.reservations');
        }

        $key   = $this->otpKey($email);
        $entry = Cache::get($key);
        $fail  = ValidationException::withMessages(['otp' => __('history.otp_invalid')]);

        if (! $entry || time() > $entry['expires'] || $entry['attempts'] >= self::OTP_MAX_ATTEMPTS) {
            Cache::forget($key);
            throw $fail;
        }

        if (! hash_equals($entry['hash'], hash_hmac('sha256', $data['otp'], (string) config('app.key')))) {
            $entry['attempts']++;
            Cache::put($key, $entry, now()->addMinutes(self::OTP_TTL_MINUTES));
            throw $fail;
        }

        Cache::forget($key);
        $request->session()->forget('guest_otp_email');
        $request->session()->put(self::SESSION_KEY, $email);

        return redirect()->route('guest.reservations');
    }

    // ---------------------------------------------------------------- Pembatalan

    public function cancelForm(string $code)
    {
        if (! session(self::SESSION_KEY)) {
            return redirect()->route('guest.reservations');
        }

        $reservation = $this->decorate($this->owned($code));

        if ($reservation->cancellationRequests->first()?->status === 'pending') {
            return redirect()->route('guest.cancel.status', $reservation->reservation_code);
        }

        if (! $reservation->can_cancel) {
            return redirect()->route('guest.reservations')->with('error', __('history.cannot_cancel'));
        }

        return view('guest.cancel-form', [
            'reservation' => $reservation,
            'reasons'     => $this->reasons(),
        ]);
    }

    public function cancelStore(Request $request, string $code): RedirectResponse
    {
        if (! session(self::SESSION_KEY)) {
            return redirect()->route('guest.reservations');
        }

        $reasons = $this->reasons();

        $data = $request->validate([
            'reason'  => ['required', Rule::in(array_keys($reasons))],
            'note'    => ['nullable', 'string', 'max:500'],
            'confirm' => ['accepted'],
        ], [
            'reason.required'  => __('history.v_reason'),
            'reason.in'        => __('history.v_reason'),
            'note.max'         => __('history.v_note'),
            'confirm.accepted' => __('history.v_confirm'),
        ]);

        $created = DB::transaction(function () use ($code, $data, $request) {
            // Kunci baris reservasi supaya klik ganda tidak membuat dua pengajuan.
            $reservation = Reservation::where('reservation_code', strtoupper($code))
                ->where('customer_email', session(self::SESSION_KEY))
                ->lockForUpdate()
                ->firstOrFail();

            $reservation->load('cancellationRequests');

            if (! $this->canCancel($reservation)) {
                return null;
            }

            // Selalu disimpan dalam bahasa Indonesia, apa pun bahasa pelanggan.
            $label = __('history.reason_' . $data['reason'], [], 'id');
            $note  = trim((string) ($data['note'] ?? ''));
            $text  = $note !== '' ? $label . ': ' . $note : $label;

            $cancellation = CancellationRequest::create([
                'reservations_id' => $reservation->reservations_id,
                'requested_by'    => 'customer',
                'reason'          => $text,
                'status'          => 'pending',
            ]);

            DB::table('audit_logs')->insert([
                'users_id'    => null,
                'action'      => 'cancellation_requested',
                'entity_type' => 'cancellation_request',
                'entity_id'   => $cancellation->cancellation_requests_id,
                'old_values'  => null,
                'new_values'  => json_encode([
                    'reservation_code' => $reservation->reservation_code,
                    'reason'           => $text,
                    'status'           => 'pending',
                ], JSON_UNESCAPED_UNICODE),
                'ip_address'  => $request->ip(),
                'user_agent'  => (string) $request->userAgent(),
                'created_at'  => now(),
            ]);

            return $cancellation;
        });

        if (! $created) {
            return redirect()->route('guest.reservations')->with('error', __('history.cannot_cancel'));
        }

        return redirect()
            ->route('guest.cancel.status', strtoupper($code))
            ->with('success', __('history.submitted'));
    }

    public function cancelStatus(string $code)
    {
        if (! session(self::SESSION_KEY)) {
            return redirect()->route('guest.reservations');
        }

        $reservation  = $this->decorate($this->owned($code));
        $cancellation = $reservation->cancellationRequests->first();

        abort_unless($cancellation, 404);

        return view('guest.cancel-status', [
            'reservation'  => $reservation,
            'cancellation' => $cancellation,
        ]);
    }

    // ---------------------------------------------------------------- Helper

    private function owned(string $code): Reservation
    {
        return Reservation::query()
            ->with([
                'outlet',
                'tables.area',
                'cancellationRequests' => fn ($q) => $q->latest('cancellation_requests_id'),
            ])
            ->where('reservation_code', strtoupper($code))
            ->where('customer_email', session(self::SESSION_KEY))
            ->firstOrFail();   // bukan milik sesi ini = 404
    }

    private function startsAt(Reservation $r): Carbon
    {
        return Carbon::parse($r->reservation_date->toDateString() . ' ' . $r->start_time);
    }

    private function canCancel(Reservation $r): bool
    {
        if (! in_array($r->reservation_status, self::CANCELLABLE, true)) {
            return false;
        }
        if ($r->cancellationRequests->contains('status', 'pending')) {
            return false;
        }

        return $this->startsAt($r)->subHours(self::CANCEL_MIN_HOURS)->isFuture();
    }

    // Menambahkan atribut tampilan (tidak disimpan ke database)
    private function decorate(Reservation $r): Reservation
    {
        $start = $this->startsAt($r)->locale(app()->getLocale());

        $codes = $r->tables->pluck('table_code')->implode(', ');
        $areas = $r->tables->map(fn ($t) => $t->area?->name)->filter()->unique()->implode(', ');

        $r->setAttribute('when_label', $start->translatedFormat('l, j M Y') . ' • ' . $start->format('H.i') . ' WIB');
        $r->setAttribute('when_short', $start->translatedFormat('j M Y') . ' • ' . $start->format('H.i'));
        $r->setAttribute('tables_label', $codes !== ''
            ? __('history.table_word') . ' ' . $codes . ($areas !== '' ? ' • ' . $areas : '')
            : '—');
        $r->setAttribute('total_label', (float) $r->grand_total > 0
            ? 'Rp' . number_format((float) $r->grand_total, 0, ',', '.')
            : '—');
        $r->setAttribute('can_cancel', $this->canCancel($r));

        return $r;
    }

    private function reasons(): array
    {
        $keys = ['schedule', 'unavailable', 'duplicate', 'wrong', 'other'];

        return collect($keys)->mapWithKeys(fn ($k) => [$k => __('history.reason_' . $k)])->all();
    }

    private function digits(?string $value): string
    {
        $d = preg_replace('/\D+/', '', (string) $value);

        return str_starts_with($d, '62') ? '0' . substr($d, 2) : $d;
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 1) . str_repeat('*', max(2, mb_strlen($name) - 1)) . '@' . $domain;
    }

    private function otpKey(string $email): string
    {
        return 'guest_otp:' . sha1($email);
    }

    private function otpCooldownKey(string $email): string
    {
        return 'guest_otp_cooldown:' . sha1($email);
    }
}