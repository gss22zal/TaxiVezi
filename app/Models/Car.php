<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Car extends BaseModel
{
    protected $fillable = [
        'driver_id',
        'brand',
        'model',
        'year',
        'color',
        'plate_number',
        'region_code',
        'car_class',
        'vin_number',
        'insurance_number',
        'insurance_expiry',
        'tech_inspection_expiry',
        'is_active',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'insurance_expiry' => 'date',
            'tech_inspection_expiry' => 'date',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Водитель
     */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    /**
     * Основной автомобиль
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
