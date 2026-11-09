<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OutletTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $outlets = [
            'novotel' => 'Novotel Food Exchange',
            'ibis'    => 'Ibis Kitchen',
        ];

        $xs = [20, 50, 80];
        $ys = [20, 50, 80];
        $capacities = [2, 2, 4, 4, 6, 2, 4, 4, 2];

        foreach ($outlets as $code => $name) {
            DB::table('outlets')->updateOrInsert(
                ['code' => $code],
                ['name' => $name, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]
            );
            $outletId = DB::table('outlets')->where('code', $code)->value('outlets_id');

            foreach (['Indoor', 'Outdoor'] as $areaName) {
                DB::table('areas')->updateOrInsert(
                    ['outlets_id' => $outletId, 'name' => $areaName],
                    ['is_active' => true, 'created_at' => $now, 'updated_at' => $now]
                );
            }

            $indoorId = DB::table('areas')
                ->where('outlets_id', $outletId)->where('name', 'Indoor')->value('areas_id');

            // 9 meja contoh di Indoor, tersusun 3 x 3 seperti mockup
            for ($i = 0; $i < 9; $i++) {
                DB::table('tables')->updateOrInsert(
                    ['outlets_id' => $outletId, 'table_code' => str_pad($i + 1, 2, '0', STR_PAD_LEFT)],
                    [
                        'areas_id'     => $indoorId,
                        'capacity'     => $capacities[$i],
                        'min_capacity' => 1,
                        'position_x'   => $xs[$i % 3],   // saya anggap persen 0-100 di atas denah
                        'position_y'   => $ys[intdiv($i, 3)],
                        'is_active'    => $i !== 6,      // meja 07 nonaktif, seperti di mockup
                        'created_at'   => $now,
                        'updated_at'   => $now,
                    ]
                );
            }
        }
    }
}