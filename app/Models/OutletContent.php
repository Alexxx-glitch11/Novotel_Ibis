<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OutletContent extends Model
{
    protected $table = 'outlet_contents';

    protected $primaryKey = 'outlet_contents_id';

    protected $fillable = [
        'outlets_id',
        'menu_items_id',
        'users_id_created_by',
        'content_type',
        'title',
        'description',
        'image_path',
        'starts_on',
        'ends_on',
        'status',
        'requires_table',
        'requires_menu',
        'price_per_pax',
    ];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'requires_table' => 'boolean',
            'requires_menu' => 'boolean',
            'price_per_pax' => 'decimal:2',
        ];
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'outlets_id', 'outlets_id');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'menu_items_id', 'menu_items_id');
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

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? route('outlet-content.image', [
                'outletContent' => $this,
                'v' => $this->updated_at?->timestamp ?? 1,
            ])
            : null;
    }

    public function syncPromotionMenuItem(?string $previousTitle = null): void
    {
        if ($this->content_type !== 'promotion') {
            if ($this->menu_items_id) {
                MenuItem::where('menu_items_id', $this->menu_items_id)
                    ->update(['is_available' => false]);
            }

            return;
        }

        $category = MenuCategory::query()
            ->where('outlets_id', $this->outlets_id)
            ->whereRaw('LOWER(name) = ?', ['promosi'])
            ->first();

        if (! $category) {
            $category = MenuCategory::create([
                'outlets_id' => $this->outlets_id,
                'name' => 'Promosi',
                'is_active' => true,
            ]);
        } elseif (! $category->is_active) {
            $category->update(['is_active' => true]);
        }

        $menuImagePath = null;
        if ($this->image_path && Storage::disk('public')->exists($this->image_path)) {
            $bytes = Storage::disk('public')->get($this->image_path);
            if ($bytes !== null) {
                $filename = basename($this->image_path);
                if (Storage::disk('menu_uploads')->put($filename, $bytes)) {
                    $menuImagePath = 'uploads/menu-items/' . $filename;
                }
            }
        }

        $menuItem = $this->menu_items_id
            ? MenuItem::where('outlets_id', $this->outlets_id)->find($this->menu_items_id)
            : null;

        if (! $menuItem) {
            $titles = array_values(array_filter(array_unique([
                $this->title,
                mb_substr((string) $this->title, 0, 100),
                $previousTitle,
                $previousTitle !== null ? mb_substr($previousTitle, 0, 100) : null,
            ])));

            if ($titles !== []) {
                $menuItem = MenuItem::query()
                    ->where('outlets_id', $this->outlets_id)
                    ->where('menu_categories_id', $category->menu_categories_id)
                    ->whereIn('name', $titles)
                    ->first();
            }
        }

        $isAvailable = $this->status === 'published'
            && ($this->ends_on === null || $this->ends_on->toDateString() >= now()->toDateString());

        $attributes = [
            'outlets_id' => $this->outlets_id,
            'menu_categories_id' => $category->menu_categories_id,
            'name' => mb_substr((string) $this->title, 0, 100),
            'description' => $this->description,
            'price' => (float) $this->price_per_pax,
            'image_path' => $menuImagePath ?? $menuItem?->image_path,
            'is_available' => $isAvailable,
        ];

        if ($menuItem) {
            $menuItem->update($attributes);
        } else {
            $menuItem = MenuItem::create($attributes);
        }

        if ((int) $this->menu_items_id !== (int) $menuItem->menu_items_id) {
            $this->forceFill(['menu_items_id' => $menuItem->menu_items_id])->saveQuietly();
        }
    }
}
