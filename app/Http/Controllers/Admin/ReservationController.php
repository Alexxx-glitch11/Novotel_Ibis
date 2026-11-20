<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Outlet;
use App\Models\Reservation;
use App\Services\ReservationQrCode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReservationController extends Controller
{
    private const STATUSES = [
        'pending_payment', 'confirmed', 'partially_arrived', 'checked_in',
        'completed', 'cancelled', 'expired', 'no_show',
    ];

    /**
     * Daftar reservasi & pemindaian QR/cari kode untuk Admin.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $outletId = is_numeric($request->query('outlet')) ? (int) $request->query('outlet') : null;
        $status = (string) $request->query('status', 'all');
        $date = trim((string) $request->query('date', ''));
        $date = preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) === 1 ? $date : '';
        $search = trim((string) $request->query('q', ''));

        // Hasil pindai QR / input kode: langsung buka halaman detail reservasi.
        $lookup = trim((string) $request->query('lookup', ''));
        if ($lookup !== '') {
            $found = Reservation::query()
                ->when(strlen($lookup) === 64, fn (Builder $q) => $q->whereHas('qrToken', fn (Builder $t) => $t->where('token', $lookup)))
                ->when(strlen($lookup) !== 64, fn (Builder $q) => $q->whereRaw('LOWER(reservation_code) = ?', [mb_strtolower($lookup)]))
                ->first();

            if (! $found) {
                return redirect()
                    ->route('admin.reservations.index', array_filter(['outlet' => $outletId]))
                    ->withErrors(['lookup' => 'QR atau kode reservasi "' . e(mb_substr($lookup, 0, 40)) . '" tidak ditemukan.']);
            }

            return redirect()->route('admin.reservations.show', array_filter([
                'reservation' => $found->reservations_id,
                'outlet' => $outletId,
            ]));
        }

        $base = fn (): Builder => Reservation::query()
            ->when($outletId, fn (Builder $q) => $q->where('outlets_id', $outletId))
            ->when($date !== '', fn (Builder $q) => $q->whereDate('reservation_date', $date));

        $stats = [
            'total' => $base()->count(),
            'waiting' => $base()->where('reservation_status', 'pending_payment')->count(),
            'confirmed' => $base()->whereIn('reservation_status', ['confirmed', 'partially_arrived', 'checked_in', 'completed'])->count(),
            'cancelled' => $base()->whereIn('reservation_status', ['cancelled', 'no_show', 'expired'])->count(),
        ];

        $reservations = $base()
            ->with(['outlet', 'tables.area', 'latestPayment'])
            ->when(in_array($status, self::STATUSES, true), fn (Builder $q) => $q->where('reservation_status', $status))
            ->when($search !== '', function (Builder $q) use ($search): void {
                $term = '%' . addcslashes($search, '%_\\') . '%';
                $q->where(function (Builder $match) use ($term): void {
                    $match->where('reservation_code', 'like', $term)
                        ->orWhere('customer_name', 'like', $term)
                        ->orWhere('customer_phone', 'like', $term)
                        ->orWhere('customer_email', 'like', $term);
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('reservations_id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.reservations', [
            'outlets' => Outlet::ordered()->get(['outlets_id', 'name']),
            'outletId' => $outletId,
            'status' => $status,
            'statuses' => self::STATUSES,
            'date' => $date,
            'search' => $search,
            'stats' => $stats,
            'reservations' => $reservations,
        ]);
    }

    /**
     * Halaman detail lengkap reservasi (masih di bawah menu Reservasi Admin).
     */
    public function show(Request $request, Reservation $reservation, ReservationQrCode $qrCode): View
    {
        $outletId = is_numeric($request->query('outlet')) ? (int) $request->query('outlet') : null;

        $reservation->load([
            'outlet',
            'tables.area',
            'items',
            'eventContent',
            'latestPayment.verifiedBy',
            'payments.verifiedBy',
            'qrToken',
            'checkIns.checkedInBy',
            'cancellationRequests.reviewedBy',
        ]);

        $qrDataUri = $reservation->qrToken?->token
            ? $qrCode->dataUri($reservation->qrToken->token)
            : null;

        $eventTotal = max(0.0, round((float) $reservation->subtotal - (float) $reservation->items->sum('line_total'), 2));

        $paymentIds = $reservation->payments->pluck('payments_id')->all();
        $checkInIds = $reservation->checkIns->pluck('check_ins_id')->all();
        $cancelIds = $reservation->cancellationRequests->pluck('cancellation_requests_id')->all();

        $auditLogs = AuditLog::with('user')
            ->where(function (Builder $q) use ($reservation, $paymentIds, $checkInIds, $cancelIds): void {
                $q->where(fn (Builder $s) => $s->where('entity_type', 'reservation')->where('entity_id', $reservation->reservations_id));
                if ($paymentIds) {
                    $q->orWhere(fn (Builder $s) => $s->where('entity_type', 'payment')->whereIn('entity_id', $paymentIds));
                }
                if ($checkInIds) {
                    $q->orWhere(fn (Builder $s) => $s->where('entity_type', 'check_in')->whereIn('entity_id', $checkInIds));
                }
                if ($cancelIds) {
                    $q->orWhere(fn (Builder $s) => $s->where('entity_type', 'cancellation_request')->whereIn('entity_id', $cancelIds));
                }
            })
            ->orderByDesc('created_at')
            ->orderByDesc('audit_logs_id')
            ->limit(15)
            ->get();

        return view('admin.reservation-show', [
            'selected' => $reservation,
            'outletId' => $outletId,
            'qrDataUri' => $qrDataUri,
            'eventTotal' => $eventTotal,
            'auditLogs' => $auditLogs,
        ]);
    }
}
