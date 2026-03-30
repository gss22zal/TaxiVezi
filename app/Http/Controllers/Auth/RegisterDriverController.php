<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Driver;
use App\Models\Car;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Inertia\Inertia;

class RegisterDriverController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): \Inertia\Response
    {
        return Inertia::render('Auth/RegisterDriver');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            // Данные пользователя
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'required|string|lowercase|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            // Водительское удостоверение
            'license_number' => 'required|string|max:50',
            'license_expiry' => 'required|date',
            // Автомобиль
            'car_brand' => 'required|string|max:255',
            'car_model' => 'required|string|max:255',
            'car_year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'car_color' => 'nullable|string|max:255',
            'car_plate' => 'required|string|max:20',
            'car_region' => 'nullable|string|max:10',
            'car_class' => 'required|string|in:econom,comfort,business,minivan,premium',
        ]);

        // Создаем пользователя
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'driver',
            'is_active' => true,
        ]);

        // Создаем профиль водителя
        $driver = Driver::create([
            'user_id' => $user->id,
            'driver_license_number' => $request->license_number,
            'driver_license_expiry' => $request->license_expiry,
            'rating' => 5.0,
            'total_rides' => 0,
            'total_earnings' => 0,
            'is_online' => false,
            'can_accept_orders' => true,
        ]);

        // Создаем автомобиль
        Car::create([
            'driver_id' => $driver->id,
            'brand' => $request->car_brand,
            'model' => $request->car_model,
            'year' => $request->car_year,
            'color' => $request->car_color,
            'plate_number' => $request->car_plate,
            'region_code' => $request->car_region,
            'car_class' => $request->car_class,
            'is_active' => true,
            'is_primary' => true,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect('/driver');
    }
}
