<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        // Получаем ID пользователей с ролью driver
        $driverUsers = DB::table('users')
            ->where('role', 'driver')
            ->orderBy('id')
            ->get();

        $drivers = [];
        $i = 0;
        
        $driverData = [
            [
                'driver_license_number' => '77 77 123456',
                'driver_license_expiry' => '2027-03-15',
                'total_rides' => 1842,
                'total_earnings' => 456000,
                'rating' => 4.9,
                'total_ratings' => 456,
                'status' => 'available',
                'commission_rate' => 20,
                'current_lat' => 55.7558,
                'current_lng' => 37.6173,
                'is_online' => true,
                'can_accept_orders' => true,
            ],
            [
                'driver_license_number' => '77 77 234567',
                'driver_license_expiry' => '2026-08-20',
                'total_rides' => 956,
                'total_earnings' => 234000,
                'rating' => 4.8,
                'total_ratings' => 234,
                'status' => 'busy',
                'commission_rate' => 20,
                'current_lat' => 55.7580,
                'current_lng' => 37.6210,
                'is_online' => true,
                'can_accept_orders' => false,
            ],
            [
                'driver_license_number' => '77 77 345678',
                'driver_license_expiry' => '2027-01-10',
                'total_rides' => 2103,
                'total_earnings' => 567000,
                'rating' => 5.0,
                'total_ratings' => 567,
                'status' => 'available',
                'commission_rate' => 20,
                'current_lat' => 55.7520,
                'current_lng' => 37.6150,
                'is_online' => true,
                'can_accept_orders' => true,
            ],
            [
                'driver_license_number' => '77 77 456789',
                'driver_license_expiry' => '2025-11-25',
                'total_rides' => 723,
                'total_earnings' => 178000,
                'rating' => 4.7,
                'total_ratings' => 178,
                'status' => 'offline',
                'commission_rate' => 20,
                'current_lat' => null,
                'current_lng' => null,
                'is_online' => false,
                'can_accept_orders' => false,
            ],
            [
                'driver_license_number' => '77 77 567890',
                'driver_license_expiry' => '2026-05-30',
                'total_rides' => 445,
                'total_earnings' => 112000,
                'rating' => 4.6,
                'total_ratings' => 112,
                'status' => 'available',
                'commission_rate' => 20,
                'current_lat' => 55.7600,
                'current_lng' => 37.6200,
                'is_online' => true,
                'can_accept_orders' => true,
            ],
        ];

        foreach ($driverUsers as $user) {
            $data = $driverData[$i] ?? $driverData[0];
            $drivers[] = array_merge($data, [
                'user_id' => $user->id,
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ]);
            $i++;
        }

        DB::table('drivers')->insert($drivers);
    }
}
