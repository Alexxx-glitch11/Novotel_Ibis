<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', Rule::when($request->filled('from'), ['after_or_equal:from'])],
            'role' => ['nullable', 'string', 'max:50'],
            'action' => ['nullable', 'string', 'max:50'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $query = AuditLog::query()->with('user.roles')->orderByDesc('created_at')->orderByDesc('audit_logs_id');
        if (! empty($filters['from'])) $query->whereDate('created_at', '>=', $filters['from']);
        if (! empty($filters['to'])) $query->whereDate('created_at', '<=', $filters['to']);
        if (! empty($filters['role'])) $query->whereHas('user.roles', fn (Builder $q) => $q->where('name', $filters['role'])->where('guard_name', 'web'));
        if (! empty($filters['action'])) $query->where('action', $filters['action']);
        if (! empty($filters['q'])) {
            $term = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $query->where(function (Builder $q) use ($term): void {
                $q->where('entity_type', 'like', $term)->orWhere('entity_id', 'like', $term)->orWhere('action', 'like', $term)
                    ->orWhere('old_values', 'like', $term)->orWhere('new_values', 'like', $term)
                    ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', $term)->orWhere('email', 'like', $term));
            });
        }

        return view('admin.audit', [
            'filters' => $filters,
            'logs' => $query->paginate(8)->withQueryString(),
            'roles' => User::query()->whereHas('roles')->with('roles')->get()->pluck('roles')->flatten()->unique('name')->sortBy('name')->values(),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
