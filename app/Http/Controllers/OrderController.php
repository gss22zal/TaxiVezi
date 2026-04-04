<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Driver;
use App\Models\Passenger;
use App\Models\Tariff;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Создание нового заказа (для пассажира)
     */
    public function store(Request $request)
    {
        $request->validate([
            // passenger_id теперь опциональный - найдём по текущему пользователю
            'passenger_id' => 'nullable|exists:passengers,id',
            'tariff_id' => 'required|exists:tariffs,id',
            'pickup_address' => 'required|string|max:255',
            'dropoff_address' => 'required|string|max:255',
            'distance' => 'required|numeric|min:0.1',
            'duration' => 'required|integer|min:1',
        ]);

        // Получаем часовой пояс из настроек
        $timezone = Setting::get('app.timezone', 'Europe/Moscow');
        $now = Carbon::now($timezone)->format('Y-m-d H:i:s.v');

        // Находим passenger_id - из запроса или по текущему пользователю
        $passengerId = $request->passenger_id;

        if (!$passengerId) {
            // Ищем passenger по текущему авторизованному пользователю
            $user = $request->user();
            $passenger = Passenger::where('user_id', $user->id)->first();

            if (!$passenger) {
                // Если passenger не найден - создаём новый профиль
                $passenger = Passenger::create([
                    'user_id' => $user->id,
                    'is_active' => true,
                ]);
            }

            $passengerId = $passenger->id;

            Log::info('Auto-assigned passenger_id from user', [
                'user_id' => $user->id,
                'passenger_id' => $passengerId
            ]);
        }

        $tariff = Tariff::findOrFail($request->tariff_id);
        $distance = $request->distance;
        $duration = $request->duration;

        // Расчёт стоимости
        $basePrice = $tariff->base_price;
        $distancePrice = $distance * $tariff->price_per_km;
        $timePrice = $duration * ($tariff->price_per_min ?? $tariff->price_per_minute);
        $finalPrice = $basePrice + $distancePrice + $timePrice;

        // Минимальная цена
        if ($tariff->min_price && $finalPrice < $tariff->min_price) {
            $finalPrice = $tariff->min_price;
        }

        DB::table('orders')->insert([
            'order_number' => 'ORD-' . strtoupper(Str::random(6)),
            'passenger_id' => $passengerId,
            'driver_id' => null,
            'tariff_id' => $tariff->id,
            'status' => 'new',
            'pickup_address' => $request->pickup_address,
            'pickup_lat' => $request->pickup_lat ?? 55.7558,
            'pickup_lng' => $request->pickup_lng ?? 37.6173,
            'dropoff_address' => $request->dropoff_address,
            'dropoff_lat' => $request->dropoff_lat ?? 55.7558,
            'dropoff_lng' => $request->dropoff_lng ?? 37.6173,
            'distance' => $distance,
            'duration' => $duration,
            'base_price' => $basePrice,
            'distance_price' => $distancePrice,
            'time_price' => $timePrice,
            'final_price' => $finalPrice,
            'passenger_name' => $request->passenger_name,
            'passenger_phone' => $request->passenger_phone,
            'notes' => $request->notes,
            'surge_multiplier' => 1.0,
            'payment_method' => 'card',
            'payment_status' => 'pending',
            'created_at' => DB::raw("CAST('$now' AS DATETIME2)"),
            'updated_at' => DB::raw("CAST('$now' AS DATETIME2)"),
        ]);

        $orderNumber = 'ORD-' . strtoupper(Str::random(6));
        Session::flash('success', 'Заказ ' . $orderNumber . ' создан! Стоимость: ' . $finalPrice . ' ₽');

        return to_route('passenger');
    }

    /**
     * Список заказов для диспетчера
     */
    public function index(Request $request)
    {
        $query = Order::with([
            'passenger:id,user_id',
            'passenger.user:id,first_name,last_name,phone',
            'driver:id,user_id',
            'driver.user:id,first_name,last_name,phone',
            'review:id,order_id,passenger_rating',
            'review.driver:id,user_id',
            'review.driver.user:id,first_name,last_name',
            'review.passenger:id,user_id',
            'review.passenger.user:id,first_name,last_name',
        ])->orderByDesc('created_at');

        // Фильтр по статусу
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('pickup_address', 'like', "%{$search}%")
                  ->orWhere('dropoff_address', 'like', "%{$search}%")
                  ->orWhereHas('passenger.user', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('driver.user', function($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        // Фильтр по статусу
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(20);

        // Статистика
        $stats = [
            'total' => Order::count(),
            'new' => Order::where('status', 'new')->count(),
            'in_progress' => Order::whereIn('status', ['accepted', 'arrived', 'started'])->count(),
            'completed' => Order::where('status', 'completed')->count(),
            'cancelled' => Order::where('status', 'cancelled')->count(),
            'today_completed' => Order::where('status', 'completed')
                ->whereDate('completed_at', today())->count(),
            'today_earnings' => Order::where('status', 'completed')
                ->whereDate('completed_at', today())
                ->sum('final_price'),
        ];

        // Статистика для sidebar
        $dispatcherStats = [
            'completed' => $stats['today_completed'],
            'active' => Order::whereIn('status', ['new', 'accepted', 'arrived', 'started'])->count(),
            'revenue' => $stats['today_earnings'] ?? 0,
        ];

        // Статистика заказов для меню (такая же как в AppServiceProvider)
        $orderStats = [
            'new' => Order::where('status', 'new')->count(),
            'accepted' => Order::where('status', 'accepted')->count(),
            'in_progress' => Order::whereIn('status', ['accepted', 'arrived', 'started'])->count(),
        ];

        return Inertia::render('Dispatcher/Orders', [
            'orders' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
            ],
            'stats' => $stats,
            'dispatcherStats' => $dispatcherStats,
            'orderStats' => $orderStats,
        ]);
    }

    /**
     * Список заказов для админа
     */
    public function adminIndex(Request $request)
    {
        $query = Order::with([
            'passenger:id,user_id',
            'passenger.user:id,first_name,last_name,phone,email',
            'driver:id,user_id',
            'driver.user:id,first_name,last_name,phone',
        ])->orderByDesc('created_at');

        // Фильтр по статусу
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Поиск
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('pickup_address', 'like', "%{$search}%")
                  ->orWhere('dropoff_address', 'like', "%{$search}%");
            });
        }

        // Фильтр по дате
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $orders = $query->paginate(20);

        return Inertia::render('Admin/Orders', [
            'orders' => $orders->items(),
            'pagination' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
                'date_from' => $request->date_from ?? '',
                'date_to' => $request->date_to ?? '',
            ],
        ]);
    }
}
