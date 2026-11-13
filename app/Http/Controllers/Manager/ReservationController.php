<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReservationController extends Controller
{
    private const STATUSES = [
        'draft', 'pending_payment', 'confirmed', 'partially_arrived', 'checked_in',
        'completed', 'cancelled', 'expired', 'no_show',
    ];

    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $outlets = Outlet::ordered()->get();
        $base = $this->baseQuery($filters);

        $stats = [
            'total' => (clone $base)->count(),
            'waiting' => (clone $base)->whereHas('latestPayment', fn (Builder $q) => $q->where('payment_status', 'pending_verification'))->count(),
            'confirmed' => (clone $base)->whereIn('reservation_status', ['confirmed', 'partially_arrived', 'checked_in', 'completed'])->count(),
            'arrived' => (clone $base)->whereIn('reservation_status', ['checked_in', 'completed'])->count(),
        ];

        $reservations = $this->applyFilters(clone $base, $filters)
            ->with(['outlet', 'tables.area', 'latestPayment'])
            ->orderByDesc('reservation_date')
            ->orderByDesc('start_time')
            ->orderByDesc('reservations_id')
            ->paginate(6)
            ->withQueryString();

        $lastUpdated = (clone $base)->max('updated_at');

        return view('manager.reservations.index', [
            'outlets' => $outlets,
            'filters' => $filters,
            'stats' => $stats,
            'reservations' => $reservations,
            'dateLabel' => Carbon::parse($filters['date'])->locale(app()->getLocale())->translatedFormat('d F Y'),
            'lastUpdated' => $lastUpdated ? Carbon::parse($lastUpdated)->locale(app()->getLocale())->translatedFormat('H.i') : null,
            'reservationStatuses' => self::STATUSES,
        ]);
    }

    public function show(Reservation $reservation): View
    {
        $reservation->load([
            'outlet',
            'tables.area',
            'items',
            'latestPayment',
            'checkIns.checkedInBy',
            'cancellationRequests.reviewedBy',
        ]);

        $cancellation = $reservation->cancellationRequests->sortByDesc('created_at')->first();

        return view('manager.reservations.show', [
            'reservation' => $reservation,
            'cancellation' => $cancellation,
            'reservationStatus' => __('manager.res_status_' . $reservation->reservation_status),
            'paymentStatus' => $reservation->latestPayment
                ? __('manager.pay_status_' . $reservation->latestPayment->payment_status)
                : __('manager.res_no_payment'),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $rows = $this->applyFilters($this->baseQuery($filters), $filters)
            ->with(['outlet', 'tables', 'latestPayment'])
            ->orderByDesc('reservation_date')
            ->orderByDesc('start_time')
            ->lazy(500);

        return response()->streamDownload(function () use ($rows): void {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, [
                __('manager.res_col_code_guest'),
                __('manager.res_col_outlet_table'),
                __('manager.res_col_schedule'),
                __('manager.res_col_guests'),
                __('manager.res_col_payment'),
                __('manager.res_col_status'),
            ]);

            foreach ($rows as $reservation) {
                fputcsv($stream, array_map($this->safeCsvValue(...), [
                    $reservation->reservation_code . ' / ' . $reservation->customer_name,
                    $reservation->outlet?->name . ' / ' . $reservation->tables->pluck('table_code')->join(', '),
                    $reservation->reservation_date->format('Y-m-d') . ' ' . substr((string) $reservation->start_time, 0, 5),
                    (string) $reservation->guest_count,
                    $reservation->latestPayment ? __('manager.pay_status_' . $reservation->latestPayment->payment_status) : __('manager.res_no_payment'),
                    __('manager.res_status_' . $reservation->reservation_status),
                ]));
            }

            fclose($stream);
        }, 'reservasi-manager-' . $filters['date'] . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filters(Request $request): array
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'outlet' => ['nullable', 'regex:/^(all|[0-9]+)$/'],
            'status' => ['nullable', Rule::in(['all', ...self::STATUSES])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        return [
            'date' => $filters['date'] ?? now()->toDateString(),
            'outlet' => $filters['outlet'] ?? 'all',
            'status' => $filters['status'] ?? 'all',
            'q' => trim($filters['q'] ?? ''),
        ];
    }

    private function baseQuery(array $filters): Builder
    {
        return Reservation::query()
            ->whereDate('reservation_date', $filters['date'])
            ->when($filters['outlet'] !== 'all', fn (Builder $q) => $q->where('outlets_id', (int) $filters['outlet']));
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] !== 'all', fn (Builder $q) => $q->where('reservation_status', $filters['status']))
            ->when($filters['q'] !== '', function (Builder $q) use ($filters): void {
                $term = '%' . addcslashes($filters['q'], '%_\\') . '%';
                $q->where(function (Builder $match) use ($term): void {
                    $match->where('reservation_code', 'like', $term)
                        ->orWhere('customer_name', 'like', $term)
                        ->orWhere('customer_phone', 'like', $term)
                        ->orWhere('customer_email', 'like', $term)
                        ->orWhereHas('tables', fn (Builder $tables) => $tables->where('table_code', 'like', $term));
                });
            });
    }

    private function safeCsvValue(string $value): string
    {
        return preg_match('/^\s*[=+\-@]/u', $value) ? "'" . $value : $value;
    }
}
