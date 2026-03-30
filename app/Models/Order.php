<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends BaseModel
{
    protected $fillable = [
        'order_number',
        'passenger_id',
        'driver_id',
        'dispatcher_id',
        'tariff_id',
        'status',
        'pickup_address',
        'pickup_lat',
        'pickup_lng',
        'dropoff_address',
        'dropoff_lat',
        'dropoff_lng',
        'distance',
        'duration',
        'final_price',
        'base_price',
        'distance_price',
        'time_price',
        'surge_multiplier',
        'payment_method',
        'payment_status',
        'driver_earnings',
        'commission_amount',
        'passenger_name',
        'passenger_phone',
        'notes',
        'driver_notes',
        'cancelled_by',
        'cancellation_reason',
        'accepted_at',
        'arrived_at',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'pickup_lat' => 'decimal:8',
            'pickup_lng' => 'decimal:8',
            'dropoff_lat' => 'decimal:8',
            'dropoff_lng' => 'decimal:8',
            'distance' => 'decimal:2',
            'final_price' => 'decimal:2',
            'base_price' => 'decimal:2',
            'surge_multiplier' => 'decimal:2',
            'driver_earnings' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'accepted_at' => 'datetime',
            'arrived_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Пассажир
     */
    public function passenger(): BelongsTo
    {
        return $this->belongsTo(Passenger::class);
    }

    /**
     * Водитель
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Тариф
     */
    public function tariff(): BelongsTo
    {
        return $this->belongsTo(Tariff::class);
    }

    /**
     * Отзыв на заказ
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }
}
