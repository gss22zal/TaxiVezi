<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'code' => 'super_admin',
                'name' => 'Super Admin',
                'description' => 'Полный доступ ко всем функциям',
                'permissions' => json_encode(['*']),
                'is_system' => true,
                'created_at' => DB::raw("GETDATE()"),
                'updated_at' => DB::raw("GETDATE()"),
            ],
            [
                'code' => 'admin',
                'name' => 'Admin',
                'description' => 'Администратор системы',
                'permissions' => json_encode(['users.view', 'orders.view', 'finance.view', 'settings.edit']),
                'is_system' => true,
                'created_at' => DB::raw("GETDATE()"),
                'updated_at' => DB::raw("GETDATE()"),
            ],
            [
                'code' => 'dispatcher',
                'name' => 'Dispatcher',
                'description' => 'Диспетчер',
                'permissions' => json_encode(['orders.view', 'orders.assign', 'drivers.view']),
                'is_system' => true,
                'created_at' => DB::raw("GETDATE()"),
                'updated_at' => DB::raw("GETDATE()"),
            ],
            [
                'code' => 'driver',
                'name' => 'Driver',
                'description' => 'Водитель',
                'permissions' => json_encode(['orders.accept', 'profile.edit']),
                'is_system' => true,
                'created_at' => DB::raw("GETDATE()"),
                'updated_at' => DB::raw("GETDATE()"),
            ],
            [
                'code' => 'passenger',
                'name' => 'Passenger',
                'description' => 'Пассажир',
                'permissions' => json_encode(['orders.create', 'profile.edit']),
                'is_system' => true,
                'created_at' => DB::raw("GETDATE()"),
                'updated_at' => DB::raw("GETDATE()"),
            ],
        ];

        DB::table('roles')->insert($roles);
    }
}

