<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Setting;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    /**
     * Хранилище для отслеживания запросов пользователей
     */
    protected static array $requestCounts = [];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Получаем лимит из настроек
        $limit = (int) Setting::get('api.requests_per_minute', 60);
        
        // Если лимит = 0 или больше 60 - не ограничиваем
        if ($limit <= 0 || $limit > 60) {
            return $next($request);
        }
        
        $userId = $request->user()?->id ?? $request->ip();
        $key = 'rate_limit_' . $userId;
        $now = time();
        
        // Очищаем старые записи (старше 1 минуты)
        if (isset(self::$requestCounts[$key])) {
            self::$requestCounts[$key] = array_filter(
                self::$requestCounts[$key], 
                function ($timestamp) use ($now) {
                    return $timestamp > $now - 60;
                }
            );
        }
        
        // Инициализируем если нет
        if (!isset(self::$requestCounts[$key])) {
            self::$requestCounts[$key] = [];
        }
        
        // Проверяем лимит
        $count = count(self::$requestCounts[$key]);
        
        if ($count >= $limit) {
            return response()->json([
                'error' => 'Слишком много запросов. Попробуйте позже.',
                'retry_after' => 60 - ($now - min(self::$requestCounts[$key]))
            ], 429);
        }
        
        // Записываем текущий запрос
        self::$requestCounts[$key][] = $now;
        
        return $next($request);
    }
}
