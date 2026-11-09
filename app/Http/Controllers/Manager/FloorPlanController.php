<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\AuditLog;
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
            'floor_plan.required' => 'Pilih file denah terlebih dahulu.',
            'floor_plan.image'    => 'File harus berupa gambar.',
            'floor_plan.mimes'    => 'Format denah harus JPG, PNG, atau WebP.',
            'floor_plan.max'      => 'Ukuran denah maksimal 5 MB.',
            'floor_plan.uploaded' => 'Gagal mengunggah. Ukuran file melebihi batas server (upload_max_filesize di php.ini).',
        ]);

        $path = $request->file('floor_plan')->store('floor-plans', 'public');
        if (! $path) {
            return back()->withErrors(['floor_plan' => 'Gagal menyimpan file denah.']);
        }

        $oldPath = $area->floor_plan_path;
        $area->update(['floor_plan_path' => $path]);
        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->audit($request, $area, 'floor_plan_uploaded', $oldPath, $path);

        return $this->backTo($area)->with('success', 'Denah berhasil diunggah.');
    }

    public function destroy(Area $area): RedirectResponse
    {
        if ($area->floor_plan_path) {
            $oldPath = $area->floor_plan_path;
            $area->update(['floor_plan_path' => null]);
            Storage::disk('public')->delete($oldPath);
            $this->audit($request, $area, 'floor_plan_deleted', $oldPath, null);
        }

        return $this->backTo($area)->with('success', 'Denah dihapus.');
    }

    private function backTo(Area $area): RedirectResponse
    {
        return redirect()->route('manager.tables.index', [
            'outlet' => $area->outlets_id,
            'area'   => $area->areas_id,
        ]);
    }

    private function audit(Request $request, Area $area, string $action, ?string $oldPath, ?string $newPath): void
    {
        AuditLog::create([
            'users_id' => $request->user()?->users_id,
            'action' => $action,
            'entity_type' => 'area',
            'entity_id' => $area->areas_id,
            'old_values' => ['floor_plan_path' => $oldPath],
            'new_values' => ['floor_plan_path' => $newPath],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
