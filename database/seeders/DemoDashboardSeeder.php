<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDashboardSeeder extends Seeder
{
    public function run(): void
    {
        $outletIds = DB::table('outlets')->orderBy('outlets_id')->pluck('outlets_id')->all();

        if (! $outletIds) {
            $this->command->warn('Belum ada outlet. Jalankan OutletTableSeeder dulu.');
            return;
        }

        // Hapus data demo lama (payments dan cancellation_requests ikut terhapus lewat cascade)
        DB::table('reservations')->where('reservation_code', 'like', 'DEMO-%')->delete();

        $now     = now();
        $counter = 0;

        $names = [
            'Nadia Putri', 'Rizky Mahendra', 'Dewi Ananda', 'Budi Santoso',
            'Sinta Maharani', 'Agus Pratama', 'Lina Wijaya', 'Fajar Nugroho',
            'Maya Sari', 'Dimas Prakoso', 'Rina Kartika', 'Eko Saputra',
        ];
        $statuses = [
            'confirmed', 'confirmed', 'confirmed', 'confirmed',
            'pending_payment', 'pending_payment', 'checked_in', 'completed',
            'confirmed', 'pending_payment', 'confirmed', 'confirmed',
        ];

        // Membuat satu reservasi, mengembalikan [id, grand_total]
        $make = function (int $outletId, string $name, string $date, int $hour, string $status, float $grand) use (&$counter, $now): array {
            $counter++;
            $subtotal = round($grand / 1.15, 2);
            $service  = round($subtotal * 0.05, 2);
            $tax      = round($subtotal * 0.10, 2);
            $total    = $subtotal + $service + $tax;
            $num      = str_pad((string) $counter, 4, '0', STR_PAD_LEFT);

            $id = DB::table('reservations')->insertGetId([
                'reservation_code'     => 'DEMO-' . $num,
                'outlets_id'           => $outletId,
                'customer_name'        => $name,
                'customer_phone'       => '0812000' . $num,
                'customer_email'       => strtolower(str_replace(' ', '.', $name)) . '@example.com',
                'guest_count'          => 2 + ($counter % 5),
                'reservation_date'     => $date,
                'start_time'           => sprintf('%02d:00:00', $hour),
                'end_time'             => sprintf('%02d:00:00', $hour + 2),
                'reservation_status'   => $status,
                'subtotal'             => $subtotal,
                'service_rate_percent' => 5,
                'service_amount'       => $service,
                'tax_rate_percent'     => 10,
                'tax_amount'           => $tax,
                'grand_total'          => $total,
                'created_at'           => $now,
                'updated_at'           => $now,
            ], 'reservations_id');

            return [$id, $total];
        };

        // Pembayaran terverifikasi (paid)
        $pay = function (int $reservationId, float $amount, $when): void {
            DB::table('payments')->insert([
                'reservations_id'   => $reservationId,
                'payment_method'    => 'bank_transfer',
                'bank_destination'  => 'BCA',
                'amount'            => $amount,
                'payment_status'    => 'paid',
                'proof_uploaded_at' => $when,
                'verified_at'       => $when,
                'created_at'        => $when,
                'updated_at'        => $when,
            ]);
        };

        // 12 reservasi hari ini
        $today          = now()->toDateString();
        $confirmedToday = [];

        foreach ($names as $i => $name) {
            $status = $statuses[$i];

            [$id, $total] = $make(
                $outletIds[$i % count($outletIds)],
                $name,
                $today,
                11 + ($i % 9),
                $status,
                350000 + ($i % 4) * 50000
            );

            if (in_array($status, ['confirmed', 'checked_in', 'completed'], true)) {
                $pay($id, $total, now());
            }
            if ($status === 'confirmed') {
                $confirmedToday[] = $id;
            }
        }

        // 6 hari sebelumnya: satu reservasi selesai per hari, untuk grafik 7 hari
        $weekly = [1200000, 1600000, 1400000, 2000000, 1800000, 2200000];

        foreach ($weekly as $k => $target) {
            $date = now()->subDays(6 - $k);

            [$id, $total] = $make(
                $outletIds[$k % count($outletIds)],
                'Tamu Demo ' . ($k + 1),
                $date->toDateString(),
                19,
                'completed',
                $target
            );
            $pay($id, $total, $date->copy()->setTime(20, 15));
        }

        // 3 permintaan pembatalan yang menunggu keputusan
        $reasons = ['Perubahan jadwal', 'Tamu berhalangan', 'Duplikat reservasi'];

        foreach (array_slice($confirmedToday, 0, 3) as $k => $reservationId) {
            DB::table('cancellation_requests')->insert([
                'reservations_id' => $reservationId,
                'requested_by'    => 'customer',
                'reason'          => $reasons[$k],
                'status'          => 'pending',
            ]);
        }

        $this->command->info('Data demo dibuat (kode reservasi diawali DEMO-).');
    }
}