<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Driver;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DriverCancelOrderController extends Controller
{
    public function __invoke(Request $request, Order $order)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'driver') {
            return response()->json(['error' => 'Доступ запрещен'], 403);
        }
        
        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['error' => 'Водитель не найден'], 404);
        }
        
        // Проверяем, что заказ принадлежит этому водителю
        if (intval($order->driver_id) !== intval($driver->id)) {
            return response()->json(['error' => 'Заказ не принадлежит вам'], 403);
        }
        
        // Проверяем статус заказа
        if (!in_array($order->status, ['new', 'accepted', 'arrived', 'started'])) {
            return response()->json(['error' => 'Заказ уже завершён или отменён'], 400);
        }
        
        // Получаем время с учётом часового пояса
        $timezone = Setting::get('app.timezone', 'Europe/Moscow');
        $now = Carbon::now($timezone)->format('Y-m-d H:i:s.v');
        
        // Отменяем заказ
        DB::table('orders')
            ->where('id', $order->id)
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => DB::raw("CAST('$now' AS DATETIME2)"),
                'cancelled_by' => 'driver',
                'cancellation_reason' => $request->reason ?? 'Отменено водителем',
                'updated_at' => DB::raw("CAST('$now' AS DATETIME2)"),
            ]);
        
        // Освобождаем водителя
        DB::table('drivers')
            ->where('id', $driver->id)
            ->update([
                'is_online' => true,
                'can_accept_orders' => true,
                'updated_at' => DB::raw("CAST('$now' AS DATETIME2)"),
            ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Заказ отменён',
            'cancelled_by' => 'driver'
        ]);
    }
}
