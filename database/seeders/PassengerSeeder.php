<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PassengerSeeder extends Seeder
{
    public function run(): void
    {
        // Получаем ID пользователей с ролью passenger
        $passengerUsers = DB::table('users')
            ->where('role', 'passenger')
            ->orderBy('id')
            ->get();

        $passengers = [];
        $i = 0;
        
        $passengerData = [
            ['balance' => 1500, 'bonus_balance' => 500, 'total_rides' => 45, 'total_spent' => 28500, 'rating' => 4.9, 'total_ratings' => 45, 'default_payment_method' => 'card', 'home_address' => 'ул. Ленина, 15', 'work_address' => 'Пресненская наб., 12', 'preferred_car_class' => 'comfort'],
            ['balance' => 0, 'bonus_balance' => 200, 'total_rides' => 28, 'total_spent' => 18200, 'rating' => 4.7, 'total_ratings' => 28, 'default_payment_method' => 'card', 'home_address' => 'ул. Профсоюзная, 92', 'work_address' => 'Ленинградский пр-т, 37', 'preferred_car_class' => 'economy'],
            ['balance' => 500, 'bonus_balance' => 0, 'total_rides' => 12, 'total_spent' => 6400, 'rating' => 5.0, 'total_ratings' => 12, 'default_payment_method' => 'cash', 'home_address' => 'пр. Мира, 45', 'work_address' => null, 'preferred_car_class' => 'economy'],
            ['balance' => 2000, 'bonus_balance' => 1000, 'total_rides' => 67, 'total_spent' => 42000, 'rating' => 4.8, 'total_ratings' => 67, 'default_payment_method' => 'card', 'home_address' => 'Таганская пл., 1', 'work_address' => 'Кутузовский пр-т, 32', 'preferred_car_class' => 'business'],
            ['balance' => 800, 'bonus_balance' => 150, 'total_rides' => 23, 'total_spent' => 12500, 'rating' => 4.6, 'total_ratings' => 23, 'default_payment_method' => 'card', 'home_address' => 'Варшавское ш., 28', 'work_address' => 'ул. Арбат, 10', 'preferred_car_class' => 'comfort'],
        ];

        foreach ($passengerUsers as $user) {
            $data = $passengerData[$i] ?? $passengerData[0];
            $passengers[] = [
                'user_id' => $user->id,
                'balance' => $data['balance'],
                'bonus_balance' => $data['bonus_balance'],
                'total_rides' => $data['total_rides'],
                'total_spent' => $data['total_spent'],
                'rating' => $data['rating'],
                'total_ratings' => $data['total_ratings'],
                'default_payment_method' => $data['default_payment_method'],
                'home_address' => $data['home_address'],
                'work_address' => $data['work_address'],
                'preferred_car_class' => $data['preferred_car_class'],
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ];
            $i++;
        }

        DB::table('passengers')->insert($passengers);
    }
}
