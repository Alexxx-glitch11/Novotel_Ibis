<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationQrToken extends Model
{
    protected $table = 'reservation_qr_tokens';

    protected $primaryKey = 'reservation_qr_tokens_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'reservations_id',
        'token',
        'valid_from',
        'valid_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'datetime',
            'valid_until' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservations_id', 'reservations_id');
    }
}
