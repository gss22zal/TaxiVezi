<?php

namespace App\Http\Controllers;

use App\Models\Passenger;
use App\Models\Tariff;
use App\Models\Setting;
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

        // Получаем настройки карт
        $mapsSettings = [
            'yandex_maps_api_key' => Setting::get('maps.yandex_maps_api_key', ''),
            'google_maps_api_key' => Setting::get('maps.google_maps_api_key', ''),
            'default_map_center' => Setting::get('maps.default_map_center', '53.990061,84.746699'),
        ];

        return Inertia::render('Passenger/Home', [
            'passenger' => [
                'id' => $passenger->id,
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                ]
            ],
            'tariffs' => $tariffs,
            'mapsSettings' => $mapsSettings,
        ]);
    }
}
