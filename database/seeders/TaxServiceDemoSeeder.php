<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Outlet;
use App\Models\TaxServiceRate;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TaxServiceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $novotel = Outlet::where('code', 'novotel')->first();
        $ibis = Outlet::where('code', 'ibis')->first();

        if (! $novotel || ! $ibis) {
            return;
        }

        // Hapus log tarif demo lama
        AuditLog::whereIn('action', ['tax_updated', 'service_updated', 'rates_updated'])->delete();

        // 1. Set Rate Aktif Novotel
        TaxServiceRate::updateOrCreate(
            ['outlets_id' => $novotel->outlets_id],
            [
                'tax_rate' => 12.00,
                'service_rate' => 5.00,
                'effective_from' => '2026-10-29',
                'effective_until' => null,
            ]
        );

        // 2. Set Rate Aktif ibis
        TaxServiceRate::updateOrCreate(
            ['outlets_id' => $ibis->outlets_id],
            [
                'tax_rate' => 10.00,
                'service_rate' => 5.00,
                'effective_from' => '2026-10-22',
                'effective_until' => null,
            ]
        );

        // 3. Masukkan 3 riwayat audit log persis seperti di gambar
        $logs = [
            [
                'date' => '2026-10-29 14:20:00',
                'action' => 'tax_updated',
                'outlet_name' => 'Novotel',
                'field' => 'Pajak',
                'old_val' => '—',
                'new_val' => '12%',
                'operator' => 'manager@novotel',
            ],
            [
                'date' => '2026-10-22 10:15:00',
                'action' => 'service_updated',
                'outlet_name' => 'ibis',
                'field' => 'Biaya layanan',
                'old_val' => '—',
                'new_val' => '—',
                'operator' => 'manager@ibis',
            ],
            [
                'date' => '2026-10-15 09:30:00',
                'action' => 'service_updated',
                'outlet_name' => 'Novotel',
                'field' => 'Biaya layanan',
                'old_val' => '—',
                'new_val' => '—',
                'operator' => 'manager@novotel',
            ],
        ];

        foreach ($logs as $log) {
            AuditLog::create([
                'users_id' => null,
                'action' => $log['action'],
                'entity_type' => 'tax_service_rate',
                'entity_id' => $novotel->outlets_id,
                'old_values' => [
                    'outlet_name' => $log['outlet_name'],
                    'field' => $log['field'],
                    'tax_rate' => $log['old_val'],
                    'service_rate' => $log['old_val'],
                ],
                'new_values' => [
                    'outlet_name' => $log['outlet_name'],
                    'field' => $log['field'],
                    'tax_rate' => $log['new_val'],
                    'service_rate' => $log['new_val'],
                    'operator' => $log['operator'],
                ],
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Mozilla/5.0',
                'created_at' => Carbon::parse($log['date']),
            ]);
        }
    }
}
