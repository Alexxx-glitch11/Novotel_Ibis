<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationItem extends Model
{
    protected $table = 'reservation_items';

    protected $primaryKey = 'reservation_items_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'reservations_id',
        'menu_items_id',
        'item_name_snapshot',
        'unit_price_snapshot',
        'quantity',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'unit_price_snapshot' => 'decimal:2',
            'quantity' => 'integer',
            'line_total' => 'decimal:2',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservations_id', 'reservations_id');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_items_id', 'menu_items_id');
    }
}
