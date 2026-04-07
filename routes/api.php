<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderStatsController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PassengerOrderHistoryController;
use App\Http\Controllers\Api\PassengerOrderHideController;
use App\Http\Controllers\Api\UpdateOrderStatusController;


Route::get('/order-stats', OrderStatsController::class)->middleware('web');

// Смена статуса заказа (диспетчер/админ)
Route::middleware(['web', 'auth'])->group(function () {
    Route::put('/orders/{order}/status', UpdateOrderStatusController::class);
});

// Роуты для отзывов
Route::middleware(['web', 'auth'])->group(function () {
    // Пассажир оставляет отзыв на водителя
    Route::post('/review', [ReviewController::class, 'store']);
    // Проверить отзыв пассажира
    Route::get('/review/check/{orderId}', [ReviewController::class, 'checkReview']);
    // Получить отзывы водителя
    Route::get('/reviews/driver/{driverId}', [ReviewController::class, 'driverReviews']);

    // Водитель оставляет отзыв на пассажира
    Route::post('/review/driver', [ReviewController::class, 'driverReviewStore']);
    // Проверить отзыв водителя
    Route::get('/review/driver/check/{orderId}', [ReviewController::class, 'checkDriverReview']);
});

// Пассажир - история заказов
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/passenger/orders/history', PassengerOrderHistoryController::class);
    Route::post('/passenger/orders/{order}/hide', PassengerOrderHideController::class);
});

// Водитель - история заказов
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/driver/orders/history', \App\Http\Controllers\Api\DriverOrderHistoryController::class);
    Route::post('/driver/orders/{order}/hide', \App\Http\Controllers\Api\DriverOrderHideController::class);
});

