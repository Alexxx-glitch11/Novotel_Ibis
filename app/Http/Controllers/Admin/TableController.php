<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Outlet;
use App\Models\Table;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TableController extends Controller
{
    public function index(Request $request): View
    {
        $outlets = Outlet::ordered()->get();
        $outlet = $outlets->firstWhere('outlets_id', $request->integer('outlet')) ?? $outlets->first();
        $areas = $outlet ? $outlet->areas()->orderBy('name')->get() : collect();
        $area = $areas->firstWhere('areas_id', $request->integer('area')) ?? $areas->first();
        $tables = $area ? $area->tables()->with('area')->orderBy('table_code')->get() : collect();

        return view('admin.tables', compact('outlets', 'outlet', 'areas', 'area', 'tables'));
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $table = DB::transaction(function () use ($data, $request, $audit): Table {
            $table = Table::create($data);
            $audit->record($request, 'admin_table_created', 'table', $table->tables_id, null, $this->snapshot($table));
            return $table;
        });

        return redirect()->route('admin.tables.index', ['outlet' => $table->outlets_id, 'area' => $table->areas_id])->with('success', 'Meja berhasil ditambahkan.');
    }

    public function update(Request $request, Table $table, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request, $table);
        $old = $this->snapshot($table);
        $structural = ['outlets_id', 'areas_id', 'table_code', 'capacity', 'min_capacity'];
        $hasHistory = $table->reservations()->exists() || $table->reservationHolds()->exists();
        if ($hasHistory && collect($structural)->contains(fn ($key) => (string) $table->{$key} !== (string) $data[$key])) {
            return back()->withInput()->with('error', 'Meja memiliki riwayat reservasi atau hold; kode, area, outlet, dan kapasitasnya tidak dapat diubah. Posisi denah dan status tetap dapat dikelola.');
        }

        DB::transaction(function () use ($table, $data, $request, $audit, $old): void {
            $table->update($data);
            $audit->record($request, 'admin_table_updated', 'table', $table->tables_id, $old, $this->snapshot($table->fresh()));
        });

        return redirect()->route('admin.tables.index', ['outlet' => $table->outlets_id, 'area' => $table->areas_id])->with('success', 'Data meja berhasil diperbarui.');
    }

    private function validated(Request $request, ?Table $table = null): array
    {
        $outletId = (int) $request->input('outlets_id');

        return $request->validate([
            'outlets_id' => ['required', 'integer', 'exists:outlets,outlets_id'],
            'areas_id' => ['required', 'integer', Rule::exists('areas', 'areas_id')->where('outlets_id', $outletId)],
            'table_code' => ['required', 'string', 'max:20', Rule::unique('tables', 'table_code')->where('outlets_id', $outletId)->ignore($table?->tables_id, 'tables_id')],
            'capacity' => ['required', 'integer', 'min:1', 'max:50'],
            'min_capacity' => ['required', 'integer', 'min:1', 'lte:capacity'],
            'position_x' => ['required', 'numeric', 'between:0,100'],
            'position_y' => ['required', 'numeric', 'between:0,100'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }

    private function snapshot(Table $table): array
    {
        return $table->only(['outlets_id', 'areas_id', 'table_code', 'capacity', 'min_capacity', 'position_x', 'position_y', 'is_active']);
    }
}
