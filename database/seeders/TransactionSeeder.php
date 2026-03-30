<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $users = DB::table('users')->orderBy('id')->get();
        $orders = DB::table('orders')->where('status', 'completed')->get();

        $transactions = [
            [
                'transaction_id' => 'TXN-' . time() . '-001',
                'user_id' => $users->where('role', 'passenger')->first()->id ?? 6,
                'order_id' => $orders[0]->id ?? null,
                'type' => 'ride_payment',
                'amount' => 2100,
                'currency' => 'RUB',
                'payment_method' => 'card',
                'payment_gateway' => 'yookassa',
                'status' => 'success',
                'description' => 'Оплата за поездку ТК-4818',
                'processed_at' => DB::raw("DATEADD(hour, -1, GETDATE())"),
                'created_at' => DB::raw("DATEADD(hour, -1, GETDATE())"),
                'updated_at' => DB::raw("DATEADD(hour, -1, GETDATE())"),
            ],
            [
                'transaction_id' => 'TXN-' . time() . '-002',
                'user_id' => $users->where('role', 'passenger')->first()->id ?? 7,
                'order_id' => $orders[1]->id ?? null,
                'type' => 'ride_payment',
                'amount' => 550,
                'currency' => 'RUB',
                'payment_method' => 'card',
                'payment_gateway' => 'yookassa',
                'status' => 'success',
                'description' => 'Оплата за поездку ТК-4817',
                'processed_at' => DB::raw("DATEADD(minute, -200, GETDATE())"),
                'created_at' => DB::raw("DATEADD(minute, -200, GETDATE())"),
                'updated_at' => DB::raw("DATEADD(minute, -200, GETDATE())"),
            ],
            [
                'transaction_id' => 'TXN-' . time() . '-003',
                'user_id' => $users->where('role', 'driver')->first()->id ?? 11,
                'order_id' => null,
                'type' => 'payout',
                'amount' => -15000,
                'currency' => 'RUB',
                'payment_method' => 'bank_card',
                'payment_gateway' => null,
                'status' => 'success',
                'description' => 'Выплата водителю',
                'processed_at' => DB::raw("DATEADD(day, -1, GETDATE())"),
                'created_at' => DB::raw("DATEADD(day, -1, GETDATE())"),
                'updated_at' => DB::raw("DATEADD(day, -1, GETDATE())"),
            ],
            [
                'transaction_id' => 'TXN-' . time() . '-004',
                'user_id' => $users->where('role', 'passenger')->first()->id ?? 6,
                'order_id' => null,
                'type' => 'topup',
                'amount' => 5000,
                'currency' => 'RUB',
                'payment_method' => 'card',
                'payment_gateway' => 'yookassa',
                'status' => 'success',
                'description' => 'Пополнение баланса',
                'processed_at' => DB::raw("DATEADD(day, -2, GETDATE())"),
                'created_at' => DB::raw("DATEADD(day, -2, GETDATE())"),
                'updated_at' => DB::raw("DATEADD(day, -2, GETDATE())"),
            ],
            [
                'transaction_id' => 'TXN-' . time() . '-005',
                'user_id' => $users->where('role', 'passenger')->first()->id ?? 8,
                'order_id' => null,
                'type' => 'bonus',
                'amount' => 500,
                'currency' => 'RUB',
                'payment_method' => null,
                'payment_gateway' => null,
                'status' => 'success',
                'description' => 'Бонус за приглашение друга',
                'processed_at' => DB::raw("DATEADD(day, -5, GETDATE())"),
                'created_at' => DB::raw("DATEADD(day, -5, GETDATE())"),
                'updated_at' => DB::raw("DATEADD(day, -5, GETDATE())"),
            ],
        ];

        DB::table('transactions')->insert($transactions);
    }
}
