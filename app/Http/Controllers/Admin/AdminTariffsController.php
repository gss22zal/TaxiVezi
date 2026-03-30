<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tariff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminTariffsController extends Controller
{
    /**
     * Проверка админа
     */
    private function checkAdmin()
    {
         $user = Auth::user();
        $role = $user->role ?? $user->role_id ?? null;
        if (!$role || !in_array($role, ['admin', 'super_admin'])) {
            abort(403, 'Доступ запрещён');
        }
    }

    /**
     * Список тарифов
     */
    public function index(Request $request)
    {
        $query = Tariff::query();

        // Поиск по названию
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', "%{$request->search}%");
        }

        // Фильтр по статусу
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        $tariffs = $query->orderBy('id')->paginate(15);

        return Inertia::render('Admin/Tariffs', [
            'tariffs' => $tariffs->items(),
            'pagination' => [
                'current_page' => $tariffs->currentPage(),
                'last_page' => $tariffs->lastPage(),
                'per_page' => $tariffs->perPage(),
                'total' => $tariffs->total(),
            ],
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
            ]
        ]);
    }

    /**
     * Создание тарифа
     */
    public function store(Request $request)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:tariffs,code',
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'price_per_km' => 'required|numeric|min:0',
            'price_per_min' => 'required|numeric|min:0',
            'min_price' => 'required|numeric|min:0',
            'min_distance' => 'nullable|numeric|min:0',
            'min_duration' => 'nullable|integer|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        Tariff::create($validated);

        return back()->with('success', 'Тариф создан');
    }

    /**
     * Обновление тарифа
     */
    public function update(Request $request, Tariff $tariff)
    {
        $this->checkAdmin();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:tariffs,code,' . $tariff->id,
            'description' => 'nullable|string',
            'base_price' => 'required|numeric|min:0',
            'price_per_km' => 'required|numeric|min:0',
            'price_per_min' => 'required|numeric|min:0',
            'min_price' => 'required|numeric|min:0',
            'min_distance' => 'nullable|numeric|min:0',
            'min_duration' => 'nullable|integer|min:0',
            'commission_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ]);

        $tariff->update($validated);

        return back()->with('success', 'Тариф обновлён');
    }

    /**
     * Удаление тарифа
     */
    public function destroy(Tariff $tariff)
    {
        $this->checkAdmin();

        // Проверяем, есть ли заказы с этим тарифом
        if ($tariff->orders()->count() > 0) {
            return back()->with('error', 'Нельзя удалить тариф с заказами');
        }

        $tariff->delete();

        return back()->with('success', 'Тариф удалён');
    }

    /**
     * Переключение статуса
     */
    public function toggleActive(Request $request, Tariff $tariff)
    {
        $this->checkAdmin();

        $tariff->update(['is_active' => !$tariff->is_active]);

        $status = $tariff->is_active ? 'активирован' : 'деактивирован';

        return back()->with('success', "Тариф {$status}");
    }
}
