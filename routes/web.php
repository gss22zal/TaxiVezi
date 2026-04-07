<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PassengerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\AdminOrdersController;
use App\Http\Controllers\Admin\AdminCancelledOrdersController;
use App\Http\Controllers\Admin\AdminTariffsController;
use App\Http\Controllers\Admin\AdminCarsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminReviewsController;
use App\Http\Controllers\Admin\AdminSettingsController;
use App\Http\Controllers\Admin\AdminTransactionsController;
use App\Http\Controllers\Admin\AdminPayoutsController;
use App\Http\Controllers\Driver\CarController;
use App\Http\Controllers\Dispatcher\DispatcherReviewsController;
use App\Http\Controllers\Api\OrderStatsController;
use App\Http\Controllers\Api\AvailableOrdersController;
use \App\Http\Controllers\Api\AcceptOrderController;
use \App\Http\Controllers\Api\CompleteOrderController;
use \App\Http\Controllers\Api\StartTripController;
use \App\Http\Controllers\Api\ArrivedAtCustomerController;
use \App\Http\Controllers\Api\DriverCancelOrderController;
use \App\Http\Controllers\Api\DriverActiveOrderController;
use \App\Http\Controllers\Api\DriverProfileController;
use \App\Http\Controllers\Api\DriverOrderHistoryController;
use \App\Http\Controllers\Api\DriverStatusController;
use \App\Http\Controllers\Api\DispatcherOrdersController;
use \App\Http\Controllers\Api\DispatcherCancelledOrdersController;
use \App\Http\Controllers\Api\DriverStatusesController;
use \App\Http\Controllers\Api\PassengerOrderController;
use \App\Http\Controllers\Api\PassengerOrderHistoryController;
use \App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;



// API endpoint для статистики заказов (real-time)
Route::get('/api/order-stats', OrderStatsController::class)->middleware('web');

// API endpoint для доступных заказов водителя
Route::get('/api/available-orders', AvailableOrdersController::class)->middleware(['auth', 'api.rate_limit']);

// API endpoint для принятия заказа водителем
Route::post('/api/orders/{order}/accept', AcceptOrderController::class)->middleware('auth');

// API endpoint для завершения заказа водителем
Route::post('/api/orders/{order}/complete', CompleteOrderController::class)->middleware('auth');

// API endpoint для начала поездки (пассажир сел в такси)
Route::post('/api/orders/{order}/start-trip', StartTripController::class)->middleware('auth');

// API endpoint для отметки "у клиента" (водитель прибыл)
Route::post('/api/orders/{order}/arrived', ArrivedAtCustomerController::class)->middleware('auth');

// API endpoint для отмены заказа водителем
Route::post('/api/orders/{order}/driver-cancel', DriverCancelOrderController::class)->middleware('auth');

// API endpoint для активного заказа водителя
Route::get('/api/driver/active-order', DriverActiveOrderController::class)->middleware(['auth', 'api.rate_limit']);

// API endpoint для профиля водителя
Route::get('/api/driver/profile', DriverProfileController::class)->middleware(['auth', 'api.rate_limit']);

// API endpoint для истории заказов водителя (завершённые поездки)
Route::get('/api/driver/orders/history', DriverOrderHistoryController::class)->middleware(['auth', 'api.rate_limit']);

// API endpoint для статуса водителя (онлайн/офлайн/занят)
Route::post('/api/driver/status', DriverStatusController::class)->middleware('auth');

// API endpoint для заказов диспетчера (real-time)
Route::get('/api/dispatcher/orders', DispatcherOrdersController::class)->middleware(['auth', 'api.rate_limit']);

// API endpoint для отменённых заказов диспетчера
Route::get('/api/dispatcher/orders/cancelled', DispatcherCancelledOrdersController::class)->middleware(['auth', 'api.rate_limit']);

// API endpoint для статусов водителей
Route::get('/api/driver-statuses', DriverStatusesController::class)->middleware(['auth', 'api.rate_limit']);

// API для пассажира - активный заказ
Route::get('/api/passenger/active-order', [PassengerOrderController::class, 'activeOrder'])->middleware('auth');

// API для пассажира - история заказов
Route::get('/api/passenger/orders/history', PassengerOrderHistoryController::class)->middleware(['auth', 'api.rate_limit']);

// API для пассажира - отмена заказа
Route::post('/api/passenger/orders/{order}/cancel', [PassengerOrderController::class, 'cancelOrder'])->middleware('auth');

// API для пассажира - оставить отзыв
Route::post('/api/passenger/orders/{order}/review', [ReviewController::class, 'store'])->middleware(['web', 'auth']);

// API для проверки отзыва на заказ
Route::get('/api/passenger/orders/{order}/review/check', [ReviewController::class, 'checkReview'])->middleware(['web', 'auth']);

// API для получения отзывов водителя
Route::get('/api/driver/{driverId}/reviews', [ReviewController::class, 'driverReviews']);

Route::get('/', function () {
    return Inertia::render('Landing');
});

// Страницы приложения - только для пассажиров
Route::middleware(['auth', 'role:passenger'])->group(function () {
    Route::get('/passenger', [PassengerController::class, 'home'])->name('passenger');

    // Создание заказа
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
});

