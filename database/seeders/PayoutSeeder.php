<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PayoutSeeder extends Seeder
{
    public function run(): void
    {
        $drivers = DB::table('drivers')->orderBy('id')->get();

        $payouts = [
            [
                'payout_number' => 'VP-000001',
                'driver_id' => $drivers[0]->id ?? 1,
                'amount' => 15000,
                'period_start' => DB::raw("CAST(DATEADD(day, -14, GETDATE()) AS DATE)"),
                'period_end' => DB::raw("CAST(DATEADD(day, -7, GETDATE()) AS DATE)"),
                'total_rides' => 45,
                'total_earnings' => 45000,
                'commission_deducted' => 9000,
                'status' => 'paid',
                'payment_method' => 'bank_card',
                'bank_account' => '****1234',
                'bank_name' => 'Сбербанк',
                'processed_at' => DB::raw("DATEADD(day, -6, GETDATE())"),
                'created_at' => DB::raw("DATEADD(day, -7, GETDATE())"),
                'updated_at' => DB::raw("DATEADD(day, -6, GETDATE())"),
            ],
            [
                'payout_number' => 'VP-000002',
                'driver_id' => $drivers[1]->id ?? 2,
                'amount' => 8500,
                'period_start' => DB::raw("CAST(DATEADD(day, -14, GETDATE()) AS DATE)"),
                'period_end' => DB::raw("CAST(DATEADD(day, -7, GETDATE()) AS DATE)"),
                'total_rides' => 28,
                'total_earnings' => 28000,
                'commission_deducted' => 5600,
                'status' => 'paid',
                'payment_method' => 'bank_card',
                'bank_account' => '****5678',
                'bank_name' => 'Тинькофф',
                'processed_at' => DB::raw("DATEADD(day, -5, GETDATE())"),
                'created_at' => DB::raw("DATEADD(day, -7, GETDATE())"),
                'updated_at' => DB::raw("DATEADD(day, -5, GETDATE())"),
            ],
            [
                'payout_number' => 'VP-000003',
                'driver_id' => $drivers[2]->id ?? 3,
                'amount' => 12000,
                'period_start' => DB::raw("CAST(DATEADD(day, -7, GETDATE()) AS DATE)"),
                'period_end' => DB::raw("CAST(GETDATE() AS DATE)"),
                'total_rides' => 35,
                'total_earnings' => 38000,
                'commission_deducted' => 7600,
                'status' => 'pending',
                'payment_method' => 'bank_card',
                'bank_account' => '****9012',
                'bank_name' => 'Альфа-Банк',
                'processed_at' => null,
                'created_at' => DB::raw('GETDATE()'),
                'updated_at' => DB::raw('GETDATE()'),
            ],
        ];

        DB::table('payouts')->insert($payouts);
    }
}
