<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Outlet;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(): View
    {
        return view('admin.outlets', [
            'outlets' => Outlet::query()->with(['areas' => fn ($q) => $q->withCount('tables')->orderBy('name')])->withCount('tables')->ordered()->get(),
        ]);
    }

    public function storeOutlet(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'alpha_dash', 'unique:outlets,code'],
            'address' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $outlet = DB::transaction(function () use ($data, $request, $audit): Outlet {
            $outlet = Outlet::create($data + ['is_active' => true]);
            $audit->record($request, 'admin_outlet_created', 'outlet', $outlet->outlets_id, null, $outlet->only(['name', 'code', 'address', 'phone', 'is_active']));
            return $outlet;
        });

        return back()->with('success', 'Outlet berhasil ditambahkan.');
    }

    public function toggleOutlet(Request $request, Outlet $outlet, AdminAuditLogger $audit): RedirectResponse
    {
        $activate = $request->boolean('is_active');
        $old = ['is_active' => (bool) $outlet->is_active];

        if ((bool) $outlet->is_active !== $activate) {
            DB::transaction(function () use ($outlet, $activate, $old, $request, $audit): void {
                $outlet->update(['is_active' => $activate]);
                $audit->record(
                    $request,
                    $activate ? 'admin_outlet_activated' : 'admin_outlet_deactivated',
                    'outlet',
                    (int) $outlet->outlets_id,
                    $old,
                    ['is_active' => $activate, 'name' => $outlet->name],
                );
            });
        }

        return back()->with('success', 'Outlet ' . $outlet->name . ' berhasil ' . ($activate ? 'diaktifkan.' : 'dinonaktifkan.'));
    }

    public function storeArea(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $outletId = (int) $request->input('outlets_id');
        $data = $request->validate([
            'outlets_id' => ['required', 'integer', 'exists:outlets,outlets_id'],
            'name' => ['required', 'string', 'max:50', Rule::unique('areas', 'name')->where('outlets_id', $outletId)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $area = DB::transaction(function () use ($data, $request, $audit): Area {
            $area = Area::create($data + ['is_active' => true]);
            $audit->record($request, 'admin_area_created', 'area', $area->areas_id, null, $area->only(['outlets_id', 'name', 'description', 'is_active']));
            return $area;
        });

        return back()->with('success', 'Area berhasil ditambahkan.');
    }
}
