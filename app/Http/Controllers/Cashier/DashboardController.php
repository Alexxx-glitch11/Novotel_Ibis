<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
use App\Models\CancellationRequest;
use App\Models\CheckIn;
use App\Models\Outlet;
use App\Models\Reservation;
use App\Services\OutletAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(Request $request, OutletAccess $outletAccess): View
    {
        $outletId = $this->resolveOutletId($request, $outletAccess);
        $tz = config('reservation.timezone', 'Asia/Jakarta');
        $today = now($tz)->toDateString();

        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $areaId = (int) $request->query('area', 0);

        $todayBase = $this->scopedReservationQuery($request, $outletAccess, $outletId)
            ->whereDate('reservation_date', $today);
        $upcomingBase = $this->scopedReservationQuery($request, $outletAccess, $outletId)
            ->whereDate('reservation_date', '>=', $today);

        $metrics = [
            'today_total' => (clone $upcomingBase)->whereIn('reservation_status', ['confirmed', 'partially_arrived', 'checked_in', 'completed'])->count(),
            'upcoming_two_hours' => $this->upcomingTwoHourCount(clone $todayBase, $tz),
            'not_checked_in' => (clone $upcomingBase)->whereIn('reservation_status', ['confirmed', 'partially_arrived'])->count(),
            'checked_in' => (clone $upcomingBase)->whereIn('reservation_status', ['checked_in', 'completed'])->count(),
            'pending_cancellations' => $this->pendingCancellationCount($request, $outletAccess, $outletId),
        ];

        $arrivalsQuery = (clone $upcomingBase)
            ->with(['outlet', 'tables.area', 'latestPayment', 'checkIns.checkedInBy'])
            ->whereIn('reservation_status', ['confirmed', 'partially_arrived', 'checked_in', 'completed'])
            ->when(in_array($status, ['confirmed', 'partially_arrived', 'checked_in'], true), fn (Builder $q) => $q->where('reservation_status', $status))
            ->when($areaId > 0, fn (Builder $q) => $q->whereHas('tables', fn (Builder $t) => $t->where('areas_id', $areaId)))
            ->when($search !== '', fn (Builder $q) => $this->applySearch($q, $search))
            ->orderByDesc('created_at')
            ->orderByDesc('reservations_id');

        $upcomingReservations = $arrivalsQuery->limit(8)->get();

        $recentHistory = $this->applyHistoryFilters(
            $this->historyBaseQuery($request, $outletAccess, $outletId, false, ''),
            'all',
            '',
        )->limit(6)->get();
        $recentIds = $recentHistory->pluck('reservations_id')->all();
        $statusLogsByReservation = $recentIds
            ? AuditLog::with('user')
                ->where('entity_type', 'reservation')
                ->whereIn('entity_id', $recentIds)
                ->whereIn('action', ['reservation_completed', 'reservation_marked_no_show'])
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('entity_id')
            : collect();

        $selectedReservation = $this->resolveSelectedReservation($request, $outletAccess, $outletId, $upcomingReservations->first());
        $areas = $this->areasForScope($request, $outletAccess, $outletId);
        $activeOutlet = $this->activeOutletModel($request, $outletAccess, $outletId);

        return view('cashier.dashboard', compact(
            'metrics',
            'upcomingReservations',
            'recentHistory',
            'statusLogsByReservation',
            'selectedReservation',
            'areas',
            'search',
            'status',
            'areaId',
            'outletId',
            'activeOutlet',
        ));
    }

    public function reservations(Request $request, OutletAccess $outletAccess): View
    {
        $outletId = $this->resolveOutletId($request, $outletAccess);
        $tz = config('reservation.timezone', 'Asia/Jakarta');
        $today = now($tz)->toDateString();

        $date = trim((string) $request->query('date', ''));
        $hasExactDate = $date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
        if (! $hasExactDate) {
            $date = '';
        }
        $search = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', 'all');
        $areaId = (int) $request->query('area', 0);

        $dayBase = $this->scopedReservationQuery($request, $outletAccess, $outletId)
            ->when(
                $hasExactDate,
                fn (Builder $q) => $q->whereDate('reservation_date', $date),
                fn (Builder $q) => $q->whereDate('reservation_date', '>=', $today),
            );

        $todayForTwoHour = $this->scopedReservationQuery($request, $outletAccess, $outletId)
            ->whereDate('reservation_date', $hasExactDate ? $date : $today);

        $metrics = [
            'day_total' => (clone $dayBase)->whereIn('reservation_status', ['confirmed', 'partially_arrived', 'checked_in', 'completed'])->count(),
            'not_checked_in' => (clone $dayBase)->whereIn('reservation_status', ['confirmed', 'partially_arrived'])->count(),
            'checked_in' => (clone $dayBase)->whereIn('reservation_status', ['checked_in', 'completed'])->count(),
            'upcoming_two_hours' => (! $hasExactDate || $date === $today)
                ? $this->upcomingTwoHourCount(clone $todayForTwoHour, $tz)
                : (clone $dayBase)->whereIn('reservation_status', ['confirmed', 'partially_arrived'])->count(),
        ];

        $reservationsQuery = (clone $dayBase)
            ->with(['outlet', 'tables.area', 'latestPayment', 'checkIns.checkedInBy'])
            ->when($status === 'all', fn (Builder $q) => $q->where('reservation_status', '!=', 'draft'))
            ->when(in_array($status, ['confirmed', 'partially_arrived', 'checked_in', 'pending_payment', 'cancelled'], true), fn (Builder $q) => $q->where('reservation_status', $status))
            ->when($areaId > 0, fn (Builder $q) => $q->whereHas('tables', fn (Builder $t) => $t->where('areas_id', $areaId)))
            ->when($search !== '', fn (Builder $q) => $this->applySearch($q, $search))
            ->orderByDesc('created_at')
            ->orderByDesc('reservations_id');

        $reservations = $reservationsQuery->paginate(6)->withQueryString();
        $selectedReservation = $this->resolveSelectedReservation($request, $outletAccess, $outletId, $reservations->getCollection()->first());
        $areas = $this->areasForScope($request, $outletAccess, $outletId);
        $activeOutlet = $this->activeOutletModel($request, $outletAccess, $outletId);
        $dateLabel = $hasExactDate ? Carbon::parse($date)->locale(app()->getLocale())->translatedFormat('d M Y') : 'Jadwal Aktif';
        $isToday = ! $hasExactDate;

        return view('cashier.reservations', compact(
            'metrics',
            'reservations',
            'selectedReservation',
            'areas',
            'date',
            'dateLabel',
            'isToday',
            'search',
            'status',
            'areaId',
            'outletId',
            'activeOutlet',
        ));
    }

    public function showReservation(Request $request, Reservation $reservation, OutletAccess $outletAccess): View
    {
        $outletAccess->assertCanAccess($request->user(), (int) $reservation->outlets_id);

        $reservation->load([
            'outlet',
            'tables.area',
            'items',
            'eventContent',
            'latestPayment.verifiedBy',
            'qrToken',
            'checkIns.checkedInBy',
            'cancellationRequests.reviewedBy',
        ]);

        $outletId = $this->resolveOutletId($request, $outletAccess);
        $eventTotal = max(0.0, round((float) $reservation->subtotal - (float) $reservation->items->sum('line_total'), 2));

        return view('cashier.reservation-show', compact('reservation', 'eventTotal', 'outletId'));
    }

    public function qr(Request $request, OutletAccess $outletAccess): View
    {
        return $this->history($request, $outletAccess);
    }

    public function history(Request $request, OutletAccess $outletAccess): View
    {
        $outletId = $this->resolveOutletId($request, $outletAccess);
        $tz = config('reservation.timezone', 'Asia/Jakarta');
        $today = now($tz)->toDateString();

        [$date, $hasExactDate, $status, $search] = $this->historyFilters($request);

        $scopedReservations = fn (): Builder => $this->historyBaseQuery($request, $outletAccess, $outletId, $hasExactDate, $date);

        $checkinRelationBase = CheckIn::query()->whereHas('reservation', function (Builder $q) use ($request, $outletAccess, $outletId, $hasExactDate, $date): void {
            $outletAccess->scope($q, $request->user());
            if ($outletId !== null) {
                $q->where('outlets_id', $outletId);
            }
            if ($hasExactDate) {
                $q->whereDate('reservation_date', $date);
            }
        });

        $metrics = [
            'checked_in_total' => $scopedReservations()->whereIn('reservation_status', ['checked_in', 'completed', 'partially_arrived'])->count(),
            'completed_total' => $scopedReservations()->where('reservation_status', 'completed')->count(),
            'checkin_logs_total' => (clone $checkinRelationBase)->count(),
            'checkin_logs_today' => (clone $checkinRelationBase)->whereDate('checked_in_at', $today)->count(),
            'cancellations_total' => $scopedReservations()->where(function (Builder $q): void {
                $q->whereIn('reservation_status', ['cancelled', 'no_show'])
                    ->orWhereHas('cancellationRequests', fn (Builder $c) => $c->where('requested_by', 'cashier'));
            })->count(),
        ];

        $reservations = $this->applyHistoryFilters($scopedReservations(), $status, $search)
            ->paginate(10)
            ->withQueryString();

        $pageIds = $reservations->getCollection()->pluck('reservations_id')->all();
        $statusLogsByReservation = $pageIds
            ? AuditLog::with('user')
                ->where('entity_type', 'reservation')
                ->whereIn('entity_id', $pageIds)
                ->whereIn('action', ['reservation_completed', 'reservation_marked_no_show'])
                ->orderByDesc('created_at')
                ->get()
                ->groupBy('entity_id')
            : collect();

        $activeOutlet = $this->activeOutletModel($request, $outletAccess, $outletId);
        $dateLabel = $hasExactDate ? Carbon::parse($date)->locale(app()->getLocale())->translatedFormat('d M Y') : 'Semua tanggal';

        return view('cashier.history', compact(
            'metrics',
            'reservations',
            'statusLogsByReservation',
            'date',
            'dateLabel',
            'status',
            'search',
            'outletId',
            'activeOutlet',
        ));
    }

    public function exportHistory(Request $request, OutletAccess $outletAccess): StreamedResponse
    {
        $outletId = $this->resolveOutletId($request, $outletAccess);
        [$date, $hasExactDate, $status, $search] = $this->historyFilters($request);

        $rows = $this->applyHistoryFilters(
            $this->historyBaseQuery($request, $outletAccess, $outletId, $hasExactDate, $date),
            $status,
            $search,
        )->lazy(200);

        $filename = 'riwayat-kasir-rrms-' . now('Asia/Jakarta')->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                'Kode Reservasi',
                'Nama Tamu',
                'Outlet',
                'Meja',
                'Tanggal',
                'Jam',
                'Tamu Hadir',
                'Status',
                'Petugas Kasir (Check-in)',
                'Waktu Check-in Terakhir',
                'Diverifikasi Finance',
                'Catatan Check-in',
            ]);

            foreach ($rows as $row) {
                $arrived = (int) ($row->checkIns->max('cumulative_guest_count') ?? 0);
                $lastCheckIn = $row->checkIns->sortByDesc('checked_in_at')->first();
                $tables = $row->tables->map(fn ($t) => $t->table_code . ($t->area ? ' (' . $t->area->name . ')' : ''))->implode(', ');
                $cashierNames = $row->checkIns->map(fn ($ci) => $ci->checkedInBy?->name)->filter()->unique()->implode(', ');
                $notes = $row->checkIns->map(fn ($ci) => $ci->notes)->filter()->implode(' | ');

                fputcsv($stream, array_map($this->safeCsvValue(...), [
                    (string) $row->reservation_code,
                    (string) $row->customer_name,
                    (string) ($row->outlet?->name ?? '—'),
                    $tables !== '' ? $tables : 'Tanpa kursi',
                    $row->reservation_date?->format('Y-m-d') ?? '—',
                    substr((string) $row->start_time, 0, 5),
                    $arrived . ' / ' . $row->guest_count,
                    (string) $row->reservation_status,
                    $cashierNames !== '' ? $cashierNames : '—',
                    $lastCheckIn?->checked_in_at?->format('Y-m-d H:i') ?? '—',
                    $row->latestPayment?->verifiedBy?->name ?? '—',
                    $notes !== '' ? $notes : '—',
                ]));
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0: string, 1: bool, 2: string, 3: string} */
    private function historyFilters(Request $request): array
    {
        $date = trim((string) $request->query('date', ''));
        $hasExactDate = $date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1;
        if (! $hasExactDate) {
            $date = '';
        }

        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));
        if ($search === '' && $request->filled('lookup')) {
            $search = trim((string) $request->query('lookup', ''));
        }

        return [$date, $hasExactDate, $status, $search];
    }

    private function historyBaseQuery(
        Request $request,
        OutletAccess $outletAccess,
        ?int $outletId,
        bool $hasExactDate,
        string $date,
    ): Builder {
        return $this->scopedReservationQuery($request, $outletAccess, $outletId)
            ->where(function (Builder $q): void {
                $q->whereIn('reservation_status', ['confirmed', 'partially_arrived', 'checked_in', 'completed', 'no_show'])
                    ->orWhereHas('checkIns')
                    ->orWhereHas('cancellationRequests', fn (Builder $c) => $c->where('requested_by', 'cashier'))
                    ->orWhere(function (Builder $sub): void {
                        $sub->where('reservation_status', 'cancelled')
                            ->whereHas('latestPayment', fn (Builder $p) => $p->where('payment_status', 'paid'));
                    });
            })
            ->when($hasExactDate, fn (Builder $q) => $q->whereDate('reservation_date', $date));
    }

    private function applyHistoryFilters(Builder $query, string $status, string $search): Builder
    {
        return $query
            ->with([
                'outlet',
                'tables.area',
                'latestPayment.verifiedBy',
                'checkIns.checkedInBy',
                'cancellationRequests.reviewedBy',
            ])
            ->when(
                in_array($status, ['checked_in', 'completed', 'partially_arrived', 'confirmed', 'no_show', 'cancelled'], true),
                fn (Builder $q) => $q->where('reservation_status', $status),
            )
            ->when($search !== '', function (Builder $q) use ($search): void {
                $term = '%' . addcslashes($search, '%_\\') . '%';
                $q->where(function (Builder $match) use ($term): void {
                    $match->where('reservation_code', 'like', $term)
                        ->orWhere('customer_name', 'like', $term)
                        ->orWhereHas('checkIns.checkedInBy', fn (Builder $u) => $u->where('name', 'like', $term))
                        ->orWhereHas('payments.verifiedBy', fn (Builder $u) => $u->where('name', 'like', $term))
                        ->orWhereHas('cancellationRequests.reviewedBy', fn (Builder $u) => $u->where('name', 'like', $term));
                });
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('reservations_id');
    }

    private function safeCsvValue(string $value): string
    {
        return preg_match('/^\s*[=+\-@]/u', $value) ? "'" . $value : $value;
    }

    public function cancellations(Request $request, OutletAccess $outletAccess): View
    {
        $outletId = $this->resolveOutletId($request, $outletAccess);
        $queryText = trim((string) $request->query('q', ''));
        $selectedReservation = null;

        if ($request->filled('reservation')) {
            $selectedReservation = $this->scopedReservationQuery($request, $outletAccess, $outletId)
                ->with(['outlet', 'tables.area', 'latestPayment', 'cancellationRequests.reviewedBy'])
                ->whereKey($request->integer('reservation'))
                ->first();
        } elseif ($queryText !== '') {
            $selectedReservation = $this->scopedReservationQuery($request, $outletAccess, $outletId)
                ->with(['outlet', 'tables.area', 'latestPayment', 'cancellationRequests.reviewedBy'])
                ->where(function (Builder $q) use ($queryText): void {
                    $q->whereRaw('LOWER(reservation_code) = ?', [mb_strtolower($queryText)])
                        ->orWhere('reservation_code', 'like', "%{$queryText}%")
                        ->orWhere('customer_name', 'like', "%{$queryText}%");
                })
                ->orderByDesc('created_at')
                ->orderByDesc('reservations_id')
                ->first();
        }

        if (! $selectedReservation && $queryText === '' && ! $request->filled('reservation')) {
            $selectedReservation = $this->scopedReservationQuery($request, $outletAccess, $outletId)
                ->with(['outlet', 'tables.area', 'latestPayment', 'cancellationRequests.reviewedBy'])
                ->whereIn('reservation_status', ['confirmed', 'partially_arrived', 'pending_payment'])
                ->orderByDesc('created_at')
                ->orderByDesc('reservations_id')
                ->first();
        }

        $searchInput = $queryText !== ''
            ? $queryText
            : ($selectedReservation ? ($selectedReservation->reservation_code . ' • ' . $selectedReservation->customer_name) : '');

        return view('cashier.cancellations', compact('selectedReservation', 'searchInput', 'queryText', 'outletId'));
    }

    public function storeCancellation(Request $request, OutletAccess $outletAccess): RedirectResponse
    {
        $data = $request->validate([
            'reservations_id' => ['required', 'integer', 'exists:reservations,reservations_id'],
            'reason' => ['required', 'string', 'max:1000'],
            'manager_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'reservations_id.required' => 'Pilih atau cari reservasi terlebih dahulu.',
            'reason.required' => 'Alasan pembatalan wajib diisi.',
        ]);

        $reservation = DB::transaction(function () use ($request, $outletAccess, $data): Reservation {
            $locked = Reservation::whereKey((int) $data['reservations_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $outletAccess->assertCanAccess($request->user(), (int) $locked->outlets_id);

            if (in_array($locked->reservation_status, ['cancelled', 'completed', 'expired', 'no_show'], true)) {
                throw ValidationException::withMessages([
                    'reason' => 'Reservasi ini sudah berstatus ' . $locked->reservation_status . ' dan tidak dapat diajukan pembatalan.',
                ]);
            }

            if ($locked->cancellationRequests()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages([
                    'reason' => 'Permintaan pembatalan untuk reservasi ini sudah ada dan sedang menunggu keputusan Manager.',
                ]);
            }

            $reasonText = trim($data['reason']);
            $noteText = trim((string) ($data['manager_note'] ?? ''));
            $combinedReason = $noteText !== '' ? ($reasonText . "\n\nCatatan Kasir: " . $noteText) : $reasonText;

            $cancellation = CancellationRequest::create([
                'reservations_id' => $locked->reservations_id,
                'requested_by' => 'cashier',
                'reason' => $combinedReason,
                'status' => 'pending',
                'created_at' => now(),
            ]);

            AuditLog::create([
                'users_id' => $request->user()?->users_id,
                'action' => 'cancellation_requested_by_cashier',
                'entity_type' => 'cancellation_request',
                'entity_id' => $cancellation->cancellation_requests_id,
                'new_values' => [
                    'reservation_code' => $locked->reservation_code,
                    'requested_by' => 'cashier',
                    'status' => 'pending',
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $locked;
        });

        return redirect()
            ->route('cashier.cancellations.index', array_filter([
                'reservation' => $reservation->reservations_id,
                'outlet' => $request->input('outlet'),
            ]))
            ->with('success', 'Permintaan pembatalan untuk ' . $reservation->reservation_code . ' telah dikirim ke Manager.');
    }

    private function resolveOutletId(Request $request, OutletAccess $outletAccess): ?int
    {
        $outlet = $request->query('outlet');
        $user = $request->user();
        if ($outletAccess->isGlobal($user)) {
            return is_numeric($outlet) && (int) $outlet > 0 ? (int) $outlet : null;
        }
        $outletIds = $outletAccess->outletIds($user);
        if ($outlet === null || $outlet === '' || $outlet === 'all') {
            return null;
        }
        abort_unless(is_numeric($outlet) && in_array((int) $outlet, $outletIds, true), 404);

        return (int) $outlet;
    }

    private function scopedReservationQuery(Request $request, OutletAccess $outletAccess, ?int $outletId): Builder
    {
        $query = Reservation::query();
        $outletAccess->scope($query, $request->user());
        if ($outletId !== null) {
            $query->where('outlets_id', $outletId);
        }

        return $query;
    }

    private function applySearch(Builder $query, string $search): void
    {
        $query->where(function (Builder $q) use ($search): void {
            $q->where('reservation_code', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%");
        });
    }

    private function resolveSelectedReservation(
        Request $request,
        OutletAccess $outletAccess,
        ?int $outletId,
        ?Reservation $fallback,
    ): ?Reservation {
        if ($request->filled('reservation')) {
            $found = $this->scopedReservationQuery($request, $outletAccess, $outletId)
                ->with(['outlet', 'tables.area', 'latestPayment', 'qrToken', 'checkIns.checkedInBy'])
                ->whereKey($request->integer('reservation'))
                ->first();
            if ($found) {
                return $found;
            }
        }

        if ($fallback) {
            $fallback->loadMissing(['outlet', 'tables.area', 'latestPayment', 'qrToken', 'checkIns.checkedInBy']);
        }

        return $fallback;
    }

    private function upcomingTwoHourCount(Builder $todayBase, string $tz): int
    {
        $now = now($tz);
        $from = $now->format('H:i:s');
        $to = $now->copy()->addHours(2)->format('H:i:s');

        return $todayBase
            ->whereIn('reservation_status', ['confirmed', 'partially_arrived'])
            ->where('start_time', '>=', $from)
            ->where('start_time', '<=', $to)
            ->count();
    }

    private function pendingCancellationCount(Request $request, OutletAccess $outletAccess, ?int $outletId): int
    {
        $allowedIds = $outletAccess->isGlobal($request->user())
            ? null
            : $outletAccess->outletIds($request->user());

        return CancellationRequest::query()
            ->where('status', 'pending')
            ->whereHas('reservation', function (Builder $q) use ($allowedIds, $outletId): void {
                if ($allowedIds !== null) {
                    $q->whereIn('outlets_id', $allowedIds ?: [0]);
                }
                if ($outletId !== null) {
                    $q->where('outlets_id', $outletId);
                }
            })
            ->count();
    }

    private function areasForScope(Request $request, OutletAccess $outletAccess, ?int $outletId)
    {
        $query = Area::query()
            ->with('outlet:outlets_id,name')
            ->where('is_active', true)
            ->orderBy('name')
            ->orderBy('outlets_id');
        if ($outletId !== null) {
            $query->where('outlets_id', $outletId);
        } elseif (! $outletAccess->isGlobal($request->user())) {
            $ids = $outletAccess->outletIds($request->user());
            $query->whereIn('outlets_id', $ids ?: [0]);
        }

        $areas = $query->get(['areas_id', 'outlets_id', 'name']);
        $multipleOutlets = $outletId === null && $areas->pluck('outlets_id')->unique()->count() > 1;
        if ($multipleOutlets) {
            $areas->each(function (Area $area): void {
                $outletName = (string) ($area->outlet?->name ?? '');
                $shortOutlet = str_contains(mb_strtolower($outletName), 'ibis')
                    ? 'ibis'
                    : (str_contains(mb_strtolower($outletName), 'novotel') ? 'Novotel' : ($outletName ?: 'Outlet'));
                $area->name = $area->name . ' (' . $shortOutlet . ')';
            });
        }

        return $areas;
    }

    private function activeOutletModel(Request $request, OutletAccess $outletAccess, ?int $outletId): ?Outlet
    {
        if ($outletId !== null) {
            return Outlet::find($outletId);
        }

        if (! $outletAccess->isGlobal($request->user())) {
            $ids = $outletAccess->outletIds($request->user());
            if (count($ids) === 1) {
                return Outlet::find($ids[0]);
            }
        }

        return null;
    }
}
