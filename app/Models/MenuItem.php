<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItem extends Model
{
    protected $table = 'menu_items';

    protected $primaryKey = 'menu_items_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'outlets_id',
        'menu_categories_id',
        'name',
        'description',
        'price',
        'image_path',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_available' => 'boolean',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlets_id', 'outlets_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'menu_categories_id', 'menu_categories_id');
    }

    public function reservationItems(): HasMany
    {
        return $this->hasMany(ReservationItem::class, 'menu_items_id', 'menu_items_id');
    }
}
