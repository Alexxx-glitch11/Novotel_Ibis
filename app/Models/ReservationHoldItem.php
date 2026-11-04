<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationHoldItem extends Model
{
    protected $table = 'reservation_hold_items';

    protected $primaryKey = 'reservation_hold_items_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'reservation_holds_id',
        'tables_id',
    ];

    public function hold(): BelongsTo
    {
        return $this->belongsTo(ReservationHold::class, 'reservation_holds_id', 'reservation_holds_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'tables_id', 'tables_id');
    }
}
