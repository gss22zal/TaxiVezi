<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Order;
use App\Models\Passenger;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    /**
     * Оставить отзыв на водителя (от пассажира) после поездки
     */
    public function store(Request $request)
    {
        // Восстановление авторизации если она потеряна
        $user = Auth::user();
        if (!$user) {
            $user = $request->user();
        }
        
        Log::info('Review store: DEBUG', [
            'user' => $user ? $user->id . ' (' . $user->role . ')' : 'NULL',
        ]);

        if (!$user || $user->role !== 'passenger') {
            return response()->json(['message' => 'Доступ запрещен', 'debug' => 'user: ' . ($user ? $user->id : 'null')], 403);
        }

        $validated = $request->validate([
            'order_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
        ]);

        $passenger = Passenger::where('user_id', $user->id)->first();
        
        Log::info('Review store: passenger', [
            'passenger_id' => $passenger?->id,
        ]);

        if (!$passenger) {
            return response()->json(['message' => 'Профиль пассажира не найден'], 404);
        }

        $order = Order::find($validated['order_id']);
        if (!$order) {
            return response()->json(['message' => 'Заказ не найден'], 404);
        }

        Log::info('Review store: order check', [
            'order_id' => $order->id,
            'order_passenger_id' => $order->passenger_id,
            'passenger_id' => $passenger->id,
        ]);

        if ((string)$order->passenger_id !== (string)$passenger->id) {
            Log::warning('Review access denied', [
                'user_id' => $user->id,
                'passenger_id' => $passenger->id,
                'order_passenger_id' => $order->passenger_id,
            ]);
            return response()->json(['message' => 'Заказ не принадлежит вам'], 403);
        }

        if ($order->status !== 'completed') {
            return response()->json([
                'message' => 'Можно оставить отзыв только на завершённый заказ',
                'current_status' => $order->status
            ], 400);
        }

        // Проверяем что пассажир ещё не оставлял отзыв (именно passenger_rating, не просто наличие записи)
        $existingReview = Review::where('order_id', $order->id)
            ->where('passenger_id', $passenger->id)
            ->whereNotNull('passenger_rating')
            ->first();

        if ($existingReview) {
            return response()->json(['message' => 'Отзыв уже оставлен'], 409);
        }

        // Ищем существующую запись (может быть от водителя)
        $review = Review::where('order_id', $order->id)->first();

        $now = date('Y-m-d\TH:i:s');

        if ($review) {
            // Обновляем существующую запись, добавляя данные пассажира
            DB::table('reviews')
                ->where('id', $review->id)
                ->update([
                    'passenger_id' => $passenger->id,
                    'passenger_rating' => (int) $validated['rating'],
                    'passenger_comment' => $validated['comment'] ?? null,
                    'passenger_tags' => !empty($validated['tags'])
                        ? json_encode($validated['tags'], JSON_UNESCAPED_UNICODE)
                        : null,
                    'updated_at' => DB::raw("CAST('$now' AS DATETIME2)"),
                ]);
            $reviewId = $review->id;
            Log::info('Passenger review updated existing record', [
                'review_id' => $reviewId,
                'order_id' => $order->id,
            ]);
        } else {
            // Создаём новую запись
            $reviewId = DB::table('reviews')->insertGetId([
                'order_id' => $order->id,
                'passenger_id' => $passenger->id,
                'driver_id' => $order->driver_id,
                'passenger_rating' => (int) $validated['rating'],
                'passenger_comment' => $validated['comment'] ?? null,
                'passenger_tags' => !empty($validated['tags']) 
                    ? json_encode($validated['tags'], JSON_UNESCAPED_UNICODE)
                    : null,
                'driver_tags' => null,
                'is_anonymous' => false,
                'created_at' => DB::raw("CAST('$now' AS DATETIME2)"),
                'updated_at' => DB::raw("CAST('$now' AS DATETIME2)"),
            ]);
            Log::info('Passenger review created new record', [
                'review_id' => $reviewId,
                'order_id' => $order->id,
            ]);
        }

        // Обновляем рейтинг водителя
        if ($order->driver_id) {
            $driverReviews = Review::where('driver_id', $order->driver_id)
                ->whereNotNull('passenger_rating')
                ->get();

            $avgRating = $driverReviews->avg('passenger_rating');
            
            DB::table('drivers')
                ->where('id', $order->driver_id)
                ->update([
                    'rating' => round($avgRating, 2),
                    'total_ratings' => $driverReviews->count(),
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Спасибо за отзыв!',
            'review' => [
                'id' => $reviewId,
                'rating' => (int) $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'tags' => $validated['tags'] ?? []
            ],
        ], 201);
    }

    /**
     * Получить отзывы водителя
     */
    public function driverReviews(Request $request, int $driverId)
    {
        $reviews = Review::where('driver_id', $driverId)
            ->whereNotNull('passenger_rating')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json(['reviews' => $reviews]);
    }

    /**
     * Проверить, оставил ли пассажир отзыв на заказ
     */
    public function checkReview(Request $request, int $orderId)
    {
        // DEBUG: проверим что приходит
        $user = Auth::user();

        // Если нет авторизации - пробуем через session
        if (!$user) {
            $user = $request->user();
        }

        // Пробуем через разные способы
        if (!$user && $request->session()->has('auth_password_hash')) {
            // Пробуем залогинить из сессии
            $user = \App\Models\User::find($request->session()->get('login_web_59ba36addc2b2f9401580f014c7f58ea4e30989d'));
        }

        Log::info('checkReview: DEBUG', [
            'order_id' => $orderId,
            'auth_user' => $user ? $user->id . ' (' . $user->role . ')' : 'NULL',
            'session_id' => $request->session()->getId(),
            'cookies' => $request->cookies->keys(),
        ]);

        if (!$user || $user->role !== 'passenger') {
            return response()->json(['message' => 'Доступ запрещен', 'debug' => 'user: ' . ($user ? $user->id : 'null')], 403);
        }

        $passenger = Passenger::where('user_id', $user->id)->first();

        Log::info('checkReview: passenger', [
            'passenger_id' => $passenger?->id,
            'passenger_user_id' => $passenger?->user_id,
        ]);

        if (!$passenger) {
            Log::warning('checkReview: passenger profile not found', ['user_id' => $user->id]);
            return response()->json(['message' => 'Профиль пассажира не найден'], 404);
        }

        $order = Order::find($orderId);

        Log::info('checkReview: order', [
            'order_passenger_id' => $order?->passenger_id,
            'order_status' => $order?->status,
        ]);

        if (!$order) {
            return response()->json(['message' => 'Заказ не найден'], 404);
        }

        // Разрешаем проверку если passenger_id совпадает
        $canAccess = (string)$order->passenger_id === (string)$passenger->id;

        Log::info('checkReview: access check', [
            'can_access' => $canAccess,
            'order_passenger_id' => (string)$order->passenger_id,
            'current_passenger_id' => (string)$passenger->id,
        ]);

        if (!$canAccess) {
            Log::warning('checkReview: access denied', [
                'user_id' => $user->id,
                'passenger_id' => $passenger->id,
                'order_passenger_id' => $order->passenger_id,
                'order_id' => $orderId,
            ]);
            return response()->json(['message' => 'Заказ не принадлежит вам'], 403);
        }

        // Ищем именно отзыв пассажира (с passenger_rating)
        $review = Review::where('order_id', $orderId)
            ->where('passenger_id', $passenger->id)
            ->whereNotNull('passenger_rating')
            ->first();

        return response()->json([
            'has_review' => $review !== null,
            'review' => $review ? [
                'id' => $review->id,
                'rating' => $review->passenger_rating,
                'comment' => $review->passenger_comment,
            ] : null,
        ]);
    }

    /**
     * Оставить отзыв на пассажира (от водителя) после поездки
     */
    public function driverReviewStore(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            $user = $request->user();
        }

        Log::info('Driver review: DEBUG', [
            'user' => $user ? $user->id . ' (' . $user->role . ')' : 'NULL',
        ]);

        if (!$user || $user->role !== 'driver') {
            return response()->json(['message' => 'Доступ запрещен', 'debug' => 'user: ' . ($user ? $user->id : 'null')], 403);
        }

        $validated = $request->validate([
            'order_id' => 'required|integer',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
            'tags' => 'nullable|array',
        ]);

        $driver = Driver::where('user_id', $user->id)->first();

        Log::info('Driver review: driver', [
            'driver_id' => $driver?->id,
        ]);

        if (!$driver) {
            return response()->json(['message' => 'Профиль водителя не найден'], 404);
        }

        $order = Order::find($validated['order_id']);
        if (!$order) {
            return response()->json(['message' => 'Заказ не найден'], 404);
        }

        Log::info('Driver review: order check', [
            'order_id' => $order->id,
            'order_driver_id' => $order->driver_id,
            'driver_id' => $driver->id,
        ]);

        // Проверяем что водитель выполнял этот заказ
        if ((string)$order->driver_id !== (string)$driver->id) {
            Log::warning('Driver review access denied', [
                'user_id' => $user->id,
                'driver_id' => $driver->id,
                'order_driver_id' => $order->driver_id,
            ]);
            return response()->json(['message' => 'Заказ не принадлежит вам'], 403);
        }

        // Проверяем что заказ завершён
        if ($order->status !== 'completed') {
            return response()->json([
                'message' => 'Можно оставить отзыв только на завершённый заказ',
                'current_status' => $order->status
            ], 400);
        }

        // Проверяем что отзыв от водителя ещё не оставлен (именно driver_rating)
        $existingReview = Review::where('order_id', $order->id)
            ->where('driver_id', $driver->id)
            ->whereNotNull('driver_rating')
            ->first();

        if ($existingReview) {
            return response()->json(['message' => 'Отзыв уже оставлен'], 409);
        }

        // Ищем существующую запись (может быть от пассажира)
        $review = Review::where('order_id', $order->id)->first();

        $now = date('Y-m-d\TH:i:s');

        if ($review) {
            // Обновляем существующую запись, добавляя данные водителя
            DB::table('reviews')
                ->where('id', $review->id)
                ->update([
                    'driver_id' => $driver->id,
                    'driver_rating' => (int) $validated['rating'],
                    'driver_comment' => $validated['comment'] ?? null,
                    'driver_tags' => !empty($validated['tags'])
                        ? json_encode($validated['tags'], JSON_UNESCAPED_UNICODE)
                        : null,
                    'updated_at' => DB::raw("CAST('$now' AS DATETIME2)"),
                ]);
            $reviewId = $review->id;
            Log::info('Driver review updated existing record', [
                'review_id' => $reviewId,
                'order_id' => $order->id,
            ]);
        } else {
            // Создаём новую запись
            $reviewId = DB::table('reviews')->insertGetId([
                'order_id' => $order->id,
                'passenger_id' => $order->passenger_id,
                'driver_id' => $driver->id,
                'passenger_rating' => null,
                'driver_rating' => (int) $validated['rating'],
                'passenger_comment' => null,
                'driver_comment' => $validated['comment'] ?? null,
                'passenger_tags' => null,
                'driver_tags' => !empty($validated['tags'])
                    ? json_encode($validated['tags'], JSON_UNESCAPED_UNICODE)
                    : null,
                'is_anonymous' => false,
                'created_at' => DB::raw("CAST('$now' AS DATETIME2)"),
                'updated_at' => DB::raw("CAST('$now' AS DATETIME2)"),
            ]);
            Log::info('Driver review created new record', [
                'review_id' => $reviewId,
                'order_id' => $order->id,
            ]);
        }

        // Обновляем рейтинг пассажира
        if ($order->passenger_id) {
            $passengerReviews = Review::where('passenger_id', $order->passenger_id)
                ->whereNotNull('driver_rating')
                ->get();

            $avgRating = $passengerReviews->avg('driver_rating');

            DB::table('passengers')
                ->where('id', $order->passenger_id)
                ->update([
                    'rating' => round($avgRating, 2),
                    'total_ratings' => $passengerReviews->count(),
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Спасибо за отзыв!',
            'review' => [
                'id' => $reviewId,
                'rating' => (int) $validated['rating'],
                'comment' => $validated['comment'] ?? null,
                'tags' => $validated['tags'] ?? []
            ],
        ], 201);
    }

    /**
     * Проверить, оставил ли водитель отзыв на заказ
     */
    public function checkDriverReview(Request $request, int $orderId)
    {
        $user = Auth::user();
        if (!$user) {
            $user = $request->user();
        }

        if (!$user || $user->role !== 'driver') {
            return response()->json(['message' => 'Доступ запрещен'], 403);
        }

        $driver = Driver::where('user_id', $user->id)->first();
        if (!$driver) {
            return response()->json(['message' => 'Профиль водителя не найден'], 404);
        }

        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Заказ не найден'], 404);
        }

        // Проверяем что водитель выполнял этот заказ
        if ((string)$order->driver_id !== (string)$driver->id) {
            return response()->json(['message' => 'Заказ не принадлежит вам'], 403);
        }

        $review = Review::where('order_id', $orderId)
            ->where('driver_id', $driver->id)
            ->first();

        return response()->json([
            'has_review' => $review !== null,
            'review' => $review ? [
                'id' => $review->id,
                'rating' => $review->driver_rating,
                'comment' => $review->driver_comment,
            ] : null,
        ]);
    }
}