// Страницы приложения - только для водителей
Route::middleware(['auth', 'role:driver'])->group(function () {
    Route::get('/driver', function () {
        return Inertia::render('Driver/Home');
    })->name('driver');

    // API для автомобилей водителя
    Route::prefix('driver')->group(function () {
        Route::get('/cars', [CarController::class, 'index']);
        Route::get('/cars/primary', [CarController::class, 'primary']);
        Route::post('/cars', [CarController::class, 'store']);
        Route::put('/cars/{car}', [CarController::class, 'update']);
        Route::delete('/cars/{car}', [CarController::class, 'destroy']);
        Route::post('/cars/{car}/primary', [CarController::class, 'setPrimary']);
    });
});

// Страницы приложения - только для диспетчеров и админов
Route::middleware(['auth', 'role:dispatcher'])->group(function () {
    Route::get('/dispatcher/orders', [OrderController::class, 'index'])->name('dispatcher.orders');

    Route::get('/dispatcher/orders/cancelled', function () {
        return Inertia::render('Dispatcher/Cancelled');
    })->name('dispatcher.orders.cancelled');

    Route::get('/dispatcher/drivers', function () {
        return Inertia::render('Dispatcher/Drivers');
    })->name('dispatcher.drivers');

    Route::get('/dispatcher/analytics', [AnalyticsController::class, 'dispatcher'])->name('dispatcher.analytics');

    Route::get('/dispatcher/map', function () {
        return Inertia::render('Dispatcher/Map');
    })->name('dispatcher.map');

    Route::get('/dispatcher/reviews', [DispatcherReviewsController::class, 'index'])->name('dispatcher.reviews');

    Route::get('/dispatcher', function () {
        return redirect()->route('dispatcher.orders');
    })->name('dispatcher');
});

// Админ-панель - только для админов
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/users/drivers', [AdminUsersController::class, 'drivers'])->name('admin.users.drivers');
    Route::get('/users/passengers', [AdminUsersController::class, 'passengers'])->name('admin.users.passengers');
    Route::get('/users/dispatchers', [AdminUsersController::class, 'dispatchers'])->name('admin.users.dispatchers');

    // Управление пользователями
    Route::post('/users/toggle-block', [AdminUsersController::class, 'toggleBlock'])->name('admin.users.toggleBlock');
    Route::delete('/users/destroy', [AdminUsersController::class, 'destroy'])->name('admin.users.destroy');

    // Управление автомобилями водителей
    Route::get('/drivers/{driver}/car', [AdminCarsController::class, 'show'])->name('admin.drivers.car');
    Route::post('/drivers/{driver}/car', [AdminCarsController::class, 'store'])->name('admin.drivers.car.store');
    Route::put('/cars/{car}', [AdminCarsController::class, 'update'])->name('admin.cars.update');
    Route::delete('/cars/{car}', [AdminCarsController::class, 'destroy'])->name('admin.cars.destroy');
    Route::post('/cars/{car}/primary', [AdminCarsController::class, 'setPrimary'])->name('admin.cars.primary');

    Route::get('/orders', [AdminOrdersController::class, 'index'])->name('admin.orders');

    // Отменённые заказы
    Route::get('/orders/cancelled', [AdminCancelledOrdersController::class, 'index'])->name('admin.orders.cancelled');

    Route::get('/finance/transactions', [AdminTransactionsController::class, 'index'])->name('admin.finance.transactions');

    Route::get('/finance/payouts', [AdminPayoutsController::class, 'index'])->name('admin.finance.payouts');

    Route::get('/finance/taxes', function () {
        return Inertia::render('Admin/Finance/Taxes');
    })->name('admin.finance.taxes');

    Route::get('/analytics', [AnalyticsController::class, 'admin'])->name('admin.analytics');

    Route::get('/tariffs', [AdminTariffsController::class, 'index'])->name('admin.tariffs');
    Route::post('/tariffs', [AdminTariffsController::class, 'store'])->name('admin.tariffs.store');
    Route::put('/tariffs/{tariff}', [AdminTariffsController::class, 'update'])->name('admin.tariffs.update');
    Route::delete('/tariffs/{tariff}', [AdminTariffsController::class, 'destroy'])->name('admin.tariffs.destroy');
    Route::post('/tariffs/{tariff}/toggle', [AdminTariffsController::class, 'toggleActive'])->name('admin.tariffs.toggle');

    Route::get('/settings', [AdminSettingsController::class, 'index'])->name('admin.settings');
    Route::post('/settings', [AdminSettingsController::class, 'update'])->name('admin.settings.update');

    Route::get('/roles', function () {
        return Inertia::render('Admin/Roles');
    })->name('admin.roles');

    Route::get('/notifications', function () {
        return Inertia::render('Admin/Notifications');
    })->name('admin.notifications');

    Route::get('/database', function () {
        return Inertia::render('Admin/Database');
    })->name('admin.database');

    Route::get('/logs', function () {
        return Inertia::render('Admin/Logs');
    })->name('admin.logs');

    Route::get('/reviews', [AdminReviewsController::class, 'index'])->name('admin.reviews');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
