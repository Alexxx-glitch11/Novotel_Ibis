<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    protected $table = 'areas';

    protected $primaryKey = 'areas_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'outlets_id',
        'name',
        'description',
        'floor_plan_path',
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

    public function tables(): HasMany
    {
        return $this->hasMany(Table::class, 'areas_id', 'areas_id');
    }

    public function getFloorPlanUrlAttribute(): ?string
    {
        return $this->floor_plan_path
            ? asset('storage/' . ltrim($this->floor_plan_path, '/'))
            : null;
    }
}
