<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservationHold extends Model
{
    protected $table = 'reservation_holds';

    protected $primaryKey = 'reservation_holds_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'session_token_hash',
        'outlets_id',
        'reservation_date',
        'start_time',
        'end_time',
        'started_at',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'reservation_date' => 'date',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlets_id', 'outlets_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReservationHoldItem::class, 'reservation_holds_id', 'reservation_holds_id');
    }

    public function tables(): BelongsToMany
    {
        return $this->belongsToMany(Table::class, 'reservation_hold_items', 'reservation_holds_id', 'tables_id');
    }
}
