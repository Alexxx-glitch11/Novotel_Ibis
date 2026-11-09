<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Reservation extends Model
{
    protected $table = 'reservations';

    protected $primaryKey = 'reservations_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'reservation_code',
        'outlets_id',
        'users_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'guest_count',
        'reservation_date',
        'start_time',
        'end_time',
        'reservation_status',
        'subtotal',
        'service_rate_percent',
        'service_amount',
        'tax_rate_percent',
        'tax_amount',
        'grand_total',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'guest_count' => 'integer',
            'reservation_date' => 'date',
            'subtotal' => 'decimal:2',
            'service_rate_percent' => 'decimal:2',
            'service_amount' => 'decimal:2',
            'tax_rate_percent' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlets_id', 'outlets_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id', 'users_id');
    }

    public function tables(): BelongsToMany
    {
        return $this->belongsToMany(Table::class, 'reservation_tables', 'reservations_id', 'tables_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class, 'reservations_id', 'reservations_id');
    }

    public function qrToken(): HasOne
    {
        return $this->hasOne(ReservationQrToken::class, 'reservations_id', 'reservations_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'reservations_id', 'reservations_id');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class, 'reservations_id', 'reservations_id')->latestOfMany('payments_id');
    }

    public function checkIns(): HasMany
    {
        return $this->hasMany(CheckIn::class, 'reservations_id', 'reservations_id');
    }

    public function cancellationRequests(): HasMany
    {
        return $this->hasMany(CancellationRequest::class, 'reservations_id', 'reservations_id');
    }
}
