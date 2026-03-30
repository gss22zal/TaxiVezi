<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderStatsController extends Controller
{
    public function __invoke()
    {
        // Та же логика, что и в AppServiceProvider
        return response()->json([
            'new' => Order::where('status', 'new')->count(),
            'accepted' => Order::where('status', 'accepted')->count(),
            'in_progress' => Order::whereIn('status', ['accepted', 'arrived', 'started'])->count(),
            'completed_today' => Order::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->count(),
            'revenue_today' => Order::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->sum('final_price'),
        ]);
    }
}
