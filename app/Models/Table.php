<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Table extends Model
{
    protected $table = 'tables';

    protected $primaryKey = 'tables_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'outlets_id',
        'areas_id',
        'table_code',
        'capacity',
        'min_capacity',
        'position_x',
        'position_y',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'min_capacity' => 'integer',
            'position_x' => 'decimal:2',
            'position_y' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlets_id', 'outlets_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class, 'areas_id', 'areas_id');
    }

    public function reservations(): BelongsToMany
    {
        return $this->belongsToMany(Reservation::class, 'reservation_tables', 'tables_id', 'reservations_id');
    }

    public function reservationHolds(): BelongsToMany
    {
        return $this->belongsToMany(ReservationHold::class, 'reservation_hold_items', 'tables_id', 'reservation_holds_id');
    }
}
