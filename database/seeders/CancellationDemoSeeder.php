<?php

namespace Database\Seeders;

use App\Models\CancellationRequest;
use App\Models\Outlet;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\Table;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CancellationDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Bersihkan data demo lama jika ada
        DB::table('cancellation_requests')->delete();

        $novotel = Outlet::where('code', 'novotel')->first();
        $ibis = Outlet::where('code', 'ibis')->first();

        if (! $novotel || ! $ibis) {
            return;
        }

        $novotelTable = Table::where('outlets_id', $novotel->outlets_id)->where('table_code', '06')->first()
            ?? Table::where('outlets_id', $novotel->outlets_id)->first();

        $ibisTable = Table::where('outlets_id', $ibis->outlets_id)->first();

        $novotelTable4 = Table::where('outlets_id', $novotel->outlets_id)->where('table_code', '04')->first()
            ?? $novotelTable;

        $items = [
            [
                'outlet' => $novotel,
                'table' => $novotelTable,
                'customer_name' => 'Nadia Putri',
                'customer_phone' => '081234567890',
                'customer_email' => 'nadia.putri@example.com',
                'code' => 'NVT-1031-284',
                'guest_count' => 2,
                'date' => '2026-10-31',
                'start_time' => '19:00:00',
                'end_time' => '21:00:00',
                'total' => 650000,
                'reason' => 'Perubahan jadwal dari pihak tamu.',
            ],
            [
                'outlet' => $ibis,
                'table' => $ibisTable,
                'customer_name' => 'Rizky Mahendra',
                'customer_phone' => '081987654321',
                'customer_email' => 'rizky.m@example.com',
                'code' => 'IBS-1031-105',
                'guest_count' => 2,
                'date' => '2026-10-31',
                'start_time' => '19:30:00',
                'end_time' => '21:30:00',
                'total' => 450000,
                'reason' => 'Tamu berhalangan',
            ],
            [
                'outlet' => $novotel,
                'table' => $novotelTable4,
                'customer_name' => 'Dewi Ananda',
                'customer_phone' => '081122334455',
                'customer_email' => 'dewi.ananda@example.com',
                'code' => 'NVT-1031-312',
                'guest_count' => 4,
                'date' => '2026-11-01',
                'start_time' => '20:00:00',
                'end_time' => '22:00:00',
                'total' => 820000,
                'reason' => 'Duplikat reservasi',
            ],
        ];

        foreach ($items as $data) {
            $rsv = Reservation::updateOrCreate(
                ['reservation_code' => $data['code']],
                [
                    'outlets_id' => $data['outlet']->outlets_id,
                    'customer_name' => $data['customer_name'],
                    'customer_phone' => $data['customer_phone'],
                    'customer_email' => $data['customer_email'],
                    'guest_count' => $data['guest_count'],
                    'reservation_date' => $data['date'],
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'reservation_status' => 'confirmed',
                    'subtotal' => $data['total'] * 0.85,
                    'service_rate_percent' => 5.00,
                    'service_amount' => $data['total'] * 0.05,
                    'tax_rate_percent' => 10.00,
                    'tax_amount' => $data['total'] * 0.10,
                    'grand_total' => $data['total'],
                    'notes' => 'Catatan khusus tamu.',
                ]
            );

            if ($data['table']) {
                $rsv->tables()->sync([$data['table']->tables_id]);
            }

            Payment::updateOrCreate(
                ['reservations_id' => $rsv->reservations_id],
                [
                    'payment_method' => 'bank_transfer',
                    'bank_destination' => 'BCA',
                    'amount' => $data['total'],
                    'payment_status' => 'paid',
                    'verified_at' => now(),
                ]
            );

            CancellationRequest::create([
                'reservations_id' => $rsv->reservations_id,
                'requested_by' => 'guest',
                'reason' => $data['reason'],
                'status' => 'pending',
                'created_at' => now(),
            ]);
        }
    }
}
