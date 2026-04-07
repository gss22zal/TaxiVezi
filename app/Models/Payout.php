<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $fillable = [
        'payout_number',
        'driver_id',
        'amount',
        'period_start',
        'period_end',
        'total_rides',
        'total_earnings',
        'commission_deducted',
        'status',
        'payment_method',
        'bank_account',
        'bank_name',
        'processed_by',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'total_earnings' => 'decimal:2',
        'commission_deducted' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'processed_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
