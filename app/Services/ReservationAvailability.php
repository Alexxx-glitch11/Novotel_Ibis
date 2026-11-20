<?php

namespace App\Services;

use App\Models\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReservationAvailability
{
    /**
     * Return active tables that can seat the party and have no overlapping
     * reservation or unexpired hold for the requested time slot.
     */
    public function availableTables(
        int $outletId,
        int $areaId,
        string $date,
        string $startTime,
        string $endTime,
        int $guestCount,
        ?int $ignoreReservationId = null,
    ): Collection {
        $reservedTableQuery = DB::table('reservation_tables')
            ->join('reservations', 'reservations.reservations_id', '=', 'reservation_tables.reservations_id')
            ->whereDate('reservations.reservation_date', $date)
            ->where('reservations.start_time', '<', $endTime)
            ->where('reservations.end_time', '>', $startTime);

        if ($ignoreReservationId !== null) {
            $reservedTableQuery->where('reservations.reservations_id', '<>', $ignoreReservationId);
        }

        $reservedTableIds = $reservedTableQuery
            ->whereIn('reservations.reservation_status', ['pending_payment', 'confirmed', 'partially_arrived', 'checked_in'])
            ->select('reservation_tables.tables_id');

        $heldTableIds = DB::table('reservation_hold_items')
            ->join('reservation_holds', 'reservation_holds.reservation_holds_id', '=', 'reservation_hold_items.reservation_holds_id')
            ->whereDate('reservation_holds.reservation_date', $date)
            ->where('reservation_holds.status', 'active')
            ->where('reservation_holds.expires_at', '>', now())
            ->where('reservation_holds.start_time', '<', $endTime)
            ->where('reservation_holds.end_time', '>', $startTime)
            ->select('reservation_hold_items.tables_id');

        return Table::query()
            ->where('outlets_id', $outletId)
            ->where('areas_id', $areaId)
            ->whereHas('area', fn ($query) => $query->where('outlets_id', $outletId)->where('is_active', true))
            ->where('is_active', true)
            ->where('min_capacity', '<=', $guestCount)
            ->where('capacity', '>=', $guestCount)
            ->whereNotIn('tables_id', $reservedTableIds)
            ->whereNotIn('tables_id', $heldTableIds)
            ->orderBy('table_code')
            ->get();
    }
}
