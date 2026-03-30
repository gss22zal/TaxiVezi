<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\RegisterPassengerController;
use App\Http\Controllers\Auth\RegisterDriverController;
use App\Http\Controllers\Auth\RegisterDispatcherController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('guest')->group(function () {
    // Общая регистрация (редирект на выбор роли) - для авторизованных редирект по роли
    Route::get('register', function () {
        return redirect()->route('register.choose');
    })->name('register')->middleware('redirect.authenticated');

    // Выбор роли при регистрации
    Route::get('register/role', function () {
        return Inertia::render('Auth/RegisterRole');
    })->name('register.choose');

    // Регистрация пассажира
    Route::get('register/passenger', [RegisterPassengerController::class, 'create'])
        ->name('register.passenger');
    Route::post('register/passenger', [RegisterPassengerController::class, 'store']);

    // Регистрация водителя
    Route::get('register/driver', [RegisterDriverController::class, 'create'])
        ->name('register.driver');
    Route::post('register/driver', [RegisterDriverController::class, 'store']);

    // Регистрация диспетчера
    Route::get('register/dispatcher', [RegisterDispatcherController::class, 'create'])
        ->name('register.dispatcher');
    Route::post('register/dispatcher', [RegisterDispatcherController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login')->middleware('redirect.authenticated');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
