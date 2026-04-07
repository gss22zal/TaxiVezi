<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderStatsController extends Controller
{
    public function __invoke()
    {
        // Получаем часовой пояс из настроек
        $timezone = Setting::get('app.timezone', 'Europe/Moscow');
        $today = Carbon::now($timezone)->toDateString();
        
        // Статистика заказов по статусам (активные заказы)
        return response()->json([
            'new' => Order::where('status', 'new')->count(),
            'accepted' => Order::where('status', 'accepted')->count(),
            'arrived' => Order::where('status', 'arrived')->count(),
            'in_transit' => Order::where('status', 'in_transit')->count(),
            // Статистика за сегодня
            'completed_today' => Order::where('status', 'completed')
                ->whereDate('completed_at', $today)
                ->count(),
            'revenue_today' => Order::where('status', 'completed')
                ->whereDate('completed_at', $today)
                ->sum('final_price'),
            // Новые заказы за сегодня
            'new_today' => Order::where('status', 'new')
                ->whereDate('created_at', $today)
                ->count(),
        ]);
    }
}
