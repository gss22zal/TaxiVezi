<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CarSeeder extends Seeder
{
    public function run(): void
    {
        // Очистим старые данные
        DB::table('cars')->truncate();

        $drivers = DB::table('drivers')->orderBy('id')->get();

        // Разнообразные данные автомобилей
        $carData = [
            // Эконом класс
            ['brand' => 'KIA', 'model' => 'Rio', 'year' => 2023, 'color' => 'Белый', 'car_class' => 'econom'],
            ['brand' => 'Hyundai', 'model' => 'Solaris', 'year' => 2022, 'color' => 'Серебристый', 'car_class' => 'econom'],
            ['brand' => 'Volkswagen', 'model' => 'Polo', 'year' => 2023, 'color' => 'Чёрный', 'car_class' => 'econom'],
            ['brand' => 'Renault', 'model' => 'Logan', 'year' => 2021, 'color' => 'Синий', 'car_class' => 'econom'],
            ['brand' => 'Nissan', 'model' => 'Almera', 'year' => 2022, 'color' => 'Коричневый', 'car_class' => 'econom'],
            // Комфорт класс
            ['brand' => 'KIA', 'model' => 'K5', 'year' => 2023, 'color' => 'Чёрный', 'car_class' => 'comfort'],
            ['brand' => 'Hyundai', 'model' => 'Sonata', 'year' => 2022, 'color' => 'Белый', 'car_class' => 'comfort'],
            ['brand' => 'Toyota', 'model' => 'Corolla', 'year' => 2023, 'color' => 'Серый', 'car_class' => 'comfort'],
            ['brand' => 'Mazda', 'model' => '6', 'year' => 2022, 'color' => 'Красный', 'car_class' => 'comfort'],
            ['brand' => 'Skoda', 'model' => 'Octavia', 'year' => 2023, 'color' => 'Белый', 'car_class' => 'comfort'],
            // Бизнес класс
            ['brand' => 'Toyota', 'model' => 'Camry', 'year' => 2023, 'color' => 'Чёрный', 'car_class' => 'business'],
            ['brand' => 'BMW', 'model' => '3', 'year' => 2022, 'color' => 'Синий', 'car_class' => 'business'],
            ['brand' => 'Mercedes', 'model' => 'C-Class', 'year' => 2023, 'color' => 'Чёрный', 'car_class' => 'business'],
            ['brand' => 'Audi', 'model' => 'A4', 'year' => 2022, 'color' => 'Белый', 'car_class' => 'business'],
            ['brand' => 'Lexus', 'model' => 'ES', 'year' => 2023, 'color' => 'Серебристый', 'car_class' => 'business'],
            // Минивэн
            ['brand' => 'KIA', 'model' => 'Carnival', 'year' => 2023, 'color' => 'Белый', 'car_class' => 'minivan'],
            ['brand' => 'Toyota', 'model' => 'Sienna', 'year' => 2022, 'color' => 'Серый', 'car_class' => 'minivan'],
            ['brand' => 'Honda', 'model' => 'Odyssey', 'year' => 2021, 'color' => 'Чёрный', 'car_class' => 'minivan'],
            // Премиум
            ['brand' => 'Mercedes', 'model' => 'S-Class', 'year' => 2023, 'color' => 'Чёрный', 'car_class' => 'premium'],
            ['brand' => 'BMW', 'model' => '7', 'year' => 2023, 'color' => 'Синий', 'car_class' => 'premium'],
            ['brand' => 'Audi', 'model' => 'A8', 'year' => 2022, 'color' => 'Чёрный', 'car_class' => 'premium'],
            ['brand' => 'Porsche', 'model' => 'Panamera', 'year' => 2023, 'color' => 'Белый', 'car_class' => 'premium'],
            ['brand' => 'Lexus', 'model' => 'LS', 'year' => 2023, 'color' => 'Серебристый', 'car_class' => 'premium'],
        ];

        // Генерируем госномера
        $letters = ['А', 'В', 'Е', 'К', 'М', 'Н', 'О', 'Р', 'С', 'Т', 'Х', 'У'];
        $regions = ['77', '97', '99', '177', '197', '199', '777', '797', '799'];

        $cars = [];
        $carIndex = 0;

        foreach ($drivers as $driver) {
            $data = $carData[$carIndex % count($carData)];
            
            // Генерируем уникальный госномер
            $plateNumber = $letters[array_rand($letters)] . 
                          str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT) . 
                          $letters[array_rand($letters)] . 
                          $letters[array_rand($letters)];
            
            $region = $regions[array_rand($regions)];
            
            // Генерируем VIN
            $vin = 'X' . substr(str_shuffle('0123456789ABCDEFGHJKLMNPRSTUVWXYZ'), 0, 16);

            // Даты в формате для SQL Server
            $insuranceDays = rand(180, 365);
            $techDays = rand(90, 300);
            $insuranceExpiry = date('Y-m-d', strtotime("+{$insuranceDays} days"));
            $techExpiry = date('Y-m-d', strtotime("+{$techDays} days"));

            $cars[] = [
                'driver_id' => $driver->id,
                'brand' => $data['brand'],
                'model' => $data['model'],
                'year' => $data['year'],
                'color' => $data['color'],
                'plate_number' => $plateNumber,
                'region_code' => $region,
                'car_class' => $data['car_class'],
                'vin_number' => $vin,
                'insurance_number' => 'ЕЕА' . rand(100000000, 999999999),
                'insurance_expiry' => $insuranceExpiry,
                'tech_inspection_expiry' => $techExpiry,
                'is_active' => true,
                'is_primary' => true,
                'created_at' => DB::raw("CAST(N'" . date('Y-m-d H:i:s') . "' AS DATETIME2)"),
                'updated_at' => DB::raw("CAST(N'" . date('Y-m-d H:i:s') . "' AS DATETIME2)"),
            ];
            
            $carIndex++;
        }

        DB::table('cars')->insert($cars);
        
        echo "Создано " . count($cars) . " автомобилей для водителей\n";
    }
}
