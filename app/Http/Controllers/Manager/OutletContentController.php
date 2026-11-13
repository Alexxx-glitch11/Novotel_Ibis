<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Outlet;
use App\Models\OutletContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OutletContentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'outlet' => ['nullable', 'regex:/^(all|[0-9]+)$/'],
            'type' => ['nullable', Rule::in(['all', 'promotion', 'event'])],
            'status' => ['nullable', Rule::in(['all', 'active', 'scheduled', 'draft', 'ended', 'inactive'])],
            'selected' => ['nullable', 'integer'],
            'edit' => ['nullable', 'integer'],
        ]);
        $filters = [
            'outlet' => $filters['outlet'] ?? 'all',
            'type' => $filters['type'] ?? 'all',
            'status' => $filters['status'] ?? 'all',
        ];

        $outlets = Outlet::ordered()->get();
        $query = OutletContent::query()->with('outlet')->orderByDesc('updated_at')->orderByDesc('outlet_contents_id');
        if ($filters['outlet'] !== 'all') {
            $query->where('outlets_id', (int) $filters['outlet']);
        }
        if ($filters['type'] !== 'all') {
            $query->where('content_type', $filters['type']);
        }
        $this->applyStatusFilter($query, $filters['status']);

        $contents = $query->paginate(8)->withQueryString();
        $selectedContent = isset($request->query()['selected'])
            ? OutletContent::with('outlet')->findOrFail((int) $request->query('selected'))
            : $contents->getCollection()->first();
        $editingContent = isset($request->query()['edit'])
            ? OutletContent::findOrFail((int) $request->query('edit'))
            : null;
        $formOpen = $request->boolean('create') || $editingContent !== null;

        return view('manager.events.index', compact(
            'outlets', 'filters', 'contents', 'selectedContent', 'editingContent', 'formOpen'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['users_id_created_by'] = $request->user()?->users_id;
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('outlet-content', 'public');
        }
        unset($data['image']);

        $content = OutletContent::create($data);
        $this->audit($request, 'outlet_content_created', $content, null, $this->snapshot($content));

        return redirect()->route('manager.events.index', [
            'outlet' => $content->outlets_id,
            'selected' => $content->outlet_contents_id,
        ])->with('success', __('manager.event_created'));
    }

    public function update(Request $request, OutletContent $outletContent): RedirectResponse
    {
        $oldValues = $this->snapshot($outletContent);
        $data = $this->validated($request);
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('outlet-content', 'public');
        }
        unset($data['image']);

        $outletContent->update($data);
        $this->audit($request, 'outlet_content_updated', $outletContent, $oldValues, $this->snapshot($outletContent));

        return redirect()->route('manager.events.index', [
            'outlet' => $outletContent->outlets_id,
            'selected' => $outletContent->outlet_contents_id,
        ])->with('success', __('manager.event_updated'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'outlets_id' => ['required', 'integer', 'exists:outlets,outlets_id'],
            'content_type' => ['required', Rule::in(['promotion', 'event'])],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', Rule::when($request->filled('starts_on'), ['after_or_equal:starts_on'])],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'ends_on.after_or_equal' => __('manager.event_date_order'),
            'image.mimes' => __('manager.event_image_format'),
            'image.max' => __('manager.event_image_size'),
        ]);
    }

    private function applyStatusFilter(Builder $query, string $status): void
    {
        $today = now()->toDateString();

        match ($status) {
            'draft' => $query->where('status', 'draft'),
            'inactive' => $query->where('status', 'inactive'),
            'scheduled' => $query->where('status', 'published')->whereDate('starts_on', '>', $today),
            'ended' => $query->where('status', 'published')->whereDate('ends_on', '<', $today),
            'active' => $query->where('status', 'published')
                ->where(fn (Builder $q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', $today))
                ->where(fn (Builder $q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', $today)),
            default => null,
        };
    }

    private function snapshot(OutletContent $content): array
    {
        return [
            'outlet_name' => $content->outlet?->name,
            'content_type' => $content->content_type,
            'title' => $content->title,
            'description' => $content->description,
            'starts_on' => $content->starts_on?->toDateString(),
            'ends_on' => $content->ends_on?->toDateString(),
            'status' => $content->status,
            'image_path' => $content->image_path,
        ];
    }

    private function audit(Request $request, string $action, OutletContent $content, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'users_id' => $request->user()?->users_id,
            'action' => $action,
            'entity_type' => 'outlet_content',
            'entity_id' => $content->outlet_contents_id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
