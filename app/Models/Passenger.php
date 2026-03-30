<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Passenger extends BaseModel
{
    protected $fillable = [
        'user_id',
        'balance',
        'bonus_balance',
        'total_rides',
        'total_spent',
        'default_payment_method',
        'home_address',
        'work_address',
        'preferred_car_class',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'bonus_balance' => 'decimal:2',
            'total_spent' => 'decimal:2',
            'total_rides' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'passenger_id');
    }
}
