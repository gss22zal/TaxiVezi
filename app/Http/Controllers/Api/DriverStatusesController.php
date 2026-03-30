<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverStatusesController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = Auth::user();
        
        if (!$user || !in_array($user->role, ['admin', 'dispatcher'])) {
            return response()->json(['drivers' => []]);
        }
        
        // Получаем всех активных водителей (не заблокированных)
        $drivers = Driver::whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->with('user:id,first_name,last_name')
            ->get()
            ->map(function ($driver) {
                // Проверяем, есть ли активный заказ
                $activeOrder = Order::where('driver_id', $driver->id)
                    ->whereIn('status', ['accepted', 'arrived', 'started', 'in_progress'])
                    ->exists();
                
                $fullName = $driver->user 
                    ? ($driver->user->first_name . ' ' . $driver->user->last_name)
                    : 'Водитель';
                
                $primaryCar = $driver->cars()->where('is_primary', true)->first();
                
                return [
                    'id' => $driver->id,
                    'name' => trim($fullName) ?: 'Водитель',
                    'car' => $primaryCar ? [
                        'id' => $primaryCar->id,
                        'brand' => $primaryCar->brand,
                        'model' => $primaryCar->model,
                        'plate_number' => $primaryCar->plate_number,
                    ] : null,
                    'is_online' => (bool) $driver->is_online,
                    'is_busy' => $activeOrder,
                    'status' => $activeOrder ? 'busy' : ($driver->is_online ? 'free' : 'offline'),
                ];
            });
        
        return response()->json(['drivers' => $drivers]);
    }
}
