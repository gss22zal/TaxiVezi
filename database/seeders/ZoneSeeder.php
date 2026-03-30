<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        $zones = [
            [
                'name' => 'Центр Москвы',
                'code' => 'moscow_center',
                'surge_multiplier' => 1.0,
                'is_active' => true,
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            [
                'name' => 'Аэропорт Шереметьево',
                'code' => 'sheremetyevo',
                'surge_multiplier' => 1.2,
                'is_active' => true,
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            [
                'name' => 'Аэропорт Домодедово',
                'code' => 'domodedovo',
                'surge_multiplier' => 1.2,
                'is_active' => true,
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            [
                'name' => 'Внуково',
                'code' => 'vnukovo',
                'surge_multiplier' => 1.1,
                'is_active' => true,
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
        ];

        DB::table('zones')->insert($zones);
    }
}
