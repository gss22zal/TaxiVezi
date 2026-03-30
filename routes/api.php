<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\OrderStatsController;
use App\Http\Controllers\Api\ReviewController;


Route::get('/order-stats', OrderStatsController::class)->middleware('web');

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

