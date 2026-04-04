<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PassengerOrderHistoryController extends Controller
{
    /**
     * История заказов пассажира (завершённые и отменённые)
     */
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'passenger') {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $passenger = \App\Models\Passenger::where('user_id', $user->id)->first();
        
        if (!$passenger) {
            return response()->json(['orders' => [], 'pagination' => []]);
        }

        $perPage = 10;
        $page = $request->input('page', 1);

        $query = Order::where('passenger_id', $passenger->id)
            ->whereIn('status', ['completed', 'cancelled'])
            ->where('is_hidden', false)
            ->orderByDesc('created_at');

        $total = $query->count();
        $orders = $query->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'pickup_address' => $order->pickup_address,
                    'dropoff_address' => $order->dropoff_address,
                    'distance' => $order->distance,
                    'duration' => $order->duration,
                    'final_price' => $order->final_price,
                    'status' => $order->status,
                    'tariff_id' => $order->tariff_id,
                    'created_at' => $order->created_at,
                    'completed_at' => $order->completed_at,
                ];
            });

        $pagination = [
            'current_page' => (int) $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
        ];

        return response()->json([
            'orders' => $orders,
            'pagination' => $pagination,
        ]);
    }
}