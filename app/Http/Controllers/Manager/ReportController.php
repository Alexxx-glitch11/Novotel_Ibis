<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\CancellationRequest;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\Reservation;
use App\Services\OutletAccess;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request, OutletAccess $outletAccess): View
    {
        $timezone = config('reservation.timezone', 'Asia/Jakarta');
        $today = Carbon::today($timezone);
        $rangeOptions = [
            ($today->copy()->subDays(6)->toDateString() . '_' . $today->toDateString()) => '7 hari terakhir',
            ($today->copy()->startOfMonth()->toDateString() . '_' . $today->toDateString()) => 'Bulan berjalan',
            ($today->copy()->subDays(13)->toDateString() . '_' . $today->copy()->subDays(7)->toDateString()) => '7 hari sebelumnya',
        ];

        $selectedRange = (string) $request->query('date_range', array_key_first($rangeOptions));
        if (! array_key_exists($selectedRange, $rangeOptions)) {
            $selectedRange = array_key_first($rangeOptions);
        }
        [$startValue, $endValue] = explode('_', $selectedRange, 2);
        $startDate = Carbon::createFromFormat('Y-m-d', $startValue, $timezone)->startOfDay();
        $endDate = Carbon::createFromFormat('Y-m-d', $endValue, $timezone)->endOfDay();

        $outletQuery = Outlet::where('is_active', true)->ordered();
        $outletAccess->scope($outletQuery, $request->user());
        $outlets = $outletQuery->get();
        $selectedOutlet = (string) $request->query('outlet', 'all');
        $allowedOutletIds = null;
        if (! $outletAccess->isGlobal($request->user())) {
            $allowedOutletIds = $outletAccess->outletIds($request->user());
            abort_if($selectedOutlet !== 'all' && ! in_array((int) $selectedOutlet, $allowedOutletIds, true), 404);
        } elseif ($selectedOutlet !== 'all' && ! $outlets->contains('outlets_id', (int) $selectedOutlet)) {
            $selectedOutlet = 'all';
        }

        $reservationQuery = Reservation::query()
            ->whereBetween('reservation_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($selectedOutlet !== 'all', fn (Builder $query) => $query->where('outlets_id', (int) $selectedOutlet))
            ->when($selectedOutlet === 'all' && $allowedOutletIds !== null, fn (Builder $query) => $query->whereIn('outlets_id', $allowedOutletIds));
        $paymentQuery = Payment::query()
            ->whereRaw('DATE(COALESCE(verified_at, created_at)) BETWEEN ? AND ?', [$startDate->toDateString(), $endDate->toDateString()])
            ->whereHas('reservation', function (Builder $query) use ($selectedOutlet, $allowedOutletIds): void {
                $query->where('reservation_status', '<>', 'cancelled')
                    ->when($selectedOutlet !== 'all', fn (Builder $reservation) => $reservation->where('outlets_id', (int) $selectedOutlet))
                    ->when($selectedOutlet === 'all' && $allowedOutletIds !== null, fn (Builder $reservation) => $reservation->whereIn('outlets_id', $allowedOutletIds));
            });

        // Revenue is always based on verified Paid payments; pending/rejected/cancelled reservations are excluded.
        $paidPayments = (clone $paymentQuery)->where('payment_status', 'paid');
        $paidPaymentsCount = (clone $paidPayments)->count();
        $totalRevenue = (float) (clone $paidPayments)->sum('amount');
        $totalReservations = (clone $reservationQuery)->count();
        $confirmedReservations = (clone $reservationQuery)
            ->whereIn('reservation_status', ['confirmed', 'partially_arrived', 'checked_in', 'completed'])
            ->count();
        $averageTransaction = $paidPaymentsCount > 0 ? $totalRevenue / $paidPaymentsCount : 0;
        $displayRevenueText = 'Rp' . number_format($totalRevenue / 1_000_000, 1, ',', '.') . ' jt';
        $displayPaidCount = $paidPaymentsCount;
        $displayTotalRes = $totalReservations;
        $displayConfirmedRes = $confirmedReservations;
        $displayAvgText = 'Rp' . number_format($averageTransaction / 1_000, 0, ',', '.') . ' rb';
        $chartTotalText = $displayRevenueText;

        $dailyStats = [];
        $maxDayRevenue = 1.0;
        $cursor = $startDate->copy();
        while ($cursor->lte($endDate)) {
            $dayRevenue = (float) (clone $paidPayments)
                ->whereRaw('DATE(COALESCE(verified_at, created_at)) = ?', [$cursor->toDateString()])
                ->sum('amount');
            $dailyStats[] = [
                'day' => $cursor->locale('id')->isoFormat('ddd'),
                'date' => $cursor->format('d M'),
                'revenue' => $dayRevenue,
                'percent' => 8,
                'bg' => 'bg-[#e0e7ff]',
            ];
            $maxDayRevenue = max($maxDayRevenue, $dayRevenue);
            $cursor->addDay();
        }
        foreach ($dailyStats as &$stat) {
            $stat['percent'] = $stat['revenue'] > 0 ? max(14, (int) round($stat['revenue'] / $maxDayRevenue * 100)) : 8;
        }
        unset($stat);

        $outletBreakdown = [];
        $tableSummary = [];
        foreach ($outlets->when($selectedOutlet !== 'all', fn ($collection) => $collection->where('outlets_id', (int) $selectedOutlet)) as $outlet) {
            $outletReservations = Reservation::query()
                ->where('outlets_id', $outlet->outlets_id)
                ->whereBetween('reservation_date', [$startDate->toDateString(), $endDate->toDateString()]);
            $outletPayments = Payment::query()
                ->where('payment_status', 'paid')
                ->whereRaw('DATE(COALESCE(verified_at, created_at)) BETWEEN ? AND ?', [$startDate->toDateString(), $endDate->toDateString()])
                ->whereHas('reservation', fn (Builder $query) =>
                    $query->where('outlets_id', $outlet->outlets_id)
                        ->where('reservation_status', '<>', 'cancelled')
                );
            $outletRevenue = (float) (clone $outletPayments)->sum('amount');
            $outletPaidCount = (clone $outletPayments)->count();
            $outletName = str_contains(mb_strtolower($outlet->name), 'ibis') ? 'ibis' : 'Novotel';

            $outletBreakdown[] = [
                'name' => $outletName,
                'revenue_formatted' => 'Rp' . number_format($outletRevenue / 1_000_000, 1, ',', '.') . ' jt',
                'revenue_raw' => $outletRevenue,
                'percent' => $totalRevenue > 0 ? (int) round($outletRevenue / $totalRevenue * 100) : 0,
                'bar_color' => $outletName === 'Novotel' ? 'bg-[#14123f]' : 'bg-[#86efac]',
            ];
            $tableSummary[] = [
                'outlet' => $outletName,
                'reservations' => (clone $outletReservations)->count(),
                'cancellations' => CancellationRequest::query()->whereHas('reservation', fn (Builder $query) =>
                    $query->where('outlets_id', $outlet->outlets_id)->whereBetween('reservation_date', [$startDate->toDateString(), $endDate->toDateString()])
                )->count(),
                'paid_count' => $outletPaidCount,
                'revenue_formatted' => 'Rp' . number_format($outletRevenue, 0, ',', '.'),
            ];
        }

        $selectedPaymentStatus = 'paid';

        return view('manager.reports.index', compact(
            'outlets', 'selectedOutlet', 'selectedRange', 'rangeOptions', 'selectedPaymentStatus',
            'displayRevenueText', 'displayPaidCount', 'displayTotalRes', 'displayConfirmedRes',
            'displayAvgText', 'chartTotalText', 'dailyStats', 'outletBreakdown', 'tableSummary'
        ));
    }
}
