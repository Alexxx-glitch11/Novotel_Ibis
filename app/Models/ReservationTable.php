<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationTable extends Model
{
    protected $table = 'reservation_tables';

    protected $primaryKey = 'reservation_tables_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'reservations_id',
        'tables_id',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservations_id', 'reservations_id');
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'tables_id', 'tables_id');
    }
}
