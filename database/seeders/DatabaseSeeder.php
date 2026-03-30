<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Основные справочники
        $this->call([
            RoleSeeder::class,
            TariffSeeder::class,
            ZoneSeeder::class,
            SettingSeeder::class,
            PromotionSeeder::class,
        ]);

        // Пользователи и связанные сущности
        $this->call([
            UserSeeder::class,
            PassengerSeeder::class,
            DriverSeeder::class,
            CarSeeder::class,
        ]);

        // Заказы и транзакции
        $this->call([
            OrdersWeekSeeder::class,
            TransactionSeeder::class,
            ReviewSeeder::class,
            PayoutSeeder::class,
        ]);
    }
}
