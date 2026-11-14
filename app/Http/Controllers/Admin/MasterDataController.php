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

        return back()->with('success', 'Outlet berhasil ditambahkan. Jam operasional belum dikonfigurasi pada skema saat ini.');
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
