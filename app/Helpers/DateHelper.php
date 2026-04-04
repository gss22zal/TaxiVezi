<?php

use Illuminate\Support\Facades\DB;
use App\Models\Setting;
use Carbon\Carbon;

/**
 * Хелпер для работы с датами в SQL Server
 */
if (!function_exists('now_db')) {
    /**
     * Возвращает текущую дату/время для SQL Server
     * С учётом часового пояса из настроек
     */
    function now_db(): string
    {
        $timezone = Setting::get('app.timezone', 'Europe/Moscow');
        return Carbon::now($timezone)->format('Y-m-d\TH:i:s.v');
    }
}

if (!function_exists('getdate_db')) {
    /**
     * Возвращает текущую дату/время SQL Server через GETDATE()
     * Внимание: GETDATE() не учитывает часовой пояс!
     * Используйте now_db() для правильного времени
     */
    function getdate_db(): \Illuminate\Database\Query\Expression
    {
        return DB::raw("GETDATE()");
    }
}

if (!function_exists('date_add_db')) {
    /**
     * Добавляет интервал к дате в SQL Server
     * 
     * @param string $dateColumn название колонки или дата
     * @param int $amount количество единиц
     * @param string $interval MINUTE, HOUR, DAY, MONTH, YEAR
     * @return \Illuminate\Database\Query\Expression
     */
    function date_add_db(string $dateColumn, int $amount, string $interval = 'MINUTE'): \Illuminate\Database\Query\Expression
    {
        $interval = strtoupper($interval);
        $validIntervals = ['MINUTE', 'HOUR', 'DAY', 'MONTH', 'YEAR'];
        
        if (!in_array($interval, $validIntervals)) {
            $interval = 'MINUTE';
        }
        
        return DB::raw("DATEADD({$interval}, {$amount}, {$dateColumn})");
    }
}

if (!function_exists('today_db')) {
    /**
     * Возвращает текущую дату для SQL Server (без времени)
     * С учётом часового пояса из настроек
     */
    function today_db(): string
    {
        $timezone = Setting::get('app.timezone', 'Europe/Moscow');
        return Carbon::now($timezone)->format('Y-m-d');
    }
}

if (!function_exists('format_date_db')) {
    /**
     * Форматирует дату для SQL Server
     * 
     * @param mixed $date дата (Carbon, DateTime, строка)
     * @param string $format формат
     * @return string
     */
    function format_date_db($date, string $format = 'Y-m-d\TH:i:s.v'): string
    {
        return Carbon::parse($date)->format($format);
    }
}

if (!function_exists('parse_date_from_db')) {
    /**
     * Парсит дату из SQL Server в Carbon
     * 
     * @param mixed $value значение из БД
     * @return Carbon\Carbon|null
     */
    function parse_date_from_db($value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }
        
        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }
}

if (!function_exists('where_date_today')) {
    /**
     * Добавляет условие WHERE для даты сегодня
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column название колонки
     * @return \Illuminate\Database\Eloquent\Builder
     */
    function where_date_today($query, string $column = 'created_at')
    {
        return $query->whereDate($column, Carbon::now(config('app.timezone'))->toDateString());
    }
}

if (!function_exists('where_date_between')) {
    /**
     * Добавляет условие WHERE для диапазона дат
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $column название колонки
     * @param string|Carbon $startDate начальная дата
     * @param string|Carbon $endDate конечная дата
     * @return \Illuminate\Database\Eloquent\Builder
     */
    function where_date_between($query, string $column, $startDate, $endDate)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        
        return $query->whereBetween($column, [$start, $end]);
    }
}
