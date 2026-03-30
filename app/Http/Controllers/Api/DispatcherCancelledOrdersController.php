<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DispatcherCancelledOrdersController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !in_array($user->role, ['admin', 'dispatcher'])) {
            return response()->json(['orders' => [], 'stats' => []]);
        }
        
        $query = Order::with([
            'passenger:id,user_id',
            'passenger.user:id,first_name,last_name,phone',
            'driver:id,user_id',
            'driver.user:id,first_name,last_name',
        ])
        ->where('status', 'cancelled')
        ->orderBy('cancelled_at', 'desc')
        ->limit(100);
        
        $orders = $query->get();
        
        // Статистика
        $stats = [
            'total' => Order::where('status', 'cancelled')->count(),
            'by_passenger' => Order::where('status', 'cancelled')->where('cancelled_by', 'passenger')->count(),
            'by_driver' => Order::where('status', 'cancelled')->where('cancelled_by', 'driver')->count(),
        ];
        
        return response()->json([
            'orders' => $orders,
            'stats' => $stats
        ]);
    }
}
