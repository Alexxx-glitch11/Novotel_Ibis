<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckIn extends Model
{
    protected $table = 'check_ins';

    protected $primaryKey = 'check_ins_id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'reservations_id',
        'users_id_checked_in_by',
        'guest_count_arrived',
        'cumulative_guest_count',
        'checked_in_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'guest_count_arrived' => 'integer',
            'cumulative_guest_count' => 'integer',
            'checked_in_at' => 'datetime',
        ];
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class, 'reservations_id', 'reservations_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id_checked_in_by', 'users_id');
    }
}
