<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\ReservationQrToken;
use App\Models\Table;
use App\Services\OutletAccess;
use App\Services\ReservationAvailability;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancePaymentController extends Controller
{
    public function __construct(
        private readonly ReservationAvailability $availability,
        private readonly OutletAccess $outletAccess,
    )
    {
    }

    public function dashboard(Request $request): View
    {
        $outletId = $this->resolveOutletId($request);
        $search = trim((string) $request->query('q', ''));

        $baseOutlet = fn (): Builder => $this->scopePaymentOutlets(Payment::query(), $request, $outletId);

        $today = now('Asia/Jakarta')->toDateString();
        $metrics = [
            'pending' => $baseOutlet()->where('payment_status', 'pending_verification')->count(),
            'verified_today' => $baseOutlet()
                ->where('payment_status', 'paid')
                ->whereDate('verified_at', $today)
                ->whereHas('reservation', fn (Builder $r) => $r->where('reservation_status', '<>', 'cancelled'))
                ->count(),
            'rejected' => $baseOutlet()->where('payment_status', 'rejected')->count(),
            'paid_today_total' => (float) $baseOutlet()
                ->where('payment_status', 'paid')
                ->whereDate('verified_at', $today)
                ->whereHas('reservation', fn (Builder $r) => $r->where('reservation_status', '<>', 'cancelled'))
                ->sum('amount'),
        ];

        $queueQuery = $baseOutlet()
            ->with(['reservation.outlet', 'reservation.tables.area'])
            ->where('payment_status', 'pending_verification')
            ->when($search !== '', function (Builder $q) use ($search): void {
                $q->whereHas('reservation', function (Builder $r) use ($search): void {
                    $r->where('reservation_code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->latest('proof_uploaded_at')
            ->latest('payments_id');

        $payments = $queueQuery->paginate(8)->withQueryString();

        $selectedId = (int) $request->query('selected', 0);
        $selectedPayment = $selectedId > 0
            ? ($payments->getCollection()->firstWhere('payments_id', $selectedId)
                ?? $baseOutlet()
                    ->where('payment_status', 'pending_verification')
                    ->with(['reservation.outlet', 'reservation.tables.area', 'verifiedBy'])
                    ->whereKey($selectedId)
                    ->first()
                ?? $payments->getCollection()->first())
            : $payments->getCollection()->first();

        $outletQuery = Outlet::ordered();
        $this->outletAccess->scope($outletQuery, $request->user());
        $outlets = $outletQuery->get(['outlets_id', 'name']);

        return view('finance.dashboard', [
            'metrics' => $metrics,
            'payments' => $payments,
            'selectedPayment' => $selectedPayment,
            'selectedProofMeta' => $this->proofMeta($selectedPayment),
            'outlets' => $outlets,
            'search' => $search,
            'outletId' => $outletId,
        ]);
    }

    public function index(Request $request): View
    {
        $outletId = $this->resolveOutletId($request);

        $queue = $this->scopePaymentOutlets(Payment::with(['reservation.outlet', 'reservation.tables.area', 'verifiedBy']), $request, $outletId)
            ->where('payment_status', 'pending_verification')
            ->latest('proof_uploaded_at')
            ->latest('payments_id')
            ->limit(25)
            ->get();

        $payment = $queue->first();

        return view('finance.payments.show', [
            'payment' => $payment,
            'queue' => $queue,
            'proofMeta' => $this->proofMeta($payment),
        ]);
    }

    public function show(Request $request, Payment $payment): View
    {
        $outletId = $this->resolveOutletId($request);
        $this->outletAccess->assertCanAccess($request->user(), $payment->reservation()->value('outlets_id'));
        $payment->load(['reservation.outlet', 'reservation.tables.area', 'verifiedBy']);

        $queue = $this->scopePaymentOutlets(Payment::with(['reservation.outlet']), $request, $outletId)
            ->where('payment_status', 'pending_verification')
            ->latest('proof_uploaded_at')
            ->latest('payments_id')
            ->limit(25)
            ->get();

        return view('finance.payments.show', [
            'payment' => $payment,
            'queue' => $queue,
            'proofMeta' => $this->proofMeta($payment),
        ]);
    }

    public function history(Request $request): View
    {
        $outletId = $this->resolveOutletId($request);
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $from = (string) $request->query('from', '');
        $to = (string) $request->query('to', '');

        $baseOutlet = fn (): Builder => $this->scopePaymentOutlets(Payment::query(), $request, $outletId);

        $today = now('Asia/Jakarta')->toDateString();
        $metrics = [
            'paid_today' => $baseOutlet()->where('payment_status', 'paid')->whereDate('verified_at', $today)->count(),
            'paid_total' => $baseOutlet()->where('payment_status', 'paid')->count(),
            'rejected' => $baseOutlet()->where('payment_status', 'rejected')->count(),
        ];

        $payments = $baseOutlet()
            ->with(['reservation.outlet', 'verifiedBy'])
            ->whereIn('payment_status', ['paid', 'rejected'])
            ->when(in_array($status, ['paid', 'rejected'], true), fn (Builder $q) => $q->where('payment_status', $status))
            ->when($from !== '', fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to !== '', fn (Builder $q) => $q->whereDate('created_at', '<=', $to))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $q->whereHas('reservation', function (Builder $r) use ($search): void {
                    $r->where('reservation_code', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('verified_at')
            ->orderByDesc('proof_uploaded_at')
            ->orderByDesc('payments_id')
            ->paginate(10)
            ->withQueryString();

        return view('finance.history', [
            'metrics' => $metrics,
            'payments' => $payments,
            'outlets' => $this->outletsFor($request, ['outlets_id', 'name']),
            'search' => $search,
            'status' => $status,
            'from' => $from,
            'to' => $to,
            'outletId' => $outletId,
        ]);
    }

    public function reports(Request $request): View
    {
        $data = $this->buildReportData($request);

        return view('finance.reports', $data);
    }

    public function exportReports(Request $request): StreamedResponse
    {
        $data = $this->buildReportData($request);
        $rows = $this->scopePaymentOutlets(Payment::with(['reservation.outlet', 'verifiedBy']), $request, $data['outletId'])
            ->where('created_at', '>=', $data['fromDate'])
            ->orderByDesc('created_at')
            ->get();

        $filename = 'laporan-keuangan-rrms-' . now('Asia/Jakarta')->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Kode Reservasi', 'Tamu', 'Outlet', 'Metode', 'Nominal', 'Status', 'Diverifikasi Oleh', 'Waktu Verifikasi']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->reservation?->reservation_code ?? '-',
                    $row->reservation?->customer_name ?? '-',
                    $row->reservation?->outlet?->name ?? '-',
                    $row->payment_method,
                    (string) $row->amount,
                    $row->payment_status,
                    $row->verifiedBy?->name ?? '-',
                    $row->verified_at?->format('Y-m-d H:i:s') ?? '-',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function proof(Request $request, Payment $payment): StreamedResponse
    {
        $this->outletAccess->assertCanAccess($request->user(), $payment->reservation()->value('outlets_id'));
        abort_unless($payment->proof_file_path && Storage::disk('local')->exists($payment->proof_file_path), 404);

        return Storage::disk('local')->response($payment->proof_file_path);
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        $note = trim((string) $request->input('verification_note', ''));

        DB::transaction(function () use ($request, $payment, $note): void {
            $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->payment_status !== 'pending_verification') {
                throw ValidationException::withMessages(['payment' => 'Pembayaran ini sudah diproses sebelumnya.']);
            }
            $reservation = $locked->reservation()->lockForUpdate()->firstOrFail();
            $this->outletAccess->assertCanAccess($request->user(), (int) $reservation->outlets_id);
            if ($reservation->reservation_status === 'cancelled') {
                throw ValidationException::withMessages(['payment' => 'Reservasi ini sudah dibatalkan. Silakan tolak bukti pembayaran agar dipindahkan ke riwayat.']);
            }
            if ($reservation->reservation_status !== 'pending_payment') {
                throw ValidationException::withMessages(['payment' => 'Reservasi ini tidak lagi menunggu pembayaran.']);
            }
            if ($reservation->cancellationRequests()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['payment' => 'Pembatalan sedang menunggu keputusan Manager.']);
            }
            $table = $reservation->tables()->first();
            if ($table) {
                $lockedTable = Table::whereKey($table->tables_id)->lockForUpdate()->with('area')->first();
                if (! $lockedTable || ! $this->availability->availableTables(
                    $reservation->outlets_id,
                    $lockedTable->areas_id,
                    $reservation->reservation_date->toDateString(),
                    $reservation->start_time,
                    $reservation->end_time,
                    (int) $reservation->guest_count,
                    (int) $reservation->reservations_id,
                )->contains('tables_id', (int) $lockedTable->tables_id)) {
                    throw ValidationException::withMessages(['payment' => 'Meja ini sudah tidak tersedia pada jadwal tersebut. Hubungi pelanggan sebelum menyetujui pembayaran.']);
                }
            }

            $locked->update([
                'payment_status' => 'paid',
                'users_id_verified_by' => $request->user()->getAuthIdentifier(),
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);
            $reservation->update(['reservation_status' => 'confirmed']);

            $qrToken = $reservation->qrToken()->first();
            if ($qrToken) {
                $qrToken->update(['is_active' => true]);
            } else {
                ReservationQrToken::create([
                    'reservations_id' => $reservation->reservations_id,
                    'token' => Str::random(64),
                    'valid_from' => Carbon::parse($reservation->reservation_date->toDateString() . ' ' . $reservation->start_time, config('reservation.timezone')),
                    'valid_until' => Carbon::parse($reservation->reservation_date->toDateString() . ' ' . $reservation->end_time, config('reservation.timezone')),
                    'is_active' => true,
                ]);
            }

            AuditLog::create([
                'users_id' => $request->user()?->users_id,
                'action' => 'payment_verified',
                'entity_type' => 'payment',
                'entity_id' => $locked->payments_id,
                'old_values' => [
                    'payment_status' => 'pending_verification',
                    'reservation_status' => 'pending_payment',
                ],
                'new_values' => array_filter([
                    'reservation_code' => $reservation->reservation_code,
                    'payment_status' => 'paid',
                    'reservation_status' => 'confirmed',
                    'verification_note' => $note !== '' ? $note : null,
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return $this->redirectAfterVerification(
            $request,
            'Pembayaran diterima (Paid), reservasi dikonfirmasi, dan dipindahkan ke Riwayat pembayaran.',
        );
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:1000']]);
        DB::transaction(function () use ($request, $payment, $data): void {
            $locked = Payment::whereKey($payment->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->payment_status !== 'pending_verification') {
                throw ValidationException::withMessages(['payment' => 'Pembayaran ini sudah diproses sebelumnya.']);
            }
            $reservation = $locked->reservation()->lockForUpdate()->firstOrFail();
            $this->outletAccess->assertCanAccess($request->user(), (int) $reservation->outlets_id);
            $locked->update([
                'payment_status' => 'rejected',
                'users_id_verified_by' => $request->user()->getAuthIdentifier(),
                'verified_at' => now(),
                'rejection_reason' => $data['rejection_reason'],
            ]);

            AuditLog::create([
                'users_id' => $request->user()?->users_id,
                'action' => 'payment_rejected',
                'entity_type' => 'payment',
                'entity_id' => $locked->payments_id,
                'old_values' => ['payment_status' => 'pending_verification'],
                'new_values' => [
                    'reservation_code' => $locked->reservation?->reservation_code,
                    'payment_status' => 'rejected',
                    'rejection_reason' => $data['rejection_reason'],
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        });

        return $this->redirectAfterVerification(
            $request,
            'Bukti ditolak dan dipindahkan ke Riwayat pembayaran. Pelanggan dapat mengunggah ulang bukti pembayaran.',
        );
    }

    private function redirectAfterVerification(Request $request, string $message): RedirectResponse
    {
        $referer = (string) $request->headers->get('referer', '');
        $refererPath = (string) parse_url($referer, PHP_URL_PATH);
        parse_str((string) parse_url($referer, PHP_URL_QUERY), $refererQuery);

        $outlet = $request->query('outlet') ?? $request->input('outlet') ?? ($refererQuery['outlet'] ?? null);
        $q = $request->query('q') ?? ($refererQuery['q'] ?? null);

        $fromDashboard = $refererPath === '/finance'
            || str_starts_with($refererPath, '/finance/ringkasan');

        if ($fromDashboard) {
            return redirect()->route('finance.dashboard', array_filter([
                'outlet' => $outlet,
                'q' => $q,
            ], fn ($v) => $v !== null && $v !== ''))->with('success', $message);
        }

        return redirect()->route('finance.payments.index', array_filter([
            'outlet' => $outlet,
        ], fn ($v) => $v !== null && $v !== ''))->with('success', $message);
    }

    private function outletsFor(Request $request, array $columns)
    {
        $query = Outlet::ordered();
        $this->outletAccess->scope($query, $request->user());

        return $query->get($columns);
    }

    private function scopePaymentOutlets(Builder $query, Request $request, ?int $selectedOutletId): Builder
    {
        if ($this->outletAccess->isGlobal($request->user())) {
            return $selectedOutletId === null
                ? $query
                : $query->whereHas('reservation', fn (Builder $reservation) => $reservation->where('outlets_id', $selectedOutletId));
        }

        $outletIds = $selectedOutletId === null
            ? $this->outletAccess->outletIds($request->user())
            : [$selectedOutletId];

        if (! $outletIds) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('reservation', fn (Builder $reservation) => $reservation->whereIn('outlets_id', $outletIds));
    }

    private function resolveOutletId(Request $request): ?int
    {
        $outlet = $request->query('outlet');
        $user = $request->user();
        if ($this->outletAccess->isGlobal($user)) {
            return is_numeric($outlet) && (int) $outlet > 0 ? (int) $outlet : null;
        }
        $outletIds = $this->outletAccess->outletIds($user);
        if ($outlet === null || $outlet === '' || $outlet === 'all') {
            return null;
        }
        abort_unless(is_numeric($outlet) && in_array((int) $outlet, $outletIds, true), 404);

        return (int) $outlet;
    }

    private function proofMeta(?Payment $payment): array
    {
        if (! $payment || ! $payment->proof_file_path) {
            return ['exists' => false, 'filename' => 'Belum ada berkas bukti', 'size' => '—', 'ext' => '—'];
        }

        $exists = Storage::disk('local')->exists($payment->proof_file_path);
        $filename = basename($payment->proof_file_path);
        $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION) ?: 'FILE');
        $sizeLabel = '—';

        if ($exists) {
            $bytes = Storage::disk('local')->size($payment->proof_file_path);
            $sizeLabel = $bytes >= 1048576
                ? number_format($bytes / 1048576, 1, ',', '.') . ' MB'
                : max(1, (int) round($bytes / 1024)) . ' KB';
        }

        return [
            'exists' => $exists,
            'filename' => $filename,
            'size' => $sizeLabel,
            'ext' => $ext,
            'is_image' => in_array($ext, ['JPG', 'JPEG', 'PNG', 'WEBP', 'GIF'], true),
        ];
    }

    private function buildReportData(Request $request): array
    {
        $outletId = $this->resolveOutletId($request);
        $period = (string) $request->query('period', '7');
        $days = match ($period) {
            'today' => 0,
            '30' => 29,
            default => 6,
        };
        $fromDate = now('Asia/Jakarta')->subDays($days)->startOfDay();

        $baseOutlet = fn (): Builder => $this->scopePaymentOutlets(Payment::query(), $request, $outletId);

        $paidInPeriod = $baseOutlet()
            ->where('payment_status', 'paid')
            ->whereRaw('COALESCE(verified_at, created_at) >= ?', [$fromDate])
            ->whereHas('reservation', fn (Builder $r) => $r->where('reservation_status', '<>', 'cancelled'));

        $verifiedRevenue = (float) (clone $paidInPeriod)->sum('amount');
        $paidCount = (clone $paidInPeriod)->count();
        $pendingCount = $baseOutlet()->where('payment_status', 'pending_verification')->count();
        $rejectedCount = $baseOutlet()->where('payment_status', 'rejected')->count();

        // 7-day bar chart
        $chartFrom = now('Asia/Jakarta')->subDays(6)->startOfDay();
        $perDay = $baseOutlet()
            ->where('payment_status', 'paid')
            ->whereRaw('COALESCE(verified_at, created_at) >= ?', [$chartFrom])
            ->whereHas('reservation', fn (Builder $r) => $r->where('reservation_status', '<>', 'cancelled'))
            ->selectRaw('DATE(COALESCE(verified_at, created_at)) as day, SUM(amount) as total')
            ->groupByRaw('DATE(COALESCE(verified_at, created_at))')
            ->pluck('total', 'day');

        $bars = [];
        $maxBar = 1.0;
        for ($i = 6; $i >= 0; $i--) {
            $date = now('Asia/Jakarta')->subDays($i);
            $val = (float) ($perDay[$date->toDateString()] ?? 0);
            if ($val > $maxBar) {
                $maxBar = $val;
            }
            $bars[] = [
                'day' => $date->copy()->locale('id')->translatedFormat('D'),
                'value' => $val,
                'short' => $this->rupiahShort($val, false),
                'is_today' => $i === 0,
            ];
        }
        foreach ($bars as &$bar) {
            $bar['height_pct'] = $bar['value'] > 0 ? max(14, (int) round(($bar['value'] / $maxBar) * 100)) : 8;
        }
        unset($bar);

        $outlets = $this->outletsFor($request, ['outlets_id', 'name', 'code']);
        $outletSummary = $outlets
            ->when($outletId !== null, fn ($c) => $c->where('outlets_id', $outletId))
            ->map(function (Outlet $outlet) use ($fromDate): array {
                $q = Payment::query()
                    ->where('payment_status', 'paid')
                    ->whereRaw('COALESCE(verified_at, created_at) >= ?', [$fromDate])
                    ->whereHas('reservation', fn (Builder $r) => $r->where('outlets_id', $outlet->outlets_id)->where('reservation_status', '<>', 'cancelled'));

                return [
                    'name' => $outlet->name,
                    'initial' => str_starts_with(strtolower($outlet->code ?? $outlet->name), 'i') ? 'i' : 'N',
                    'is_ibis' => str_starts_with(strtolower($outlet->code ?? $outlet->name), 'i'),
                    'count' => (clone $q)->count(),
                    'total' => (float) (clone $q)->sum('amount'),
                ];
            })
            ->values();

        $recentTransactions = $baseOutlet()
            ->with(['reservation.outlet'])
            ->latest('created_at')
            ->latest('payments_id')
            ->limit(8)
            ->get();

        return [
            'outletId' => $outletId,
            'period' => $period,
            'fromDate' => $fromDate,
            'outlets' => $outlets,
            'verifiedRevenue' => $verifiedRevenue,
            'paidCount' => $paidCount,
            'pendingCount' => $pendingCount,
            'rejectedCount' => $rejectedCount,
            'bars' => $bars,
            'outletSummary' => $outletSummary,
            'recentTransactions' => $recentTransactions,
        ];
    }

    private function rupiahShort(float $amount, bool $withPrefix = true): string
    {
        $prefix = $withPrefix ? 'Rp' : '';
        if ($amount >= 1_000_000_000) {
            return $prefix . number_format($amount / 1_000_000_000, 1, ',', '.') . ($withPrefix ? ' M' : '');
        }
        if ($amount >= 1_000_000) {
            return $prefix . number_format($amount / 1_000_000, 1, ',', '.') . ($withPrefix ? ' jt' : '');
        }
        if ($amount >= 1_000) {
            return $prefix . number_format($amount / 1_000, 0, ',', '.') . ($withPrefix ? ' rb' : '');
        }

        return $prefix . number_format($amount, 0, ',', '.');
    }
}
