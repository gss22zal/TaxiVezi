<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminPayoutsController extends Controller
{
    public function index(Request $request)
    {
        $query = Payout::with([
            'driver:id,user_id',
            'driver.user:id,first_name,last_name,phone',
        ])->orderByDesc('created_at');

        // Фильтры
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payout_number', 'like', "%{$search}%")
                  ->orWhereHas('driver.user', function ($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $payouts = $query->paginate(20);

        // Статистика
        $stats = [
            'total' => Payout::count(),
            'totalAmount' => Payout::paid()->sum('amount'),
            'pendingAmount' => Payout::pending()->sum('amount'),
            'byStatus' => [
                'paid' => Payout::where('status', 'paid')->count(),
                'pending' => Payout::where('status', 'pending')->count(),
                'cancelled' => Payout::where('status', 'cancelled')->count(),
            ],
            'totalRides' => Payout::sum('total_rides'),
        ];

        return inertia('Admin/Finance/Payouts', [
            'payouts' => $payouts,
            'stats' => $stats,
            'filters' => [
                'status' => $request->status ?? 'all',
                'search' => $request->search ?? '',
            ],
        ]);
    }
}
