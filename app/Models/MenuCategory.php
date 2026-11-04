<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuCategory extends Model
{
    protected $table = 'menu_categories';

    protected $primaryKey = 'menu_categories_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'outlets_id',
        'name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlets_id', 'outlets_id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'menu_categories_id', 'menu_categories_id');
    }
}
