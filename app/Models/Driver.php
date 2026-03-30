<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Driver extends BaseModel
{
    protected $fillable = [
        'user_id',
        'license_number',
        'license_expiry',
        'rating',
        'total_trips',
        'total_earnings',
        'is_online',
        'is_available',
        'can_accept_orders',
    ];

    protected function casts(): array
    {
        return [
            'license_expiry' => 'date',
            'is_online' => 'boolean',
            'is_available' => 'boolean',
            'can_accept_orders' => 'boolean',
            'rating' => 'float',
            'total_trips' => 'integer',
            'total_earnings' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function car(): HasOne
    {
        return $this->hasOne(Car::class)->where('is_primary', true);
    }

    public function cars(): HasMany
    {
        return $this->hasMany(Car::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
