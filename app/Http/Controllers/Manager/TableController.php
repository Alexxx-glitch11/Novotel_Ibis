<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TableController extends Controller
{
    public function index(Request $request)
    {
        // Semua outlet (termasuk yang nonaktif), Novotel paling atas.
        $outlets = Outlet::ordered()->get();
        $outlet  = $outlets->firstWhere('outlets_id', (int) $request->query('outlet')) ?? $outlets->first();

        abort_if(! $outlet, 404, __('manager.no_outlet'));

        $areas = $outlet->areas()->orderBy('name')->get();
        $area  = $areas->firstWhere('areas_id', (int) $request->query('area')) ?? $areas->first();

        $tables = $area ? $area->tables()->orderBy('table_code')->get() : collect();

        return view('manager.tables.index', compact('outlets', 'outlet', 'areas', 'area', 'tables'));
    }

    public function store(Request $request): RedirectResponse
    {
        $table = Table::create($this->validated($request));

        return $this->backTo($table, __('manager.tbl_added'));
    }

    public function update(Request $request, Table $table): RedirectResponse
    {
        $table->update($this->validated($request, $table));

        return $this->backTo($table, __('manager.tbl_updated'));
    }

    public function destroy(Table $table): RedirectResponse
    {
        // Meja yang sudah punya riwayat reservasi tidak boleh dihapus (jejak data)
        if ($table->reservations()->exists() || $table->reservationHolds()->exists()) {
            return $this->backTo($table, null, __('manager.tbl_has_history'));
        }

        $redirect = $this->backTo($table, __('manager.tbl_deleted'));
        $table->delete();

        return $redirect;
    }

    private function validated(Request $request, ?Table $table = null): array
    {
        $outletId = (int) $request->input('outlets_id');

        $data = $request->validate([
            'outlets_id'   => ['required', 'integer', 'exists:outlets,outlets_id'],
            'areas_id'     => ['required', 'integer', Rule::exists('areas', 'areas_id')->where('outlets_id', $outletId)],
            'table_code'   => [
                'required', 'string', 'max:20',
                Rule::unique('tables', 'table_code')->where('outlets_id', $outletId)->ignore($table?->tables_id, 'tables_id'),
            ],
            'capacity'     => ['required', 'integer', 'min:1', 'max:50'],
            'min_capacity' => ['required', 'integer', 'min:1', 'lte:capacity'],
            'position_x'   => ['required', 'numeric', 'between:0,100'],
            'position_y'   => ['required', 'numeric', 'between:0,100'],
        ], [
            'table_code.unique' => __('manager.v_code_unique'),
            'min_capacity.lte'  => __('manager.v_min_lte'),
            'areas_id.exists'   => __('manager.v_area_exists'),
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function backTo(Table $table, ?string $success = null, ?string $error = null): RedirectResponse
    {
        $redirect = redirect()->route('manager.tables.index', [
            'outlet' => $table->outlets_id,
            'area'   => $table->areas_id,
        ]);

        return $success ? $redirect->with('success', $success) : $redirect->with('error', $error);
    }
}