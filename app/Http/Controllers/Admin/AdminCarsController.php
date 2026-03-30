<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCarsController extends Controller
{
    /**
     * Получить автомобиль водителя
     */
    public function show(Driver $driver)
    {
        $car = Car::where('driver_id', $driver->id)
            ->where('is_primary', true)
            ->first();

        if (!$car) {
            // Если нет основного, берём первый
            $car = Car::where('driver_id', $driver->id)->first();
        }

        // Получаем все автомобили водителя
        $allCars = Car::where('driver_id', $driver->id)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        return response()->json([
            'car' => $car,
            'all_cars' => $allCars,
            'driver' => [
                'id' => $driver->id,
                'name' => $driver->user->first_name . ' ' . $driver->user->last_name
            ]
        ]);
    }

    /**
     * Обновить автомобиль
     */
    public function update(Request $request, Car $car)
    {
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

        $car->update($validated);

        // Если устанавливается как основной, снимаем флаг с других
        if ($car->is_primary) {
            Car::where('driver_id', $car->driver_id)
                ->where('id', '!=', $car->id)
                ->update(['is_primary' => false]);
        }

        return response()->json($car);
    }

    /**
     * Создать автомобиль для водителя
     */
    public function store(Request $request, Driver $driver)
    {
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
        if (($validated['is_primary'] ?? true)) {
            Car::where('driver_id', $driver->id)->update(['is_primary' => false]);
        }

        $car = Car::create(array_merge($validated, [
            'driver_id' => $driver->id,
            'is_active' => true,
        ]));

        return response()->json($car);
    }

    /**
     * Удалить автомобиль
     */
    public function destroy(Car $car)
    {
        $car->delete();
        return response()->json(['success' => true]);
    }

    /**
     * Установить основной автомобиль
     */
    public function setPrimary(Car $car)
    {
        // Снимаем флаг со всех автомобилей водителя
        Car::where('driver_id', $car->driver_id)->update(['is_primary' => false]);
        
        // Устанавливаем основным
        $car->update(['is_primary' => true]);

        return response()->json($car);
    }
}
