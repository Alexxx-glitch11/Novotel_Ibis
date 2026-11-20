<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CheckIn;
use App\Models\Reservation;
use App\Models\ReservationQrToken;
use App\Services\OutletAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CheckInController extends Controller
{
    public function scan(Request $request, OutletAccess $outletAccess): RedirectResponse
    {
        $input = trim((string) $request->input('qr_token', ''));
        $request->merge(['qr_token' => $input]);
        $request->validate(['qr_token' => ['required', 'string', 'min:3', 'max:100']]);

        $reservation = null;
        $qr = null;
        $byToken = strlen($input) === 64;

        if ($byToken) {
            $qr = ReservationQrToken::with('reservation')
                ->where('token', $input)
                ->first();
            $reservation = $qr?->reservation;
        }

        if (! $reservation) {
            $reservation = Reservation::with('qrToken')
                ->whereRaw('LOWER(reservation_code) = ?', [mb_strtolower($input)])
                ->first();
            $qr = $reservation?->qrToken;
            $byToken = false;
        }

        if (! $reservation) {
            throw ValidationException::withMessages([
                'qr_token' => 'QR atau kode reservasi tidak ditemukan.',
            ]);
        }

        $outletAccess->assertCanAccess($request->user(), (int) $reservation->outlets_id);

        if (($reason = $this->checkInIneligibleReason($reservation, $qr, $byToken)) !== null) {
            return redirect()
                ->route('cashier.reservations.show', ['reservation' => $reservation->reservations_id])
                ->withErrors(['qr_token' => $reason]);
        }

        $request->session()->put('cashier_checkin_reservation_id', (int) $reservation->reservations_id);

        return redirect()
            ->route('cashier.reservations.show', ['reservation' => $reservation->reservations_id])
            ->with('checkin_scan_success', 'Reservasi terverifikasi. Periksa detail dan catat jumlah tamu yang hadir.');
    }

    public function record(Request $request, Reservation $reservation, OutletAccess $outletAccess): RedirectResponse
    {
        $data = $request->validate([
            'guest_count_arrived' => ['required', 'integer', 'min:1', 'max:50'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($request, $reservation, $outletAccess, $data): void {
            $locked = Reservation::whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();
            $outletAccess->assertCanAccess($request->user(), (int) $locked->outlets_id);
            $locked->load(['latestPayment', 'qrToken']);

            if (($reason = $this->checkInIneligibleReason($locked, $locked->qrToken, false)) !== null) {
                throw ValidationException::withMessages([
                    'guest_count_arrived' => $reason,
                ]);
            }

            $previous = (int) $locked->checkIns()->max('cumulative_guest_count');
            $cumulative = $previous + (int) $data['guest_count_arrived'];
            if ($cumulative > (int) $locked->guest_count) {
                throw ValidationException::withMessages([
                    'guest_count_arrived' => 'Jumlah kumulatif tamu hadir tidak boleh melebihi jumlah tamu reservasi.',
                ]);
            }

            $checkIn = CheckIn::create([
                'reservations_id' => $locked->reservations_id,
                'users_id_checked_in_by' => $request->user()->users_id,
                'guest_count_arrived' => (int) $data['guest_count_arrived'],
                'cumulative_guest_count' => $cumulative,
                'checked_in_at' => now(config('reservation.timezone')),
                'notes' => $data['notes'] ?? null,
            ]);
            $newStatus = $cumulative === (int) $locked->guest_count ? 'checked_in' : 'partially_arrived';
            $locked->update(['reservation_status' => $newStatus]);

            AuditLog::create([
                'users_id' => $request->user()->users_id,
                'action' => 'reservation_checkin_recorded',
                'entity_type' => 'check_in',
                'entity_id' => $checkIn->check_ins_id,
                'old_values' => ['reservation_status' => $reservation->reservation_status, 'cumulative_guest_count' => $previous],
                'new_values' => ['reservation_code' => $locked->reservation_code, 'reservation_status' => $newStatus, 'cumulative_guest_count' => $cumulative],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        $request->session()->put('cashier_checkin_reservation_id', (int) $reservation->reservations_id);

        return $this->redirectAfterCheckIn($request, $reservation);
    }

    public function complete(Request $request, Reservation $reservation, OutletAccess $outletAccess): RedirectResponse
    {
        DB::transaction(function () use ($request, $reservation, $outletAccess): void {
            $locked = Reservation::whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();
            $outletAccess->assertCanAccess($request->user(), (int) $locked->outlets_id);

            if (! in_array($locked->reservation_status, ['checked_in', 'partially_arrived'], true)) {
                throw ValidationException::withMessages([
                    'reservation_status' => 'Hanya reservasi yang sudah atau sebagian hadir yang dapat ditandai selesai.',
                ]);
            }

            $oldStatus = $locked->reservation_status;
            $locked->update(['reservation_status' => 'completed']);
            $locked->qrToken()->update(['is_active' => false]);

            AuditLog::create([
                'users_id' => $request->user()->users_id,
                'action' => 'reservation_completed',
                'entity_type' => 'reservation',
                'entity_id' => $locked->reservations_id,
                'old_values' => ['reservation_status' => $oldStatus],
                'new_values' => [
                    'reservation_code' => $locked->reservation_code,
                    'reservation_status' => 'completed',
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return $this->redirectAfterCheckIn(
            $request,
            $reservation,
            'Kunjungan reservasi ' . $reservation->reservation_code . ' telah ditandai selesai.',
        );
    }

    public function markNoShow(Request $request, Reservation $reservation, OutletAccess $outletAccess): RedirectResponse
    {
        DB::transaction(function () use ($request, $reservation, $outletAccess): void {
            $locked = Reservation::whereKey($reservation->getKey())->lockForUpdate()->firstOrFail();
            $outletAccess->assertCanAccess($request->user(), (int) $locked->outlets_id);

            if ($locked->reservation_status !== 'confirmed') {
                throw ValidationException::withMessages([
                    'reservation_status' => 'Status No-show hanya berlaku untuk reservasi Terkonfirmasi yang belum check-in.',
                ]);
            }

            $locked->update(['reservation_status' => 'no_show']);
            $locked->qrToken()->update(['is_active' => false]);

            AuditLog::create([
                'users_id' => $request->user()->users_id,
                'action' => 'reservation_marked_no_show',
                'entity_type' => 'reservation',
                'entity_id' => $locked->reservations_id,
                'old_values' => ['reservation_status' => 'confirmed'],
                'new_values' => [
                    'reservation_code' => $locked->reservation_code,
                    'reservation_status' => 'no_show',
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return $this->redirectAfterCheckIn(
            $request,
            $reservation,
            'Reservasi ' . $reservation->reservation_code . ' ditandai tidak hadir (No-show).',
        );
    }

    private function redirectAfterCheckIn(
        Request $request,
        Reservation $reservation,
        string $message = 'Kedatangan tamu berhasil dicatat.',
    ): RedirectResponse {
        $refererPath = (string) parse_url((string) $request->headers->get('referer', ''), PHP_URL_PATH);
        parse_str((string) parse_url((string) $request->headers->get('referer', ''), PHP_URL_QUERY), $refererQuery);
        $outlet = $request->input('outlet') ?? ($refererQuery['outlet'] ?? null);

        if (preg_match('#^/cashier/reservasi/\d+#', $refererPath) === 1) {
            return redirect()
                ->route('cashier.reservations.show', array_filter([
                    'reservation' => $reservation->reservations_id,
                    'outlet' => $outlet,
                ], fn ($v) => $v !== null && $v !== ''))
                ->with('success', $message);
        }

        $routeName = match (true) {
            str_starts_with($refererPath, '/cashier/riwayat'),
            str_starts_with($refererPath, '/cashier/pindai-qr') => 'cashier.history.index',
            str_starts_with($refererPath, '/cashier/reservasi') => 'cashier.reservations.index',
            default => 'cashier.dashboard',
        };

        return redirect()
            ->route($routeName, array_filter([
                'reservation' => $reservation->reservations_id,
                'outlet' => $outlet,
                'date' => $refererQuery['date'] ?? null,
                'status' => $refererQuery['status'] ?? null,
                'area' => $refererQuery['area'] ?? null,
                'q' => $refererQuery['q'] ?? null,
            ], fn ($v) => $v !== null && $v !== ''))
            ->with('success', $message);
    }

    private function checkInIneligibleReason(Reservation $reservation, ?ReservationQrToken $qr, bool $requireActiveQr = true): ?string
    {
        if ($requireActiveQr && (! $qr || ! $qr->is_active)) {
            return 'QR reservasi tidak aktif.';
        }

        if ($reservation->reservation_status === 'checked_in') {
            return 'Seluruh rombongan pada reservasi ini sudah check-in.';
        }

        if ($reservation->reservation_status === 'cancelled') {
            return 'Reservasi ini sudah dibatalkan.';
        }

        if (! in_array($reservation->reservation_status, ['confirmed', 'partially_arrived'], true)) {
            return 'Reservasi belum dikonfirmasi atau belum siap check-in.';
        }

        if ($reservation->latestPayment()->value('payment_status') !== 'paid') {
            return 'Pembayaran belum dikonfirmasi Finance.';
        }

        $tz = config('reservation.timezone', 'Asia/Jakarta');
        $now = now($tz);
        $isTodayOrFuture = $reservation->reservation_date
            && $reservation->reservation_date->toDateString() >= $now->toDateString();
        $withinQrWindow = $qr && $qr->valid_from && $qr->valid_until
            && $now->greaterThanOrEqualTo($qr->valid_from)
            && $now->lessThanOrEqualTo($qr->valid_until);

        if (! $isTodayOrFuture && ! $withinQrWindow) {
            return 'Check-in hanya berlaku untuk reservasi yang masih aktif ('
                . ($reservation->reservation_date?->format('d M Y') ?? '-')
                . ').';
        }

        return null;
    }
}
