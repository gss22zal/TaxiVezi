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
            'default_map_center' => Setting::get('maps.default_map_center', '53.990061,84.746699'),
            'default_map_zoom' => Setting::get('maps.default_map_zoom', 15),
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

        return Inertia::render('Dispatcher/Map', [
            'mapSettings' => $mapSettings,
            'mapDrivers' => $drivers,
            'mapOrders' => $orders,
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
}
