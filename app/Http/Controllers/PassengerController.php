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
            // Преобразуем строку "lat,lon" в массив [lat, lon]
            'map_center' => $this->parseMapCenter(Setting::get('maps.default_map_center', '55.0415,82.9346')),
            'map_zoom' => (int) Setting::get('maps.default_map_zoom', 12),
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

    /**
     * Преобразование строки координат в массив [lat, lon]
     * @param string $coordsString Формат "lat,lon"
     * @return array Массив [lat, lon]
     */
    private function parseMapCenter(string $coordsString): array
    {
        $parts = explode(',', $coordsString);
        
        if (count($parts) >= 2) {
            $lat = (float) trim($parts[0]);
            $lon = (float) trim($parts[1]);
            
            // Проверка на валидные координаты
            if (!is_nan($lat) && !is_nan($lon) && 
                $lat >= -90 && $lat <= 90 && 
                $lon >= -180 && $lon <= 180) {
                return [$lat, $lon];
            }
        }
        
        // Возвращаем дефолтные координаты (Новосибирск)
        return [55.0415, 82.9346];
    }
}
