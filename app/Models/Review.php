<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $table = 'reviews';
    
    protected $fillable = [
        'order_id',
        'passenger_id',
        'driver_id',
        'passenger_rating',
        'driver_rating',
        'passenger_comment',
        'driver_comment',
        'passenger_tags',
        'driver_tags',
        'is_anonymous',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'passenger_tags' => 'array',
        'driver_tags' => 'array',
        'driver_rating' => 'integer',
        'passenger_rating' => 'integer',
        'is_anonymous' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function passenger()
    {
        return $this->belongsTo(Passenger::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
