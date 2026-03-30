<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DispatcherOrdersController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !in_array($user->role, ['admin', 'dispatcher'])) {
            return response()->json(['orders' => []]);
        }
        
        $status = $request->get('status', 'all');
        
        $query = Order::with(['passenger.user', 'driver.user', 'tariff'])
            ->orderBy('created_at', 'desc')
            ->limit(50);
        
        if ($status && $status !== 'all') {
            if ($status === 'accepted') {
                $query->whereIn('status', ['accepted', 'arrived']);
            } else {
                $query->where('status', $status);
            }
        }
        
        $orders = $query->get();
        
        return response()->json([
            'orders' => $orders
        ]);
    }
}
