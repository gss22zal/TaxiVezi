<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin users
        $adminUsers = [
            [
                'email' => 'gss22zal@gmail.com',
                'phone' => '+79000000001',
                'password' => Hash::make('12345'),
                'password_hash' => Hash::make('12345'),
                'role' => 'admin',
                'first_name' => 'Администратор',
                'last_name' => 'Системы',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => DB::raw('GETDATE()'),
                'phone_verified_at' => DB::raw('GETDATE()'),
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
        ];
        DB::table('users')->insert($adminUsers);

        // Dispatcher users
        $dispatcherUsers = [
            [
                'email' => 'dispatcher1@taxivezi.ru',
                'phone' => '+79000000002',
                'password' => Hash::make('password'),
                'password_hash' => Hash::make('password'),
                'role' => 'dispatcher',
                'first_name' => 'Анна',
                'last_name' => 'Михайлова',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => DB::raw('GETDATE()'),
                'phone_verified_at' => DB::raw('GETDATE()'),
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            [
                'email' => 'dispatcher2@taxivezi.ru',
                'phone' => '+79000000003',
                'password' => Hash::make('password'),
                'password_hash' => Hash::make('password'),
                'role' => 'dispatcher',
                'first_name' => 'Сергей',
                'last_name' => 'Кузнецов',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => DB::raw('GETDATE()'),
                'phone_verified_at' => DB::raw('GETDATE()'),
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
        ];
        DB::table('users')->insert($dispatcherUsers);

        // Driver users - разные статусы
        $driverUsers = [
            // Активный верифицированный водитель
            [
                'email' => 'driver1@taxivezi.ru',
                'phone' => '+79000000010',
                'password' => Hash::make('password'),
                'password_hash' => Hash::make('password'),
                'role' => 'driver',
                'first_name' => 'Руслан',
                'last_name' => 'Ахмедов',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => DB::raw('GETDATE()'),
                'phone_verified_at' => DB::raw('GETDATE()'),
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            // Активный неверифицированный водитель
            [
                'email' => 'driver2@taxivezi.ru',
                'phone' => '+79000000011',
                'password' => Hash::make('password'),
                'password_hash' => Hash::make('password'),
                'role' => 'driver',
                'first_name' => 'Виктор',
                'last_name' => 'Козлов',
                'is_active' => true,
                'is_verified' => false,
                'email_verified_at' => null,
                'phone_verified_at' => null,
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            // Неактивный верифицированный водитель
            [
                'email' => 'driver3@taxivezi.ru',
                'phone' => '+79000000012',
                'password' => Hash::make('password'),
                'password_hash' => Hash::make('password'),
                'role' => 'driver',
                'first_name' => 'Константин',
                'last_name' => 'Смирнов',
                'is_active' => false,
                'is_verified' => true,
                'email_verified_at' => DB::raw('GETDATE()'),
                'phone_verified_at' => DB::raw('GETDATE()'),
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            // Неактивный неверифицированный водитель
            [
                'email' => 'driver4@taxivezi.ru',
                'phone' => '+79000000013',
                'password' => Hash::make('password'),
                'password_hash' => Hash::make('password'),
                'role' => 'driver',
                'first_name' => 'Алексей',
                'last_name' => 'Петров',
                'is_active' => false,
                'is_verified' => false,
                'email_verified_at' => null,
                'phone_verified_at' => null,
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            // Активный верифицированный водитель
            [
                'email' => 'driver5@taxivezi.ru',
                'phone' => '+79000000014',
                'password' => Hash::make('password'),
                'password_hash' => Hash::make('password'),
                'role' => 'driver',
                'first_name' => 'Дмитрий',
                'last_name' => 'Иванов',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => DB::raw('GETDATE()'),
                'phone_verified_at' => DB::raw('GETDATE()'),
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
        ];
        DB::table('users')->insert($driverUsers);

        // Passenger users - разные статусы
        $passengerUsers = [
            // Активный верифицированный пассажир
            [
                'email' => 'passenger1@mail.ru',
                'phone' => '+79000000100',
                'password' => Hash::make('password'),
                'password_hash' => Hash::make('password'),
                'role' => 'passenger',
                'first_name' => 'Александр',
                'last_name' => 'Иванов',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => DB::raw('GETDATE()'),
                'phone_verified_at' => DB::raw('GETDATE()'),
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            // Активный неверифицированный пассажир
            [
                'email' => 'passenger2@mail.ru',
                'phone' => '+79000000101',
                'password' => Hash::make('password'),
                'password_hash' => Hash::make('password'),
                'role' => 'passenger',
                'first_name' => 'Елена',
                'last_name' => 'Петрова',
                'is_active' => true,
                'is_verified' => false,
                'email_verified_at' => null,
                'phone_verified_at' => null,
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            // Неактивный верифицированный пассажир
            [
                'email' => 'passenger3@mail.ru',
                'phone' => '+79000000102',
                'password' => Hash::make('password'),
                'password_hash' => Hash::make('password'),
                'role' => 'passenger',
                'first_name' => 'Виктор',
                'last_name' => 'Сидоров',
                'is_active' => false,
                'is_verified' => true,
                'email_verified_at' => DB::raw('GETDATE()'),
                'phone_verified_at' => DB::raw('GETDATE()'),
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            // Неактивный неверифицированный пассажир
            [
                'email' => 'passenger4@mail.ru',
                'phone' => '+79000000103',
                'password' => Hash::make('password'),
                'password_hash' => Hash::make('password'),
                'role' => 'passenger',
                'first_name' => 'Андрей',
                'last_name' => 'Михайлов',
                'is_active' => false,
                'is_verified' => false,
                'email_verified_at' => null,
                'phone_verified_at' => null,
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
            // Активный верифицированный пассажир
            [
                'email' => 'passenger5@mail.ru',
                'phone' => '+79000000104',
                'password' => Hash::make('password'),
                'password_hash' => Hash::make('password'),
                'role' => 'passenger',
                'first_name' => 'Мария',
                'last_name' => 'Соколова',
                'is_active' => true,
                'is_verified' => true,
                'email_verified_at' => DB::raw('GETDATE()'),
                'phone_verified_at' => DB::raw('GETDATE()'),
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
        ];
        DB::table('users')->insert($passengerUsers);
    }
}
