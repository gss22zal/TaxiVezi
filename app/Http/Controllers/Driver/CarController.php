<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Models\Car;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CarController extends Controller
{
    /**
     * Получить автомобили водителя
     */
    public function index(Request $request)
    {
        $driver = $request->user()->driver;
        
        if (!$driver) {
            return response()->json(['error' => 'Водитель не найден'], 404);
        }

        $cars = Car::where('driver_id', $driver->id)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        return response()->json($cars);
    }

    /**
     * Получить основной автомобиль
     */
    public function primary(Request $request)
    {
        $driver = $request->user()->driver;
        
        if (!$driver) {
            return response()->json(['error' => 'Водитель не найден'], 404);
        }

        $car = Car::where('driver_id', $driver->id)
            ->where('is_primary', true)
            ->first();

        return response()->json($car);
    }

    /**
     * Создать автомобиль
     */
    public function store(Request $request)
    {
        $driver = $request->user()->driver;
        
        if (!$driver) {
            return response()->json(['error' => 'Водитель не найден'], 404);
        }

        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:255',
            'plate_number' => 'required|string|max:20',
            'region_code' => 'nullable|string|max:10',
            'car_class' => 'required|string|in:econom,comfort,business,minivan,premium',
            'vin_number' => 'nullable|string|max:17',
            'insurance_number' => 'nullable|string|max:50',
            'insurance_expiry' => 'nullable|date',
            'tech_inspection_expiry' => 'nullable|date',
            'is_primary' => 'boolean',
        ]);

        // Если создаётся основной автомобиль, снимаем флаг с других
        if (($validated['is_primary'] ?? false)) {
            Car::where('driver_id', $driver->id)->update(['is_primary' => false]);
        }

        $car = Car::create([
            ...$validated,
            'driver_id' => $driver->id,
        ]);

        return response()->json($car);
    }

    /**
     * Обновить автомобиль
     */
    public function update(Request $request, Car $car)
    {
        // Проверяем, что автомобиль принадлежит водителю
        $driver = $request->user()->driver;
        
        if (!$driver || $car->driver_id !== $driver->id) {
            return response()->json(['error' => 'Доступ запрещён'], 403);
        }

        $validated = $request->validate([
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'year' => 'nullable|integer|min:1990|max:' . (date('Y') + 1),
            'color' => 'nullable|string|max:255',
            'plate_number' => 'required|string|max:20',
            'region_code' => 'nullable|string|max:10',
            'car_class' => 'required|string|in:econom,comfort,business,minivan,premium',
            'vin_number' => 'nullable|string|max:17',
            'insurance_number' => 'nullable|string|max:50',
            'insurance_expiry' => 'nullable|date',
            'tech_inspection_expiry' => 'nullable|date',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
        ]);

        // Если устанавливается как основной, снимаем флаг с других
        if (($validated['is_primary'] ?? false) && !$car->is_primary) {
            Car::where('driver_id', $driver->id)
                ->where('id', '!=', $car->id)
                ->update(['is_primary' => false]);
        }

        $car->update($validated);

        return response()->json($car);
    }

    /**
     * Удалить автомобиль
     */
    public function destroy(Request $request, Car $car)
    {
        $driver = $request->user()->driver;
        
        if (!$driver || $car->driver_id !== $driver->id) {
            return response()->json(['error' => 'Доступ запрещён'], 403);
        }

        $car->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Установить основной автомобиль
     */
    public function setPrimary(Request $request, Car $car)
    {
        $driver = $request->user()->driver;
        
        if (!$driver || $car->driver_id !== $driver->id) {
            return response()->json(['error' => 'Доступ запрещён'], 403);
        }

        // Снимаем флаг со всех автомобилей
        Car::where('driver_id', $driver->id)->update(['is_primary' => false]);
        
        // Устанавливаем основным
        $car->update(['is_primary' => true]);

        return response()->json($car);
    }
}
