<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AdminAuditLogger;
use App\Services\ReservationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ConfigurationController extends Controller
{
    public function index(ReservationSettings $settings): View
    {
        $available = Schema::hasTable('app_settings');
        $history = AuditLog::with('user')
            ->where('action', 'admin_configuration_updated')
            ->latest('created_at')
            ->latest('audit_logs_id')
            ->limit(10)
            ->get();

        return view('admin.configuration', [
            'available' => $available,
            'holdMinutes' => $settings->holdMinutes(),
            'durationMinutes' => $settings->durationMinutes(),
            'maxGuests' => $settings->maxGuests(),
            'bankName' => $settings->bankName(),
            'bankAccountNumber' => $settings->bankAccountNumber(),
            'bankAccountHolder' => $settings->bankAccountHolder(),
            'history' => $history,
        ]);
    }

    public function store(Request $request, ReservationSettings $settings, AdminAuditLogger $audit): RedirectResponse
    {
        if (! Schema::hasTable('app_settings')) {
            return back()->with('error', 'Tabel app_settings belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $data = $request->validate([
            'hold_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:720'],
            'max_guests' => ['required', 'integer', 'min:1', 'max:100'],
            'bank_name' => ['required', 'string', 'max:100'],
            'bank_account_number' => ['required', 'string', 'max:50'],
            'bank_account_holder' => ['required', 'string', 'max:100'],
        ]);

        $newValues = [
            'hold_minutes' => (string) (int) $data['hold_minutes'],
            'duration_minutes' => (string) (int) $data['duration_minutes'],
            'max_guests' => (string) (int) $data['max_guests'],
            'bank_name' => trim($data['bank_name']),
            'bank_account_number' => trim($data['bank_account_number']),
            'bank_account_holder' => trim($data['bank_account_holder']),
        ];

        $oldValues = [
            'hold_minutes' => (string) $settings->holdMinutes(),
            'duration_minutes' => (string) $settings->durationMinutes(),
            'max_guests' => (string) $settings->maxGuests(),
            'bank_name' => $settings->bankName(),
            'bank_account_number' => $settings->bankAccountNumber(),
            'bank_account_holder' => $settings->bankAccountHolder(),
        ];

        $diffOld = [];
        $diffNew = [];
        foreach ($newValues as $key => $val) {
            if (($oldValues[$key] ?? '') !== $val) {
                $diffOld[$key] = $oldValues[$key] ?? '';
                $diffNew[$key] = $val;
            }
        }

        if ($diffNew === []) {
            return back()->with('success', 'Konfigurasi tidak berubah.');
        }

        DB::transaction(function () use ($settings, $newValues, $request, $audit, $diffOld, $diffNew): void {
            $settings->setMany($newValues, $request->user()?->users_id);
            $audit->record(
                $request,
                'admin_configuration_updated',
                'app_setting',
                0,
                $diffOld,
                $diffNew,
            );
        });

        return back()->with('success', 'Konfigurasi sistem dan rekening bank pelanggan berhasil disimpan dan langsung berlaku.');
    }
}
