<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyUsersSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminRolePermissionSeeder::class);

        $accounts = [
            [
                'name' => 'Admin DINEA',
                'email' => 'admin@dinea.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Manager DINEA',
                'email' => 'manager@dinea.test',
                'role' => 'manager',
            ],
            [
                'name' => 'Finance DINEA',
                'email' => 'finance@dinea.test',
                'role' => 'finance',
            ],
            [
                'name' => 'Kasir DINEA',
                'email' => 'kasir@dinea.test',
                'role' => 'cashier',
            ],
        ];

        foreach ($accounts as $account) {
            $user = User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'password' => Hash::make('password'),
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$account['role']]);
        }
    }
}
