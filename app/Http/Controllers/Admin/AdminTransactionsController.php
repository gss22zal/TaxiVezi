<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTransactionsController extends Controller
{
    public function index(Request $request)
    {
        $query = Transaction::with([
            'user:id,first_name,last_name,role,phone',
            'order:id,order_number,status'
        ])->orderByDesc('created_at');

        // Фильтры
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        $transactions = $query->paginate(20);

        // Статистика
        $stats = [
            'total' => Transaction::count(),
            'totalAmount' => Transaction::successful()->sum('amount'),
            'todayAmount' => Transaction::successful()
                ->whereDate('created_at', today_db())
                ->sum('amount'),
            'byType' => [
                'ride_payment' => Transaction::where('type', 'ride_payment')->successful()->sum('amount'),
                'topup' => Transaction::where('type', 'topup')->successful()->sum('amount'),
                'payout' => Transaction::where('type', 'payout')->successful()->sum('amount'),
                'bonus' => Transaction::where('type', 'bonus')->successful()->sum('amount'),
                'refund' => Transaction::where('type', 'refund')->successful()->sum('amount'),
            ],
            'byStatus' => [
                'success' => Transaction::where('status', 'success')->count(),
                'pending' => Transaction::where('status', 'pending')->count(),
                'failed' => Transaction::where('status', 'failed')->count(),
            ],
        ];

        return inertia('Admin/Finance/Transactions', [
            'transactions' => $transactions,
            'stats' => $stats,
            'filters' => [
                'type' => $request->type ?? 'all',
                'status' => $request->status ?? 'all',
                'search' => $request->search ?? '',
            ],
        ]);
    }
}
