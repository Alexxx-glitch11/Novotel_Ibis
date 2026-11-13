<?php

namespace Database\Seeders;

use App\Models\Outlet;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MenuDemoSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'novotel' => [
                'Makanan' => [
                    [
                        'name' => 'Nasi Goreng Kampung',
                        'description' => 'Nasi goreng dengan telur dan pelengkap.',
                        'price' => 85000,
                    ],
                    [
                        'name' => 'Chicken Steak',
                        'description' => 'Ayam panggang dengan saus pilihan.',
                        'price' => 110000,
                    ],
                ],
                'Minuman' => [
                    [
                        'name' => 'Iced Tea',
                        'description' => 'Teh dingin segar.',
                        'price' => 25000,
                    ],
                    [
                        'name' => 'Orange Juice',
                        'description' => 'Jus jeruk segar.',
                        'price' => 35000,
                    ],
                ],
                'Dessert' => [
                    [
                        'name' => 'Chocolate Cake',
                        'description' => 'Kue cokelat dengan tekstur lembut.',
                        'price' => 45000,
                    ],
                ],
            ],

            'ibis' => [
                'Food' => [
                    [
                        'name' => 'Ibis Fried Rice',
                        'description' => 'Nasi goreng spesial.',
                        'price' => 75000,
                    ],
                    [
                        'name' => 'Grilled Chicken',
                        'description' => 'Ayam panggang dengan saus.',
                        'price' => 95000,
                    ],
                ],
                'Beverage' => [
                    [
                        'name' => 'Mineral Water',
                        'description' => 'Air mineral kemasan.',
                        'price' => 15000,
                    ],
                    [
                        'name' => 'Lemon Tea',
                        'description' => 'Teh dengan rasa lemon.',
                        'price' => 25000,
                    ],
                ],
                'Dessert' => [
                    [
                        'name' => 'Pudding',
                        'description' => 'Puding lembut dengan saus.',
                        'price' => 30000,
                    ],
                ],
            ],
        ];

        DB::transaction(function () use ($data) {
            foreach ($data as $outletCode => $categories) {
                $outlet = Outlet::where('code', $outletCode)
                    ->first();

                if (! $outlet) {
                    throw new RuntimeException(
                        "Outlet {$outletCode} tidak ditemukan."
                    );
                }

                foreach ($categories as $categoryName => $items) {
                    $category = MenuCategory::firstOrCreate(
                        [
                            'outlets_id' => $outlet->outlets_id,
                            'name' => $categoryName,
                        ],
                        [
                            'is_active' => true,
                        ]
                    );

                    foreach ($items as $item) {
                        MenuItem::firstOrCreate(
                            [
                                'outlets_id' => $outlet->outlets_id,
                                'name' => $item['name'],
                            ],
                            [
                                'menu_categories_id' => $category->menu_categories_id,
                                'description' => $item['description'],
                                'price' => $item['price'],
                                'image_path' => null,
                                'is_available' => true,
                            ]
                        );
                    }
                }
            }
        });

        $this->command->info(
            'Seeder kategori dan menu dummy berhasil diproses.'
        );
    }
}
