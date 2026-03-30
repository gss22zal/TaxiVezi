<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Driver;
use App\Models\Passenger;
use Illuminate\Http\Request;

class AdminReviewsController extends Controller
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

        // Статистика
        $allReviews = Review::whereNotNull('passenger_rating')->get();
        
        $stats = [
            'total' => $allReviews->count(),
            'avgRating' => $allReviews->avg('passenger_rating') ? round($allReviews->avg('passenger_rating'), 1) : 0,
            'fiveStars' => $allReviews->where('passenger_rating', 5)->count(),
            'fourStars' => $allReviews->where('passenger_rating', 4)->count(),
            'threeStars' => $allReviews->where('passenger_rating', 3)->count(),
            'twoStars' => $allReviews->where('passenger_rating', 2)->count(),
            'oneStar' => $allReviews->where('passenger_rating', 1)->count(),
        ];

        return inertia('Admin/Reviews', [
            'reviews' => $reviews,
            'stats' => $stats,
        ]);
    }
}
