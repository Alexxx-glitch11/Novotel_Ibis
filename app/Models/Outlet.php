<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
    // Outlet utama hotel: selalu tampil paling atas di semua daftar outlet.
    public const PRIMARY_CODE = 'novotel';

    protected $table = 'outlets';

    protected $primaryKey = 'outlets_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    // Urutan: outlet utama dulu, sisanya menurut nama.
    public function scopeOrdered(Builder $query): Builder
    {
        return $query
            ->orderByRaw('CASE WHEN code = ? THEN 0 ELSE 1 END', [self::PRIMARY_CODE])
            ->orderBy('name');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'outlets_id', 'outlets_id');
    }

    public function areas(): HasMany
    {
        return $this->hasMany(Area::class, 'outlets_id', 'outlets_id');
    }

    public function tables(): HasMany
    {
        return $this->hasMany(Table::class, 'outlets_id', 'outlets_id');
    }

    public function menuCategories(): HasMany
    {
        return $this->hasMany(MenuCategory::class, 'outlets_id', 'outlets_id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'outlets_id', 'outlets_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'outlets_id', 'outlets_id');
    }

    public function reservationHolds(): HasMany
    {
        return $this->hasMany(ReservationHold::class, 'outlets_id', 'outlets_id');
    }

    public function taxServiceRates(): HasMany
    {
        return $this->hasMany(TaxServiceRate::class, 'outlets_id', 'outlets_id');
    }

    public function contents(): HasMany
    {
        return $this->hasMany(OutletContent::class, 'outlets_id', 'outlets_id');
    }
}
