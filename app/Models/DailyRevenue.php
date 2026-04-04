<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyRevenue extends Model
{
    use HasFactory;

    protected $table = 'daily_revenues';

    protected $fillable = [
        'date',
        'revenue',
        'orders_count',
        'completed_count',
        'cancelled_count',
    ];

    protected $casts = [
        'date' => 'datetime:Y-m-d',  // Храним как дату, но выводим только дату
        'revenue' => 'decimal:2',
        'orders_count' => 'integer',
        'completed_count' => 'integer',
        'cancelled_count' => 'integer',
    ];

    /**
     * Получить данные за последние N дней
     */
    public static function getLastDays(int $days = 7)
    {
        $timezone = Setting::get('app.timezone', 'Europe/Moscow');
        $now = Carbon::now($timezone);
        
        return static::orderBy('date', 'asc')
            ->where('date', '>=', $now->copy()->subDays($days - 1)->toDateString())
            ->get();
    }

    /**
     * Обновить или создать запись за день
     */
    public static function updateForDate(string $date): self
    {
        // Получаем данные из orders
        $stats = DB::table('orders')
            ->whereDate('completed_at', $date)
            ->where('status', 'completed')
            ->selectRaw('
                COALESCE(SUM(final_price), 0) as revenue,
                COUNT(*) as completed_count
            ')
            ->first();

        $ordersCount = DB::table('orders')
            ->whereDate('created_at', $date)
            ->count();

        $cancelledCount = DB::table('orders')
            ->whereDate('created_at', $date)
            ->where('status', 'cancelled')
            ->count();

        return static::updateOrCreate(
            ['date' => $date],
            [
                'revenue' => $stats->revenue ?? 0,
                'orders_count' => $ordersCount,
                'completed_count' => $stats->completed_count ?? 0,
                'cancelled_count' => $cancelledCount,
            ]
        );
    }
}
