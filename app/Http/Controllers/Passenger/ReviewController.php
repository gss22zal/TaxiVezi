<?php

namespace App\Http\Controllers\Passenger;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function store(Request $request, Order $order)
    {
        // Проверка: заказ принадлежит текущему пассажиру
        $passenger = Auth::user()->passenger;
        
        if (!$passenger || $order->passenger_id !== $passenger->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        
        // Проверка: заказ завершён и отзыв ещё не оставлен
        if ($order->status !== 'completed') {
            return response()->json(['error' => 'Review can only be submitted for completed orders'], 400);
        }
        
        if (Review::where('order_id', $order->id)->exists()) {
            return response()->json(['error' => 'Review already submitted'], 409);
        }
        
        // Валидация
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50'
        ]);
        
        // Создание отзыва
        $review = DB::transaction(function () use ($order, $passenger, $validated) {
            $review = Review::create([
                'order_id' => $order->id,
                'passenger_id' => $passenger->id,
                'driver_id' => $order->driver_id,
                'passenger_rating' => $validated['rating'],
                'passenger_comment' => $validated['comment'] ?? null,
                'passenger_tags' => json_encode($validated['tags'] ?? [], JSON_UNESCAPED_UNICODE),
            ]);
            
            // Обновление рейтинга водителя
            if ($order->driver_id) {
                $driver = $order->driver;
                $stats = Review::where('driver_id', $driver->id)
                    ->whereNotNull('passenger_rating')
                    ->selectRaw('AVG(passenger_rating) as avg, COUNT(*) as count')
                    ->first();
                
                if ($stats) {
                    $driver->update([
                        'rating' => round($stats->avg, 2),
                        'total_ratings' => $stats->count
                    ]);
                }
            }
            
            return $review;
        });
        
        return response()->json([
            'success' => true,
            'review' => $review
        ], 201);
    }
}