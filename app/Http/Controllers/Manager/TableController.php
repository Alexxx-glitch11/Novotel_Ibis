<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Table;
use App\Services\OutletAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TableController extends Controller
{
    public function index(Request $request)
    {
        // Semua outlet (termasuk yang nonaktif), Novotel paling atas.
        $outletQuery = Outlet::ordered();
        app(OutletAccess::class)->scope($outletQuery, $request->user());
        $outlets = $outletQuery->get();
        $outlet  = $outlets->firstWhere('outlets_id', (int) $request->query('outlet')) ?? $outlets->first();

        abort_if(! $outlet, 404, __('manager.no_outlet'));

        $areas = $outlet->areas()->orderBy('name')->get();
        $area  = $areas->firstWhere('areas_id', (int) $request->query('area')) ?? $areas->first();

        $tables = $area ? $area->tables()->orderBy('table_code')->get() : collect();

        return view('manager.tables.index', compact('outlets', 'outlet', 'areas', 'area', 'tables'));
    }

    public function store(Request $request): RedirectResponse
    {
        $outletId = (int) $request->input('outlets_id');
        app(OutletAccess::class)->assertCanAccess($request->user(), $outletId);

        $data = $request->validate([
            'outlets_id'   => ['required', 'integer', 'exists:outlets,outlets_id'],
            'areas_id'     => ['required', 'integer', Rule::exists('areas', 'areas_id')->where('outlets_id', $outletId)],
            'table_code'   => ['required', 'string', 'max:40'],
            'capacity'     => ['required', 'integer', 'min:1', 'max:50'],
            'min_capacity' => ['required', 'integer', 'min:1', 'lte:capacity'],
            'position_x'   => ['nullable', 'numeric', 'between:0,100'],
            'position_y'   => ['nullable', 'numeric', 'between:0,100'],
        ], [
            'min_capacity.lte' => __('manager.v_min_lte'),
            'areas_id.exists'  => __('manager.v_area_exists'),
        ]);

        $rawCode = trim($data['table_code']);
        $codes = $this->expandTableCodes($rawCode);

        if (count($codes) > 50) {
            return back()->withErrors(['table_code' => 'Maksimal 50 meja dalam sekali tambah.'])->withInput();
        }

        if (count($codes) === 1) {
            $exists = Table::where('outlets_id', $outletId)->where('table_code', $codes[0])->exists();
            if ($exists) {
                return back()->withErrors(['table_code' => __('manager.v_code_unique')])->withInput();
            }
        }

        $existing = Table::where('outlets_id', $outletId)->whereIn('table_code', $codes)->pluck('table_code')->all();
        $toCreate = array_values(array_diff($codes, $existing));

        if (! $toCreate) {
            return back()->withErrors(['table_code' => 'Semua kode meja pada rentang tersebut sudah ada di outlet ini.'])->withInput();
        }

        $isActive = $request->boolean('is_active');
        $posX = (float) ($data['position_x'] ?? 50);
        $posY = (float) ($data['position_y'] ?? 50);
        $lastTable = null;

        foreach ($toCreate as $code) {
            $lastTable = Table::create([
                'outlets_id'   => $outletId,
                'areas_id'     => (int) $data['areas_id'],
                'table_code'   => $code,
                'capacity'     => (int) $data['capacity'],
                'min_capacity' => (int) $data['min_capacity'],
                'position_x'   => $posX,
                'position_y'   => $posY,
                'is_active'    => $isActive,
            ]);
        }

        $message = count($toCreate) === 1
            ? __('manager.tbl_added')
            : count($toCreate) . ' meja berhasil ditambahkan' . (count($existing) ? ' (' . count($existing) . ' sudah ada dilewati).' : '.');

        return $this->backTo($lastTable, $message);
    }

    /** @return list<string> */
    private function expandTableCodes(string $input): array
    {
        if (preg_match('/^([A-Za-z\-_]*?)(\d+)\s*-\s*([A-Za-z\-_]*?)(\d+)$/', $input, $m)) {
            $prefix = $m[1];
            $prefix2 = $m[3];
            $startStr = $m[2];
            $endStr = $m[4];

            if ($prefix2 === '' || strcasecmp($prefix, $prefix2) === 0) {
                $start = (int) $startStr;
                $end = (int) $endStr;
                if ($end >= $start) {
                    $padLen = strlen($startStr) > 1 && str_starts_with($startStr, '0') ? strlen($startStr) : 0;
                    $codes = [];
                    for ($i = $start; $i <= $end; $i++) {
                        $num = $padLen > 0 ? str_pad((string) $i, $padLen, '0', STR_PAD_LEFT) : (string) $i;
                        $codes[] = strtoupper($prefix) . $num;
                    }

                    return $codes;
                }
            }
        }

        return [$input];
    }

    public function update(Request $request, Table $table): RedirectResponse
    {
        app(OutletAccess::class)->assertCanAccess($request->user(), (int) $table->outlets_id);
        $data = $this->validated($request, $table);
        app(OutletAccess::class)->assertCanAccess($request->user(), (int) $data['outlets_id']);
        $table->update($data);

        return $this->backTo($table, __('manager.tbl_updated'));
    }

    public function destroy(Request $request, Table $table): RedirectResponse
    {
        app(OutletAccess::class)->assertCanAccess($request->user(), (int) $table->outlets_id);
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
            'position_x'   => ['nullable', 'numeric', 'between:0,100'],
            'position_y'   => ['nullable', 'numeric', 'between:0,100'],
        ], [
            'table_code.unique' => __('manager.v_code_unique'),
            'min_capacity.lte'  => __('manager.v_min_lte'),
            'areas_id.exists'   => __('manager.v_area_exists'),
        ]);

        $data['position_x'] = $data['position_x'] ?? ($table?->position_x ?? 50);
        $data['position_y'] = $data['position_y'] ?? ($table?->position_y ?? 50);
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
