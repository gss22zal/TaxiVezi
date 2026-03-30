<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminCancelledOrdersController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with([
            'passenger:id,user_id',
            'passenger.user:id,first_name,last_name,phone',
            'driver:id,user_id',
            'driver.user:id,first_name,last_name',
        ])
        ->where('status', 'cancelled')
        ->orderBy('cancelled_at', 'desc');

        // Фильтр по тому, кто отменил
        if ($request->filled('cancelled_by')) {
            $query->where('cancelled_by', $request->cancelled_by);
        }

        // Фильтр по дате
        if ($request->filled('date_from')) {
            $query->whereDate('cancelled_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('cancelled_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(20);

        // Статистика
        $stats = [
            'total' => Order::where('status', 'cancelled')->count(),
            'by_passenger' => Order::where('status', 'cancelled')->where('cancelled_by', 'passenger')->count(),
            'by_driver' => Order::where('status', 'cancelled')->where('cancelled_by', 'driver')->count(),
            'by_dispatcher' => Order::where('status', 'cancelled')->where('cancelled_by', 'dispatcher')->count(),
        ];

        return inertia('Admin/Orders/Cancelled', [
            'orders' => $orders,
            'stats' => $stats,
            'filters' => $request->only(['cancelled_by', 'date_from', 'date_to']),
        ]);
    }
}
