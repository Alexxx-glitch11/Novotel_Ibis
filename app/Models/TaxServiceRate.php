<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxServiceRate extends Model
{
    protected $table = 'tax_service_rates';

    protected $primaryKey = 'tax_service_rates_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'outlets_id',
        'tax_rate',
        'service_rate',
        'effective_from',
        'effective_until',
        'users_id_updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'service_rate' => 'decimal:2',
            'effective_from' => 'date',
            'effective_until' => 'date',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlets_id', 'outlets_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id_updated_by', 'users_id');
    }
}
