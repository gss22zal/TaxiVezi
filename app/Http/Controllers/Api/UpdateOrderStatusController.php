<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UpdateOrderStatusController extends Controller
{
    public function __invoke(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:new,accepted,arrived,in_transit,completed,cancelled',
        ]);

        $allowedTransitions = [
            'new' => ['accepted', 'cancelled'],
            'accepted' => ['arrived', 'in_transit', 'cancelled'],
            'arrived' => ['in_transit', 'cancelled'],
            'in_transit' => ['completed', 'cancelled'],
            'completed' => [],
            'cancelled' => [],
        ];

        $newStatus = $request->status;
        $currentStatus = $order->status;

        // Разрешаем любые переходы для админа/диспетчера
        // (убираем ограничение allowedTransitions)

        $timezone = Setting::get('app.timezone', 'Europe/Moscow');
        $now = Carbon::now($timezone)->format('Y-m-d H:i:s.v');

        $updateData = [
            'status' => $newStatus,
            'updated_at' => DB::raw("CAST('$now' AS DATETIME2)"),
        ];

        // Устанавливаем временные метки
        if ($newStatus === 'accepted' && !$order->accepted_at) {
            $updateData['accepted_at'] = DB::raw("CAST('$now' AS DATETIME2)");
        }
        if ($newStatus === 'arrived' && !$order->arrived_at) {
            $updateData['arrived_at'] = DB::raw("CAST('$now' AS DATETIME2)");
        }
        if ($newStatus === 'in_transit' && !$order->started_at) {
            $updateData['started_at'] = DB::raw("CAST('$now' AS DATETIME2)");
        }
        if ($newStatus === 'completed' && !$order->completed_at) {
            $updateData['completed_at'] = DB::raw("CAST('$now' AS DATETIME2)");
        }
        if ($newStatus === 'cancelled' && !$order->cancelled_at) {
            $updateData['cancelled_at'] = DB::raw("CAST('$now' AS DATETIME2)");
            $updateData['cancelled_by'] = 'dispatcher';
        }

        DB::table('orders')
            ->where('id', $order->id)
            ->update($updateData);

        Log::info('Order status changed', [
            'order_id' => $order->id,
            'old_status' => $currentStatus,
            'new_status' => $newStatus,
            'changed_by' => $request->user()->id,
        ]);

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'status' => $newStatus,
                'accepted_at' => $order->accepted_at,
                'arrived_at' => $order->arrived_at,
                'started_at' => $order->started_at,
                'completed_at' => $order->completed_at,
                'cancelled_at' => $order->cancelled_at,
            ],
        ]);
    }
}
