<?php

namespace App\Http\Controllers;

use App\Models\Passenger;
use App\Models\Tariff;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PassengerController extends Controller
{
    public function home()
    {
        $user = Auth::user();

        // Ищем профиль пассажира
        $passenger = Passenger::where('user_id', $user->id)->first();

        // Если пассажир не найден - создаём
        if (!$passenger) {
            $passenger = Passenger::create([
                'user_id' => $user->id,
                'is_active' => true,
            ]);
        }

        // Получаем тарифы
        $tariffs = Tariff::where('is_active', true)
            ->orderBy('id')
            ->get();

        return Inertia::render('Passenger/Home', [
            'passenger' => [
                'id' => $passenger->id,
                'user' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone' => $user->phone,
                ]
            ],
            'tariffs' => $tariffs,
        ]);
    }
}
