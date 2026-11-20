<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CancellationRequest;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\TaxServiceRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class OperationalDashboardService
{
    private const CONFIRMED = ['confirmed', 'partially_arrived', 'checked_in', 'completed'];

    public function build(Request $request, bool $adminScope = false): array
    {
        $outletQuery = Outlet::where('is_active', true)->ordered();
        $allowedOutletIds = null;

        if (! $adminScope) {
            $access = app(OutletAccess::class);
            $access->scope($outletQuery, $request->user());
            if (! $access->isGlobal($request->user())) {
                $allowedOutletIds = $access->outletIds($request->user());
                abort_if(
                    $request->filled('outlet') && ! in_array((int) $request->query('outlet'), $allowedOutletIds, true),
                    404,
                );
            }
        }

        $outlets = $outletQuery->get();

        $selected = null;
        if ($request->filled('outlet')) {
            $selected = $outlets->firstWhere('outlets_id', (int) $request->query('outlet'));
        } elseif ($outlets->count() === 1) {
            $selected = $outlets->first();
        }

        $effectiveOutletIds = $selected
            ? [(int) $selected->outlets_id]
            : $outlets->pluck('outlets_id')->map(fn ($id) => (int) $id)->all();

        $revenue = $this->revenue($effectiveOutletIds);

        return [
            'outlets' => $outlets,
            'selected' => $selected,
            'effectiveOutletIds' => $effectiveOutletIds,
            'stats' => $this->stats($selected, $effectiveOutletIds, $revenue),
            'cancellations' => $this->cancellations($effectiveOutletIds),
            'revenue' => $revenue['bars'],
            'revenueTotal' => $this->rupiahShort($revenue['total']),
            'rates' => $this->rates($selected, $outlets),
            'activities' => $this->activities($request, $adminScope),
        ];
    }

    /** @param list<int> $effectiveOutletIds */
    private function stats(?Outlet $selected, array $effectiveOutletIds, array $revenue): array
    {
        $tz = config('reservation.timezone', 'Asia/Jakarta');
        $activeUpcoming = Reservation::query()
            ->whereDate('reservation_date', '>=', now($tz)->toDateString())
            ->whereIn('outlets_id', $effectiveOutletIds);

        $confirmed = (clone $activeUpcoming)->whereIn('reservation_status', self::CONFIRMED)->count();
        $waiting = (clone $activeUpcoming)->where('reservation_status', 'pending_payment')->count();
        $guests = (int) (clone $activeUpcoming)
            ->whereIn('reservation_status', [...self::CONFIRMED, 'pending_payment'])
            ->sum('guest_count');

        $pendingCancel = $this->pendingCancellations($effectiveOutletIds)->count();

        return [
            [
                'letter' => 'R',
                'tone' => 'blue',
                'label' => __('manager.stat_res_label'),
                'value' => (string) ($confirmed + $waiting),
                'note' => __('manager.stat_res_note', ['confirmed' => $confirmed, 'waiting' => $waiting]),
            ],
            [
                'letter' => 'P',
                'tone' => 'amber',
                'label' => __('manager.stat_cancel_label'),
                'value' => (string) $pendingCancel,
                'note' => $pendingCancel ? __('manager.stat_cancel_note') : __('manager.stat_cancel_none'),
            ],
            [
                'letter' => 'P',
                'tone' => 'green',
                'label' => __('manager.stat_paid_label'),
                'value' => $this->rupiahShort($revenue['total']),
                'note' => trans_choice('manager.stat_paid_note', $revenue['count'], ['count' => $revenue['count']]),
            ],
            [
                'letter' => 'T',
                'tone' => 'blue',
                'label' => __('manager.stat_guests_label'),
                'value' => (string) $guests,
                'note' => $selected ? __('manager.stat_guests_outlet', ['outlet' => $selected->name]) : __('manager.stat_guests_all'),
            ],
        ];
    }

    /** @param list<int> $effectiveOutletIds */
    private function pendingCancellations(array $effectiveOutletIds): Builder
    {
        return CancellationRequest::query()
            ->where('status', 'pending')
            ->whereHas('reservation', fn (Builder $r) => $r->whereIn('outlets_id', $effectiveOutletIds));
    }

    /** @param list<int> $effectiveOutletIds */
    private function cancellations(array $effectiveOutletIds): array
    {
        $loc = app()->getLocale();

        return $this->pendingCancellations($effectiveOutletIds)
            ->with('reservation.outlet')
            ->latest('created_at')
            ->latest('cancellation_requests_id')
            ->limit(5)
            ->get()
            ->map(function (CancellationRequest $c) use ($loc) {
                $r = $c->reservation;
                $time = str_replace(':', '.', substr((string) $r->start_time, 0, 5));
                $date = $r->reservation_date->isToday()
                    ? ''
                    : $r->reservation_date->copy()->locale($loc)->translatedFormat('d M') . ', ';

                return [
                    'id' => $c->cancellation_requests_id,
                    'reservation_id' => $r->reservations_id,
                    'guest' => $r->customer_name,
                    'slot' => $r->outlet->name . ' • ' . $date . $time,
                    'reason' => Str::limit($c->reason, 40),
                ];
            })
            ->all();
    }

    /** @param list<int> $effectiveOutletIds */
    private function revenue(array $effectiveOutletIds): array
    {
        $from = now()->subDays(6)->startOfDay();
        $loc = app()->getLocale();

        $paid = fn () => Payment::query()
            ->where('payment_status', 'paid')
            ->whereRaw('COALESCE(verified_at, created_at) >= ?', [$from])
            ->whereHas('reservation', function (Builder $r) use ($effectiveOutletIds): void {
                $r->where('reservation_status', '<>', 'cancelled')
                    ->whereIn('outlets_id', $effectiveOutletIds);
            });

        $perDay = $paid()
            ->selectRaw('DATE(COALESCE(verified_at, created_at)) as day, SUM(amount) as total')
            ->groupByRaw('DATE(COALESCE(verified_at, created_at))')
            ->pluck('total', 'day');

        $bars = [];
        $total = 0.0;

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $value = (float) ($perDay[$date->toDateString()] ?? 0);
            $total += $value;

            $bars[] = [
                'day' => $date->copy()->locale($loc)->translatedFormat('D'),
                'value' => $value,
                'label' => $this->rupiahShort($value),
            ];
        }

        return ['bars' => $bars, 'total' => $total, 'count' => $paid()->count()];
    }

    private function rates(?Outlet $selected, $outlets): array
    {
        $today = now(config('reservation.timezone', 'Asia/Jakarta'))->toDateString();

        return $outlets
            ->when($selected, fn ($c) => $c->where('outlets_id', $selected->outlets_id))
            ->map(function (Outlet $o) use ($today) {
                $rate = TaxServiceRate::activeFor((int) $o->outlets_id, $today);

                return [
                    'outlet' => $o->name,
                    'tax' => $rate ? $this->percent($rate->tax_rate) : '—',
                    'service' => $rate ? $this->percent($rate->service_rate) : '—',
                ];
            })
            ->values()
            ->all();
    }

    private function activities(Request $request, bool $adminScope): array
    {
        $loc = app()->getLocale();
        $query = AuditLog::query()->with('user');

        if (! $adminScope) {
            app(OutletAccess::class)->scopeAudit($query, $request->user());
        }

        return $query
            ->orderByDesc('created_at')
            ->orderByDesc('audit_logs_id')
            ->limit(5)
            ->get()
            ->map(function (AuditLog $log) use ($loc) {
                $at = Carbon::parse($log->created_at);
                $type = Str::singular(Str::snake(class_basename((string) $log->entity_type)));
                $action = strtolower((string) $log->action);
                $verb = Str::afterLast($action, '_');

                $entityKey = 'manager.ent_' . $type;
                $verbKey = 'manager.verb_' . $verb;

                $entity = Lang::has($entityKey) ? __($entityKey) : Str::headline($type);
                $verbT = Lang::has($verbKey) ? __($verbKey) : str_replace('_', ' ', $action);

                return [
                    'time' => $at->isToday() ? $at->format('H.i') : $at->copy()->locale($loc)->translatedFormat('d M'),
                    'text' => $entity . ' ' . $verbT,
                    'actor' => $log->user?->email ?? 'Sistem',
                ];
            })
            ->all();
    }

    public function rupiahShort(float $n): string
    {
        [$dec, $thou] = app()->getLocale() === 'en' ? ['.', ','] : [',', '.'];

        if ($n >= 1_000_000_000) {
            return 'Rp' . number_format($n / 1_000_000_000, 1, $dec, $thou) . ' ' . __('manager.unit_billion');
        }
        if ($n >= 1_000_000) {
            return 'Rp' . number_format($n / 1_000_000, 1, $dec, $thou) . ' ' . __('manager.unit_million');
        }

        return 'Rp' . number_format($n, 0, $dec, $thou);
    }

    private function percent($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 2, ',', ''), '0'), ',') . '%';
    }
}
