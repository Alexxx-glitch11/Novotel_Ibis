<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\OutletContent;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(Request $request): View
    {
        $outlets = Outlet::ordered()->get();
        $filters = $request->validate([
            'outlet' => ['nullable', 'regex:/^(all|[0-9]+)$/'],
            'type' => ['nullable', Rule::in(['all', 'promotion', 'event'])],
            'status' => ['nullable', Rule::in(['all', 'active', 'scheduled', 'draft', 'ended', 'inactive'])],
        ]);
        $query = OutletContent::with('outlet')->orderByDesc('updated_at')->orderByDesc('outlet_contents_id');
        if (($filters['outlet'] ?? 'all') !== 'all') $query->where('outlets_id', (int) $filters['outlet']);
        if (($filters['type'] ?? 'all') !== 'all') $query->where('content_type', $filters['type']);
        match ($filters['status'] ?? 'all') {
            'draft' => $query->where('status', 'draft'),
            'inactive' => $query->where('status', 'inactive'),
            'scheduled' => $query->where('status', 'published')->whereDate('starts_on', '>', now()->toDateString()),
            'ended' => $query->where('status', 'published')->whereDate('ends_on', '<', now()->toDateString()),
            'active' => $query->where('status', 'published')->where(fn ($q) => $q->whereNull('starts_on')->orWhereDate('starts_on', '<=', now()->toDateString()))->where(fn ($q) => $q->whereNull('ends_on')->orWhereDate('ends_on', '>=', now()->toDateString())),
            default => null,
        };
        $contents = $query->paginate(8)->withQueryString();
        $selected = $request->integer('selected') ? OutletContent::with('outlet')->find($request->integer('selected')) : $contents->first();
        $editing = $request->integer('edit') ? OutletContent::find($request->integer('edit')) : null;

        return view('admin.content', compact('outlets', 'filters', 'contents', 'selected', 'editing'));
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $data['users_id_created_by'] = $request->user()->users_id;
        if ($request->hasFile('image')) $data['image_path'] = $request->file('image')->store('outlet-content', 'public');
        unset($data['image']);
        $content = DB::transaction(function () use ($data, $request, $audit): OutletContent {
            $content = OutletContent::create($data);
            $audit->record($request, 'admin_outlet_content_created', 'outlet_content', $content->outlet_contents_id, null, $this->snapshot($content->load('outlet')));
            return $content;
        });

        return redirect()->route('admin.content.index', ['selected' => $content->outlet_contents_id])->with('success', 'Konten berhasil ditambahkan.');
    }

    public function update(Request $request, OutletContent $outletContent, AdminAuditLogger $audit): RedirectResponse
    {
        $old = $this->snapshot($outletContent->load('outlet'));
        $data = $this->validated($request);
        if ($request->hasFile('image')) $data['image_path'] = $request->file('image')->store('outlet-content', 'public');
        unset($data['image']);
        DB::transaction(function () use ($outletContent, $data, $request, $audit, $old): void {
            $outletContent->update($data);
            $audit->record($request, 'admin_outlet_content_updated', 'outlet_content', $outletContent->outlet_contents_id, $old, $this->snapshot($outletContent->fresh()->load('outlet')));
        });

        return redirect()->route('admin.content.index', ['selected' => $outletContent->outlet_contents_id])->with('success', 'Konten berhasil diperbarui.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'outlets_id' => ['required', 'integer', 'exists:outlets,outlets_id'],
            'content_type' => ['required', Rule::in(['promotion', 'event'])],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', Rule::when($request->filled('starts_on'), ['after_or_equal:starts_on'])],
            'status' => ['required', Rule::in(['draft', 'published', 'inactive'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        unset($data['image']);

        return $data;
    }

    private function snapshot(OutletContent $content): array
    {
        return ['outlet' => $content->outlet?->name, 'content_type' => $content->content_type, 'title' => $content->title, 'description' => $content->description, 'starts_on' => $content->starts_on?->toDateString(), 'ends_on' => $content->ends_on?->toDateString(), 'status' => $content->status, 'image_path' => $content->image_path];
    }
}
