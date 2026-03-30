<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use App\Models\Tariff;
use App\Models\Order;
use App\Models\Setting;
use App\Models\Driver;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Шарим тарифы и данные пользователя для всех страниц
        Inertia::share('tariffs', function () {
            return Tariff::active()->get()->map(function ($tariff) {
                return [
                    'id' => $tariff->id,
                    'name' => $tariff->name,
                    'code' => $tariff->code,
                    'base_price' => $tariff->base_price,
                    'price_per_km' => $tariff->price_per_km,
                    'price_per_min' => $tariff->price_per_min ?? $tariff->price_per_minute,
                    'min_price' => $tariff->min_price,
                    'description' => $tariff->description,
                ];
            });
        });

        Inertia::share('passenger', function () {
            $user = Auth::user();
            if (!$user) {
                return null;
            }
            
            // Проверяем по role_id или по полю role
            $isPassenger = ($user->role_id === 5) || ($user->role === 'passenger');
            if (!$isPassenger) {
                return null;
            }
            
            $passenger = \App\Models\Passenger::where('user_id', $user->id)->first();
            if ($passenger) {
                return [
                    'id' => $passenger->id,
                    'user' => [
                        'first_name' => $user->first_name ?? $user->name,
                        'last_name' => $user->last_name ?? '',
                        'phone' => $user->phone,
                    ]
                ];
            }
            return null;
        });

        // Статистика заказов для меню
        Inertia::share('orderStats', function () {
            return [
                'new' => Order::where('status', 'new')->count(),
                'accepted' => Order::where('status', 'accepted')->count(),
                'in_progress' => Order::whereIn('status', ['accepted', 'arrived', 'started'])->count(),
            ];
        });

        // Статистика водителей для меню диспетчера (только активные, не заблокированные)
        Inertia::share('driverStats', function () {
            $totalDrivers = Driver::whereHas('user', function ($q) {
                $q->where('is_active', true);
            })->count();

            $onlineDrivers = Driver::whereHas('user', function ($q) {
                $q->where('is_active', true);
            })->where('is_online', true)->count();

            // Водители которые сейчас выполняют заказ
            $busyDrivers = Driver::whereHas('user', function ($q) {
                $q->where('is_active', true);
            })->whereHas('orders', function ($q) {
                $q->whereIn('status', ['accepted', 'arrived', 'started', 'in_progress']);
            })->count();

            return [
                'total' => $totalDrivers,
                'online' => $onlineDrivers,
                'busy' => $busyDrivers,
                'free' => $onlineDrivers - $busyDrivers,
            ];
        });

        // Настройки из БД для использования в JS
        Inertia::share('settings', function () {
            $settings = Setting::all()->groupBy('group_name');
            $result = [];
            foreach ($settings as $group => $groupSettings) {
                $result[$group] = [];
                foreach ($groupSettings as $setting) {
                    // Убираем префикс группы из ключа
                    $key = str_replace($group . '.', '', $setting->key_name);
                    $result[$group][$key] = $setting->value;
                }
            }
            return $result;
        });
    }
}
