<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverStatusController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || $user->role !== 'driver') {
            return response()->json(['error' => 'Доступ запрещен'], 403);
        }
        
        $driver = Driver::where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['error' => 'Водитель не найден'], 404);
        }
        
        $status = $request->input('status');
        
        switch ($status) {
            case 'online':
                $driver->update([
                    'is_online' => true,
                    'can_accept_orders' => true,
                ]);
                break;
            case 'offline':
                $driver->update([
                    'is_online' => false,
                    'can_accept_orders' => false,
                ]);
                break;
            case 'busy':
                $driver->update([
                    'is_online' => true,
                    'can_accept_orders' => false,
                ]);
                break;
        }
        
        return response()->json([
            'success' => true,
            'status' => $status,
            'driver' => [
                'is_online' => $driver->is_online,
                'can_accept_orders' => $driver->can_accept_orders,
            ]
        ]);
    }
}
