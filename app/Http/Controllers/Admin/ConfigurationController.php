<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminConfigurationChange;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ConfigurationController extends Controller
{
    public function index(): View
    {
        $available = Schema::hasTable('admin_configuration_changes');
        $changes = $available ? AdminConfigurationChange::with('requestedBy')->latest()->limit(8)->get() : collect();

        return view('admin.configuration', [
            'available' => $available,
            'changes' => $changes,
            'holdMinutes' => (int) config('reservation.hold_minutes', 10),
            'durationMinutes' => (int) config('reservation.duration_minutes', 120),
            'maxGuests' => 50,
        ]);
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        if (! Schema::hasTable('admin_configuration_changes')) {
            return back()->with('error', 'Penyimpanan usulan konfigurasi memerlukan migration Admin yang baru ditambahkan. Migration tidak dijalankan otomatis.');
        }

        $data = $request->validate([
            'hold_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'duration_minutes' => ['nullable', 'integer', 'min:15', 'max:720'],
            'max_guests' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $current = [
            'hold_minutes' => (int) config('reservation.hold_minutes', 10),
            'duration_minutes' => (int) config('reservation.duration_minutes', 120),
            'max_guests' => 50,
        ];
        $labels = ['hold_minutes' => 'Temporary hold meja', 'duration_minutes' => 'Durasi reservasi', 'max_guests' => 'Maksimal tamu per reservasi'];
        $created = [];

        DB::transaction(function () use ($data, $current, $labels, $request, $audit, &$created): void {
            foreach ($data as $key => $value) {
                if ($value === null || (int) $value === $current[$key]) continue;
                $change = AdminConfigurationChange::create([
                    'config_key' => $key,
                    'current_value' => ['value' => $current[$key]],
                    'proposed_value' => ['value' => (int) $value],
                    'status' => 'pending',
                    'users_id_requested_by' => $request->user()->users_id,
                    'note' => 'Menunggu validasi pemilik proses; tidak diterapkan ke alur operasional.',
                ]);
                $created[] = ['id' => $change->id, 'key' => $key, 'value' => (int) $value];
                $audit->record($request, 'admin_configuration_proposed', 'admin_configuration_change', $change->id, ['value' => $current[$key]], ['key' => $key, 'label' => $labels[$key], 'value' => (int) $value, 'status' => 'pending']);
            }
        });

        return back()->with('success', $created ? 'Usulan konfigurasi disimpan sebagai pending. Nilai belum mengubah alur reservasi.' : 'Tidak ada perubahan nilai untuk disimpan.');
    }
}
