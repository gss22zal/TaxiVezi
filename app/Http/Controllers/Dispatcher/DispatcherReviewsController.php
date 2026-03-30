<?php

namespace App\Http\Controllers\Dispatcher;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class DispatcherReviewsController extends Controller
{
    public function index()
    {
        $reviews = Review::with([
            'driver:id,user_id',
            'driver.user:id,first_name,last_name',
            'passenger:id,user_id',
            'passenger.user:id,first_name,last_name',
            'order:id,order_number,status,final_price',
        ])
            ->orderByDesc('created_at')
            ->limit(100)
            ->get();

        $allReviews = Review::whereNotNull('passenger_rating')->get();
        
        $stats = [
            'total' => $allReviews->count(),
            'avgRating' => $allReviews->avg('passenger_rating') ? round($allReviews->avg('passenger_rating'), 1) : 0,
        ];

        return inertia('Dispatcher/Reviews', [
            'reviews' => $reviews,
            'stats' => $stats,
        ]);
    }
}
