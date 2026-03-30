<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TariffSeeder extends Seeder
{
    public function run(): void
    {
        $tariffs = [
            [
                'name' => 'Эконом',
                'code' => 'economy',
                'base_price' => 100,
                'price_per_km' => 15,
                'price_per_min' => 3,
                'min_price' => 150,
                'min_distance' => 2,
                'min_duration' => 5,
                'commission_rate' => 20,
                'is_active' => true,
                'description' => 'Доступные автомобили эконом-класса',
                'created_at' => DB::raw("GETDATE()"),
                'updated_at' => DB::raw("GETDATE()"),
            ],
            [
                'name' => 'Комфорт',
                'code' => 'comfort',
                'base_price' => 200,
                'price_per_km' => 25,
                'price_per_min' => 5,
                'min_price' => 250,
                'min_distance' => 2,
                'min_duration' => 5,
                'commission_rate' => 20,
                'is_active' => true,
                'description' => 'Комфортные автомобили среднего класса',
                'created_at' => DB::raw("GETDATE()"),
                'updated_at' => DB::raw("GETDATE()"),
            ],
            [
                'name' => 'Бизнес',
                'code' => 'business',
                'base_price' => 500,
                'price_per_km' => 45,
                'price_per_min' => 10,
                'min_price' => 600,
                'min_distance' => 2,
                'min_duration' => 5,
                'commission_rate' => 25,
                'is_active' => true,
                'description' => 'Премиальные автомобили бизнес-класса',
                'created_at' => DB::raw("GETDATE()"),
                'updated_at' => DB::raw("GETDATE()"),
            ],
        ];

        DB::table('tariffs')->insert($tariffs);
    }
}
