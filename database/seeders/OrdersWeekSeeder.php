<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use App\Models\Driver;
use App\Models\Passenger;
use App\Models\Tariff;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrdersWeekSeeder extends Seeder
{
    public function run(): void
    {
        // Получаем существующих водителей и пассажиров
        $drivers = Driver::with('user')->get();
        $passengers = Passenger::with('user')->get();
        $tariffs = Tariff::all();

        if ($drivers->isEmpty() || $passengers->isEmpty()) {
            $this->command->warn('Нет водителей или пассажиров. Сначала создайте их.');
            return;
        }

        // Ensure we have valid collections
        if ($drivers->count() === 0 || $passengers->count() === 0) {
            return;
        }

        if ($tariffs->isEmpty()) {
            // Создаем тарифы если нет
            $tariffs = [
                ['name' => 'Эконом', 'code' => 'economy', 'base_price' => 50, 'price_per_km' => 10, 'price_per_minute' => 2],
                ['name' => 'Комфорт', 'code' => 'comfort', 'base_price' => 80, 'price_per_km' => 14, 'price_per_minute' => 3],
                ['name' => 'Бизнес', 'code' => 'business', 'base_price' => 150, 'price_per_km' => 20, 'price_per_minute' => 5],
            ];
            foreach ($tariffs as $tariff) {
                Tariff::create($tariff);
            }
            $tariffs = Tariff::all();
        }

        $addresses = [
            'ул. Ленина, 1',
            'ул. Пушкина, 10',
            'пр. Мира, 25',
            'ул. Советская, 45',
            'пр. Победы, 78',
            'ул. Гагарина, 12',
            'ул. Кирова, 33',
            'ул. Чапаева, 8',
            'пр. Ленина, 56',
            'ул. Некрасова, 21',
            'Вокзал',
            'Аэропорт',
            'ТЦ Мега',
            'ТЦ Аура',
            'Парк Победы',
            'Центральный рынок',
            'Больница №1',
            'Университет',
            'Стадион',
            'Дворец культуры',
        ];

        $statuses = ['completed', 'completed', 'completed', 'completed', 'completed', 'completed', 'completed', 'cancelled'];
        $completedStatuses = ['completed', 'completed', 'completed', 'completed', 'completed', 'completed', 'completed', 'cancelled'];

        $this->command->info('Создаем 20 заказов за предыдущую неделю (архивные)...');

        // Создаем 20 заказов за прошлую неделю (только завершённые/отменённые)
        for ($i = 0; $i < 20; $i++) {
            // Фиксируем дату 8 дней назад
            $daysAgo = 8;
            $hoursAgo = rand(0, 23);
            $minutesAgo = rand(0, 59);
            
            // Создаем даты в прошлом от текущей даты
            // Создаем дату в формате SQL
            $createdAt = DB::raw("DATEADD(day, -$daysAgo, GETDATE())");
            
            // Выбираем случайный тариф
            $tariff = $tariffs->random();
            
            // Генерируем расстояние и время
            $distance = rand(2, 35);
            $duration = $distance * 3 + rand(0, 15); // минуты
            
            // Рассчитываем цену
            $finalPrice = $tariff->base_price + ($distance * $tariff->price_per_km) + ($duration * $tariff->price_per_minute);
            
            // Только завершённые или отменённые за прошлую неделю
            $status = $statuses[array_rand($statuses)];
            $completedAt = $status === 'completed' ? DB::raw("DATEADD(hour, -$hoursAgo, DATEADD(day, -$daysAgo, GETDATE()))") : null;
            
            // Все за прошлую неделю - с водителем
            $driver = $drivers->random();
            
            $pickupIndex = array_rand($addresses);
            $dropoffIndex = array_rand($addresses);
            while ($dropoffIndex === $pickupIndex) {
                $dropoffIndex = array_rand($addresses);
            }

            $orderNumber = 'ORD-' . strtoupper(Str::random(6));

            DB::table('orders')->insert([
                'order_number' => $orderNumber,
                'passenger_id' => $passengers->random()->id,
                'driver_id' => $driver ? $driver->id : null,
                'tariff_id' => $tariff->id,
                'pickup_address' => $addresses[$pickupIndex],
                'dropoff_address' => $addresses[$dropoffIndex],
                'pickup_lat' => 55.7558 + (rand(-100, 100) / 1000),
                'pickup_lng' => 37.6173 + (rand(-100, 100) / 1000),
                'dropoff_lat' => 55.7558 + (rand(-100, 100) / 1000),
                'dropoff_lng' => 37.6173 + (rand(-100, 100) / 1000),
                'distance' => $distance,
                'duration' => $duration,
                'status' => $status,
                'base_price' => $tariff->base_price,
                'distance_price' => $distance * $tariff->price_per_km,
                'time_price' => $duration * $tariff->price_per_minute,
                'final_price' => $finalPrice,
                'created_at' => $createdAt,
                'completed_at' => $completedAt,
                'notes' => rand(1, 10) > 7 ? 'Нужна детская коляска' : null,
                'updated_at' => $createdAt
            ]);
        }

        // Теперь создаем 10 заказов за текущий день
        $this->command->info('Создаем 10 заказов за текущий день...');
        
        for ($i = 0; $i < 10; $i++) {
            // Все заказы в пределах 2 часов
            $minutesAgo = rand(5, 120); // от 5 минут до 2 часов назад
            
            // Используем DATEADD для SQL Server
            $createdAt = DB::raw("DATEADD(minute, -$minutesAgo, GETDATE())");
            
            $tariff = $tariffs->random();
            $distance = rand(3, 25);
            $duration = $distance * 3 + rand(0, 10);
            $finalPrice = $tariff->base_price + ($distance * $tariff->price_per_km) + ($duration * $tariff->price_per_minute);
            
            // Первые 5 заказов - новые (без водителя, можно принять)
            $isNewOrder = $i < 5;
            $status = $isNewOrder ? 'new' : $completedStatuses[array_rand($completedStatuses)];
            $completedAt = $status === 'completed' ? DB::raw("GETDATE()") : null;

            $pickupIndex = array_rand($addresses);
            $dropoffIndex = array_rand($addresses);
            while ($dropoffIndex === $pickupIndex) {
                $dropoffIndex = array_rand($addresses);
            }

            // Новые заказы - без водителя, остальные - с водителем
            $driver = $isNewOrder ? null : $drivers->random();
            $orderNumber = 'ORD-' . strtoupper(Str::random(6));

            DB::table('orders')->insert([
                'order_number' => $orderNumber,
                'passenger_id' => $passengers->random()->id,
                'driver_id' => $driver ? $driver->id : null,
                'tariff_id' => $tariff->id,
                'pickup_address' => $addresses[$pickupIndex],
                'dropoff_address' => $addresses[$dropoffIndex],
                'pickup_lat' => 55.7558 + (rand(-100, 100) / 1000),
                'pickup_lng' => 37.6173 + (rand(-100, 100) / 1000),
                'dropoff_lat' => 55.7558 + (rand(-100, 100) / 1000),
                'dropoff_lng' => 37.6173 + (rand(-100, 100) / 1000),
                'distance' => $distance,
                'duration' => $duration,
                'status' => $status,
                'base_price' => $tariff->base_price,
                'distance_price' => $distance * $tariff->price_per_km,
                'time_price' => $duration * $tariff->price_per_minute,
                'final_price' => $finalPrice,
                'created_at' => $createdAt,
                'completed_at' => $completedAt,
                'updated_at' => $createdAt
            ]);
        }

        $this->command->info('Создано 30 заказов (20 за прошлую неделю + 10 за последние 2 часа)');
        
        // Выводим статистику
        $this->command->info('');
        $this->command->info('Статистика:');
        $this->command->info('- Всего заказов: ' . Order::count());
        $this->command->info('- Выполнено: ' . Order::where('status', 'completed')->count());
        $this->command->info('- Отменено: ' . Order::where('status', 'cancelled')->count());
        $this->command->info('- Выручка: ' . Order::where('status', 'completed')->sum('final_price') . ' ₽');
    }
}
