<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $orders = DB::table('orders')->where('status', 'completed')->get();
        $passengers = DB::table('passengers')->orderBy('id')->get();
        $drivers = DB::table('drivers')->orderBy('id')->get();

        $reviews = [
            [
                'order_id' => $orders[0]->id ?? 1,
                'passenger_id' => $passengers[3]->id ?? 1,
                'driver_id' => $drivers[2]->id ?? 1,
                'passenger_rating' => 5,
                'driver_rating' => 5,
                'passenger_comment' => 'Отличная поездка! Водитель вежливый, машина чистая.',
                'driver_comment' => 'Приятный пассажир, всё прошло хорошо.',
                'passenger_tags' => json_encode(['вежливый', 'пунктуальный']),
                'driver_tags' => json_encode(['спокойный']),
                'is_anonymous' => false,
                'created_at' => DB::raw("DATEADD(hour, -1, GETDATE())"),
            ],
            [
                'order_id' => $orders[1]->id ?? 2,
                'passenger_id' => $passengers[4]->id ?? 2,
                'driver_id' => $drivers[0]->id ?? 2,
                'passenger_rating' => 4,
                'driver_rating' => 5,
                'passenger_comment' => 'Хорошая поездка, немного долго ехали из-за пробок.',
                'driver_comment' => 'Всё отлично!',
                'passenger_tags' => json_encode(['спокойный']),
                'driver_tags' => json_encode(['вежливый', 'пунктуальный']),
                'is_anonymous' => false,
                'created_at' => DB::raw("DATEADD(minute, -200, GETDATE())"),
            ],
            [
                'order_id' => $orders[2]->id ?? 3,
                'passenger_id' => $passengers[0]->id ?? 3,
                'driver_id' => $drivers[1]->id ?? 3,
                'passenger_rating' => 5,
                'driver_rating' => 4,
                'passenger_comment' => 'Водитель быстро довез, рекомендую!',
                'driver_comment' => null,
                'passenger_tags' => json_encode(['быстрый', 'профессионал']),
                'driver_tags' => null,
                'is_anonymous' => false,
                'created_at' => DB::raw("DATEADD(day, -2, GETDATE())"),
            ],
        ];

        DB::table('reviews')->insert($reviews);
    }
}
