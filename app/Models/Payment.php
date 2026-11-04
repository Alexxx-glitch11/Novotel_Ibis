<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $table = 'payments';

    protected $primaryKey = 'payments_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'reservations_id',
        'payment_method',
        'bank_destination',
        'amount',
        'payment_status',
        'proof_file_path',
        'proof_uploaded_at',
        'users_id_verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'proof_uploaded_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservations_id', 'reservations_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id_verified_by', 'users_id');
    }
}
