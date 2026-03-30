<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DriverProfileController extends Controller
{
    public function __invoke()
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'Не авторизован'], 401);
        }
        
        // Получаем водителя
        $driver = Driver::with('user')->where('user_id', $user->id)->first();
        
        if (!$driver) {
            return response()->json(['message' => 'Водитель не найден'], 404);
        }
        
        // Получаем основной автомобиль
        $car = $driver->cars()->where('is_primary', true)->first();
        
        return response()->json([
            'driver' => [
                'id' => $driver->id,
                'name' => $driver->user->first_name . ' ' . $driver->user->last_name,
                'phone' => $driver->user->phone,
                'car' => $car ? $car->brand . ' ' . $car->model . ' • ' . $car->plate_number : 'Нет автомобиля',
            ]
        ]);
    }
}
