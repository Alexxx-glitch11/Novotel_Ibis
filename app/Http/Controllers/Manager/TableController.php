<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Outlet;
use App\Models\Table;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TableController extends Controller
{
    public function index(Request $request)
    {
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get();
        $outlet = $outlets->firstWhere('outlets_id', (int) $request->query('outlet')) ?? $outlets->first();

        abort_if(! $outlet, 404, 'Belum ada outlet.');

        $areas = $outlet->areas()->orderBy('name')->get();
        $area = $areas->firstWhere('areas_id', (int) $request->query('area')) ?? $areas->first();

        $tables = $area ? $area->tables()->orderBy('table_code')->get() : collect();

        return view('manager.tables.index', compact('outlets', 'outlet', 'areas', 'area', 'tables'));
    }

    public function store(Request $request): RedirectResponse
    {
        $table = Table::create($this->validated($request));
        $this->audit($request, $table, 'table_created', null, $table->only([
            'outlets_id', 'areas_id', 'table_code', 'capacity', 'min_capacity', 'position_x', 'position_y', 'is_active',
        ]));

        return $this->backTo($table, 'Meja berhasil ditambahkan.');
    }

    public function update(Request $request, Table $table): RedirectResponse
    {
        $oldValues = $table->only([
            'outlets_id', 'areas_id', 'table_code', 'capacity', 'min_capacity', 'position_x', 'position_y', 'is_active',
        ]);
        $table->update($this->validated($request, $table));
        $this->audit($request, $table, 'table_updated', $oldValues, $table->only(array_keys($oldValues)));

        return $this->backTo($table, 'Meja berhasil diperbarui.');
    }

    public function destroy(Request $request, Table $table): RedirectResponse
    {
        // Meja yang sudah punya riwayat reservasi tidak boleh dihapus (jejak data)
        if ($table->reservations()->exists() || $table->reservationHolds()->exists()) {
            return $this->backTo($table, null, 'Meja ini sudah punya riwayat reservasi, jadi tidak bisa dihapus. Nonaktifkan saja lewat tombol Ubah.');
        }

        $redirect = $this->backTo($table, 'Meja berhasil dihapus.');
        $this->audit($request, $table, 'table_deleted', $table->only([
            'outlets_id', 'areas_id', 'table_code', 'capacity', 'min_capacity', 'position_x', 'position_y', 'is_active',
        ]), null);
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
            'table_code.unique' => 'Kode meja sudah dipakai di outlet ini.',
            'min_capacity.lte'  => 'Kapasitas minimal tidak boleh lebih besar dari kapasitas.',
            'areas_id.exists'   => 'Area tidak sesuai dengan outlet.',
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

    private function audit(Request $request, Table $table, string $action, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'users_id' => $request->user()?->users_id,
            'action' => $action,
            'entity_type' => 'table',
            'entity_id' => $table->tables_id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
