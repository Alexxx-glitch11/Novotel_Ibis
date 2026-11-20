<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CancellationRequest;
use App\Models\CheckIn;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\OutletContent;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Table;
use App\Models\TaxServiceRate;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class OutletAccess
{
    public function isGlobal(?User $user): bool
    {
        return $user?->hasRole('admin') ?? false;
    }

    public function assertCanAccess(?User $user, ?int $outletId): void
    {
        if ($this->isGlobal($user)) {
            return;
        }

        if (! $user || ! $outletId || ! in_array($outletId, $this->outletIds($user), true)) {
            throw new AccessDeniedHttpException('Akun ini tidak memiliki akses ke outlet tersebut.');
        }
    }

    /** @return list<int> */
    public function outletIds(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $ids = $user->relationLoaded('outlets')
            ? $user->outlets->modelKeys()
            : $user->outlets()->pluck('outlets.outlets_id')->all();
        if ($user->outlets_id) {
            $ids[] = (int) $user->outlets_id;
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /**
     * Limit an outlet-owned query to the signed-in user's assignment.
     * A null outlet assignment grants no outlet data; only Admin is global.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     * @param Builder<TModel> $query
     * @return Builder<TModel>
     */
    public function scope(Builder $query, ?User $user, ?string $relation = null): Builder
    {
        if ($this->isGlobal($user)) {
            return $query;
        }

        $outletIds = $this->outletIds($user);
        if (! $outletIds) {
            return $query->whereRaw('1 = 0');
        }

        if ($relation) {
            return $query->whereHas($relation, fn (Builder $related) => $related->whereIn('outlets_id', $outletIds));
        }

        return $query->whereIn('outlets_id', $outletIds);
    }

    /** @param Builder<AuditLog> $query @return Builder<AuditLog> */
    public function scopeAudit(Builder $query, ?User $user): Builder
    {
        if ($this->isGlobal($user)) {
            return $query;
        }

        $outletIds = $this->outletIds($user);
        if (! $outletIds) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $visible) use ($outletIds): void {
            $visible->where(function (Builder $q) use ($outletIds): void {
                $q->where('entity_type', 'reservation')->whereIn('entity_id', Reservation::select('reservations_id')->whereIn('outlets_id', $outletIds));
            })->orWhere(function (Builder $q) use ($outletIds): void {
                $q->where('entity_type', 'payment')->whereIn('entity_id', Payment::select('payments_id')->whereHas('reservation', fn (Builder $r) => $r->whereIn('outlets_id', $outletIds)));
            })->orWhere(function (Builder $q) use ($outletIds): void {
                $q->where('entity_type', 'cancellation_request')->whereIn('entity_id', CancellationRequest::select('cancellation_requests_id')->whereHas('reservation', fn (Builder $r) => $r->whereIn('outlets_id', $outletIds)));
            })->orWhere(function (Builder $q) use ($outletIds): void {
                $q->where('entity_type', 'check_in')->whereIn('entity_id', CheckIn::select('check_ins_id')->whereHas('reservation', fn (Builder $r) => $r->whereIn('outlets_id', $outletIds)));
            })->orWhere(function (Builder $q) use ($outletIds): void {
                $q->where('entity_type', 'tax_service_rate')->whereIn('entity_id', TaxServiceRate::select('tax_service_rates_id')->whereIn('outlets_id', $outletIds));
            })->orWhere(function (Builder $q) use ($outletIds): void {
                $q->where('entity_type', 'outlet_content')->whereIn('entity_id', OutletContent::select('outlet_contents_id')->whereIn('outlets_id', $outletIds));
            })->orWhere(function (Builder $q) use ($outletIds): void {
                $q->where('entity_type', 'table')->whereIn('entity_id', Table::select('tables_id')->whereIn('outlets_id', $outletIds));
            })->orWhere(function (Builder $q) use ($outletIds): void {
                $q->where('entity_type', 'menu_item')->whereIn('entity_id', MenuItem::select('menu_items_id')->whereIn('outlets_id', $outletIds));
            })->orWhere(function (Builder $q) use ($outletIds): void {
                $q->where('entity_type', 'menu_category')->whereIn('entity_id', MenuCategory::select('menu_categories_id')->whereIn('outlets_id', $outletIds));
            })->orWhere(function (Builder $q) use ($outletIds): void {
                $q->where('entity_type', 'outlet')->whereIn('entity_id', $outletIds);
            });
        });
    }
}
