<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Area;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FloorPlanController extends Controller
{
    public function store(Request $request, Area $area): RedirectResponse
    {
        $request->validate([
            'floor_plan' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'floor_plan.required' => __('manager.v_fp_required'),
            'floor_plan.image'    => __('manager.v_fp_image'),
            'floor_plan.mimes'    => __('manager.v_fp_mimes'),
            'floor_plan.max'      => __('manager.v_fp_max'),
            'floor_plan.uploaded' => __('manager.v_fp_uploaded'),
        ]);

        if ($area->floor_plan_path) {
            Storage::disk('public')->delete($area->floor_plan_path);
        }

        $path = $request->file('floor_plan')->store('floor-plans', 'public');
        $area->update(['floor_plan_path' => $path]);

        return $this->backTo($area)->with('success', __('manager.fp_uploaded'));
    }

    public function destroy(Area $area): RedirectResponse
    {
        if ($area->floor_plan_path) {
            Storage::disk('public')->delete($area->floor_plan_path);
            $area->update(['floor_plan_path' => null]);
        }

        return $this->backTo($area)->with('success', __('manager.fp_deleted'));
    }

    private function backTo(Area $area): RedirectResponse
    {
        return redirect()->route('manager.tables.index', [
            'outlet' => $area->outlets_id,
            'area'   => $area->areas_id,
        ]);
    }
}