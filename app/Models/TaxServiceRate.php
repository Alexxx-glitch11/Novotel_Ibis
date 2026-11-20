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

    /**
     * Tarif yang berlaku untuk sebuah outlet pada tanggal tertentu.
     * Jika ada beberapa baris dengan tanggal berlaku sama, baris terbaru (id terbesar) yang dipakai.
     */
    public static function activeFor(int $outletId, ?string $date = null): ?self
    {
        $date ??= now(config('reservation.timezone', 'Asia/Jakarta'))->toDateString();

        return static::query()
            ->where('outlets_id', $outletId)
            ->whereDate('effective_from', '<=', $date)
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $date))
            ->orderByDesc('effective_from')
            ->orderByDesc('tax_service_rates_id')
            ->first();
    }

    /** Tarif paling baru (termasuk yang dijadwalkan di masa depan). */
    public static function latestFor(int $outletId): ?self
    {
        return static::query()
            ->where('outlets_id', $outletId)
            ->orderByDesc('effective_from')
            ->orderByDesc('tax_service_rates_id')
            ->first();
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
