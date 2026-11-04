<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outlet extends Model
{
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
}
