<?php

namespace App\Http\Controllers\Dispatcher;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\Order;
use App\Models\Setting;
use Inertia\Inertia;

class DispatcherMapController extends Controller
{
    public function index()
    {
        // Получаем настройки карты
        $mapSettings = [
            'yandex_maps_api_key' => Setting::get('maps.yandex_maps_api_key', ''),
            'google_maps_api_key' => Setting::get('maps.google_maps_api_key', ''),
            // Преобразуем строку "lat,lon" в массив [lat, lon]
            'map_center' => $this->parseMapCenter(Setting::get('maps.default_map_center', '55.0415,82.9346')),
            'map_zoom' => (int) Setting::get('maps.default_map_zoom', 12),
        ];

        // Получаем активных водителей с координатами
        $drivers = Driver::where('is_online', true)
            ->with('user')
            ->get()
            ->map(function ($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->user->first_name . ' ' . mb_substr($driver->user->last_name ?? '', 0, 1) . '.',
                    'lat' => $driver->current_lat,
                    'lng' => $driver->current_lng,
                    'status' => $driver->status,
                    'car' => $driver->car ? [
                        'color' => $driver->car->color,
                        'brand' => $driver->car->brand,
                        'model' => $driver->car->model,
                        'license_plate' => $driver->car->license_plate,
                    ] : null,
                ];
            });

        // Получаем активные заказы
        $orders = Order::whereIn('status', ['new', 'accepted', 'arrived', 'in_transit'])
            ->whereNotNull('pickup_lat')
            ->whereNotNull('pickup_lng')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'lat' => $order->pickup_lat,
                    'lng' => $order->pickup_lng,
                    'pickup_address' => $order->pickup_address,
                    'dropoff_address' => $order->dropoff_address,
                    'status' => $order->status,
                    'passenger_name' => $order->passenger_name,
                ];
            });

        // Получаем данные заказа для построения маршрута (если передан order_id)
        $orderRoute = null;
        $orderId = request('order');
        if ($orderId) {
            $order = Order::find($orderId);
            if ($order) {
                $orderRoute = [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'pickup_address' => $order->pickup_address,
                    'dropoff_address' => $order->dropoff_address,
                    'pickup_lat' => $order->pickup_lat,
                    'pickup_lng' => $order->pickup_lng,
                    'dropoff_lat' => $order->dropoff_lat,
                    'dropoff_lng' => $order->dropoff_lng,
                ];
            }
        }

        return Inertia::render('Dispatcher/Map', [
            'mapSettings' => $mapSettings,
            'mapDrivers' => $drivers,
            'mapOrders' => $orders,
            'orderRoute' => $orderRoute,
        ]);
    }

    /**
     * API: получение данных карты (для polling)
     */
    public function mapData()
    {
        // Получаем активных водителей с координатами
        $drivers = Driver::where('is_online', true)
            ->with('user')
            ->get()
            ->map(function ($driver) {
                return [
                    'id' => $driver->id,
                    'name' => $driver->user->first_name . ' ' . mb_substr($driver->user->last_name ?? '', 0, 1) . '.',
                    'lat' => $driver->current_lat,
                    'lng' => $driver->current_lng,
                    'status' => $driver->status,
                    'car' => $driver->car ? [
                        'color' => $driver->car->color,
                        'brand' => $driver->car->brand,
                        'model' => $driver->car->model,
                        'license_plate' => $driver->car->license_plate,
                    ] : null,
                ];
            });

        // Получаем активные заказы
        $orders = Order::whereIn('status', ['new', 'accepted', 'arrived', 'in_transit'])
            ->whereNotNull('pickup_lat')
            ->whereNotNull('pickup_lng')
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'lat' => $order->pickup_lat,
                    'lng' => $order->pickup_lng,
                    'pickup_address' => $order->pickup_address,
                    'dropoff_address' => $order->dropoff_address,
                    'status' => $order->status,
                    'passenger_name' => $order->passenger_name,
                ];
            });

        return response()->json([
            'drivers' => $drivers,
            'orders' => $orders,
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
