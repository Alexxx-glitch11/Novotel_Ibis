<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OutletContent extends Model
{
    protected $table = 'outlet_contents';

    protected $primaryKey = 'outlet_contents_id';

    protected $fillable = [
        'outlets_id',
        'users_id_created_by',
        'content_type',
        'title',
        'description',
        'image_path',
        'starts_on',
        'ends_on',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlets_id', 'outlets_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'users_id_created_by', 'users_id');
    }

    public function scopePublishedOn(Builder $query, ?string $date = null): Builder
    {
        $date ??= now()->toDateString();

        return $query
            ->where('status', 'published')
            ->where(fn (Builder $q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $date))
            ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $date));
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->status !== 'published') {
            return $this->status;
        }

        $today = now()->toDateString();

        if ($this->starts_on?->toDateString() > $today) {
            return 'scheduled';
        }

        if ($this->ends_on?->toDateString() < $today) {
            return 'ended';
        }

        return 'active';
    }
}
