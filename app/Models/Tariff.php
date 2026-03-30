<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class Tariff extends BaseModel
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'base_price',
        'price_per_km',
        'price_per_min',
        'min_price',
        'min_distance',
        'min_duration',
        'commission_rate',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'price_per_km' => 'decimal:2',
            'price_per_min' => 'decimal:2',
            'min_price' => 'decimal:2',
            'min_distance' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Заказы с этим тарифом
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Активные тарифы
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('id');
    }
}
