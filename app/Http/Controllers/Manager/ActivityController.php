<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CancellationRequest;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityController extends Controller
{
    private const RANGES = ['7d', '30d', 'all'];

    private const TYPES = ['all', 'reservation', 'payment', 'cancellation', 'rates', 'content', 'other'];

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'range' => ['nullable', Rule::in(self::RANGES)],
            'activity' => ['nullable', Rule::in(self::TYPES)],
            'actor' => ['nullable', 'regex:/^(all|[0-9]+)$/'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $filters = [
            'range' => $filters['range'] ?? '7d',
            'activity' => $filters['activity'] ?? 'all',
            'actor' => $filters['actor'] ?? 'all',
            'q' => trim($filters['q'] ?? ''),
        ];

        $period = $this->periodQuery($filters['range']);
        $filtered = $this->applyFilters(clone $period, $filters);

        $summary = [
            'total' => (clone $period)->count(),
            'cancellations' => (clone $period)->whereIn('action', ['cancellation_approved', 'cancellation_rejected'])->count(),
            'rates' => (clone $period)->whereIn('action', ['tax_updated', 'service_updated', 'rates_updated'])->count(),
            'actors' => (clone $period)->whereNotNull('users_id')->distinct()->count('users_id'),
        ];

        $activities = $filtered
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('audit_logs_id')
            ->paginate(6)
            ->withQueryString();

        $this->decorate($activities->getCollection());
        $actors = User::query()->whereHas('auditLogs')->orderBy('name')->get(['users_id', 'name']);

        return view('manager.activities.index', compact('filters', 'summary', 'activities', 'actors'));
    }

    private function periodQuery(string $range): Builder
    {
        $query = AuditLog::query();

        if ($range === '7d') {
            $query->where('created_at', '>=', now()->subDays(6)->startOfDay());
        } elseif ($range === '30d') {
            $query->where('created_at', '>=', now()->subDays(29)->startOfDay());
        }

        return $query;
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        $query->when($filters['actor'] !== 'all', fn (Builder $q) => $q->where('users_id', (int) $filters['actor']));

        if ($filters['activity'] !== 'all') {
            match ($filters['activity']) {
                'reservation' => $query->where('entity_type', 'reservation'),
                'payment' => $query->where('entity_type', 'payment'),
                'cancellation' => $query->where('action', 'like', 'cancellation_%'),
                'rates' => $query->whereIn('action', ['tax_updated', 'service_updated', 'rates_updated']),
                'content' => $query->where('entity_type', 'outlet_content'),
                'other' => $query->whereNotIn('entity_type', ['reservation', 'payment', 'outlet_content'])
                    ->whereNotIn('action', ['cancellation_approved', 'cancellation_rejected', 'tax_updated', 'service_updated', 'rates_updated']),
                default => null,
            };
        }

        if ($filters['q'] !== '') {
            $term = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $query->where(function (Builder $q) use ($filters, $term): void {
                $q->where('action', 'like', $term)
                    ->orWhere('entity_type', 'like', $term)
                    ->orWhere('entity_id', 'like', $term)
                    ->orWhereHas('user', fn (Builder $user) => $user->where('name', 'like', $term)->orWhere('email', 'like', $term));

                if (str_starts_with(strtoupper($filters['q']), 'RSV-')) {
                    $codes = Reservation::query()->select('reservations_id')->where('reservation_code', 'like', $term);
                    $cancellationIds = CancellationRequest::query()->select('cancellation_requests_id')->whereHas(
                        'reservation', fn (Builder $reservation) => $reservation->where('reservation_code', 'like', $term)
                    );
                    $paymentIds = Payment::query()->select('payments_id')->whereHas(
                        'reservation', fn (Builder $reservation) => $reservation->where('reservation_code', 'like', $term)
                    );
                    $q->orWhere(fn (Builder $byCode) => $byCode
                        ->where('entity_type', 'reservation')->whereIn('entity_id', $codes))
                        ->orWhere(fn (Builder $byCancellation) => $byCancellation
                            ->where('entity_type', 'cancellation_request')->whereIn('entity_id', $cancellationIds))
                        ->orWhere(fn (Builder $byPayment) => $byPayment
                            ->where('entity_type', 'payment')->whereIn('entity_id', $paymentIds));
                }
            });
        }

        return $query;
    }

    private function decorate($logs): void
    {
        $reservationIds = $logs->where('entity_type', 'reservation')->pluck('entity_id')->unique();
        $cancellationIds = $logs->where('entity_type', 'cancellation_request')->pluck('entity_id')->unique();

        $reservationCodes = $reservationIds->isEmpty()
            ? collect()
            : Reservation::query()->whereIn('reservations_id', $reservationIds)->pluck('reservation_code', 'reservations_id');
        $cancellationCodes = $cancellationIds->isEmpty()
            ? collect()
            : CancellationRequest::query()->with('reservation')->whereIn('cancellation_requests_id', $cancellationIds)
                ->get()->mapWithKeys(fn (CancellationRequest $c) => [$c->cancellation_requests_id => $c->reservation?->reservation_code]);

        foreach ($logs as $log) {
            $actionKey = 'manager.activity_action_' . Str::snake((string) $log->action);
            $log->setAttribute('actor_label', $log->user?->name ?? __('manager.activity_system'));
            $log->setAttribute('activity_label', Lang::has($actionKey) ? __($actionKey) : Str::headline((string) $log->action));
            $log->setAttribute('reference_label', $this->reference($log, $reservationCodes, $cancellationCodes));
            $log->setAttribute('change_summary', $this->changeSummary($log));
            $log->setAttribute('time_label', Carbon::parse($log->created_at)->format('H.i'));
        }
    }

    private function reference(AuditLog $log, $reservationCodes, $cancellationCodes): string
    {
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];

        return match ($log->entity_type) {
            'reservation' => (string) ($reservationCodes[$log->entity_id] ?? __('manager.activity_reference_id', ['id' => $log->entity_id])),
            'cancellation_request' => (string) ($cancellationCodes[$log->entity_id] ?? __('manager.activity_reference_id', ['id' => $log->entity_id])),
            'payment' => (string) ($new['reservation_code'] ?? __('manager.activity_reference_id', ['id' => $log->entity_id])),
            'outlet_content' => (string) ($new['title'] ?? $old['title'] ?? __('manager.activity_reference_id', ['id' => $log->entity_id])),
            'tax_service_rate' => (string) ($new['outlet_name'] ?? $old['outlet_name'] ?? __('manager.activity_reference_id', ['id' => $log->entity_id])),
            'outlet' => (string) ($new['name'] ?? $old['name'] ?? __('manager.activity_reference_id', ['id' => $log->entity_id])),
            default => (string) __('manager.activity_reference_id', ['id' => $log->entity_id]),
        };
    }

    private function changeSummary(AuditLog $log): string
    {
        $old = $log->old_values ?? [];
        $new = $log->new_values ?? [];

        $reason = $new['decision_note'] ?? $new['rejection_reason'] ?? null;
        if (is_string($reason) && trim($reason) !== '') {
            return __('manager.activity_reason', ['reason' => Str::limit($reason, 100)]);
        }

        if (isset($new['field'])) {
            $valueKey = $new['field'] === 'Pajak' ? 'tax_rate' : 'service_rate';
            return $new['field'] . ' ' . ($old[$valueKey] ?? '—') . ' → ' . ($new[$valueKey] ?? '—');
        }

        $meta = ['outlet_name', 'operator', 'field', 'decision_note', 'rejection_reason', 'reservation_code', 'customer_name', 'customer_phone', 'customer_email', 'guest_count'];
        $parts = [];
        foreach (array_diff(array_keys($new), $meta) as $key) {
            if (! is_scalar($new[$key]) || (array_key_exists($key, $old) && $old[$key] === $new[$key])) {
                continue;
            }

            $fieldKey = 'manager.activity_field_' . $key;
            $label = Lang::has($fieldKey) ? __($fieldKey) : Str::headline($key);
            $before = array_key_exists($key, $old) ? $this->displayValue($key, $old[$key]) : '—';
            $after = $this->displayValue($key, $new[$key]);
            $parts[] = $label . ': ' . (string) $before . ' → ' . $after;
        }

        return $parts ? implode('; ', array_slice($parts, 0, 2)) : __('manager.activity_recorded');
    }

    private function displayValue(string $key, mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? __('manager.activity_yes') : __('manager.activity_no');
        }

        if (! is_scalar($value)) {
            return '—';
        }

        $lookup = match ($key) {
            'reservation_status' => 'manager.res_status_' . $value,
            'payment_status' => 'manager.pay_status_' . $value,
            'content_type' => 'manager.content_type_' . $value,
            'status' => 'manager.event_visibility_' . $value,
            default => null,
        };

        return $lookup && Lang::has($lookup) ? __($lookup) : (string) $value;
    }
}
