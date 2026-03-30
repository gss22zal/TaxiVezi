<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PromotionSeeder extends Seeder
{
    public function run(): void
    {
        $promotions = [
            [
                'code' => 'WELCOME500',
                'name' => 'Приветственный бонус',
                'description' => 'Скидка 500 рублей на первую поездку',
                'discount_type' => 'fixed',
                'discount_value' => 500,
                'max_discount' => 500,
                'min_order_amount' => 200,
                'usage_limit' => 1,
                'usage_count' => 0,
                'valid_from' => DB::raw('GETDATE()'),
                'valid_until' => DB::raw("DATEADD(year, 1, GETDATE())"),
                'is_active' => true,
                'applicable_tariffs' => json_encode(['economy', 'comfort', 'business']),
                'applicable_zones' => json_encode(['moscow_center']),
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            [
                'code' => 'SUMMER10',
                'name' => 'Летняя акция',
                'description' => 'Скидка 10% на все поездки',
                'discount_type' => 'percent',
                'discount_value' => 10,
                'max_discount' => 300,
                'min_order_amount' => 0,
                'usage_limit' => 5,
                'usage_count' => 0,
                'valid_from' => DB::raw('GETDATE()'),
                'valid_until' => DB::raw("DATEADD(month, 3, GETDATE())"),
                'is_active' => true,
                'applicable_tariffs' => json_encode(['economy', 'comfort']),
                'applicable_zones' => null,
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            [
                'code' => 'AIRPORT15',
                'name' => 'Аэропорт -15%',
                'description' => 'Скидка 15% на поездки в/из аэропортов',
                'discount_type' => 'percent',
                'discount_value' => 15,
                'max_discount' => 500,
                'min_order_amount' => 500,
                'usage_limit' => 3,
                'usage_count' => 0,
                'valid_from' => DB::raw('GETDATE()'),
                'valid_until' => DB::raw("DATEADD(month, 6, GETDATE())"),
                'is_active' => true,
                'applicable_tariffs' => json_encode(['comfort', 'business']),
                'applicable_zones' => json_encode(['sheremetyevo', 'domodedovo', 'vnukovo']),
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
        ];

        DB::table('promotions')->insert($promotions);
    }
}
