<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Формат даты для SQL Server (ISO 8601)
     */
    protected $dateFormat = 'Y-m-d\TH:i:s.v';

    /**
     * Переопределяем метод для корректного форматирования даты
     */
    public function freshTimestamp()
    {
        return \Carbon\Carbon::now()->format($this->dateFormat);
    }

    /**
     * Форматирование даты для хранения в БД
     */
    public function fromDateTime($value)
    {
        return \Carbon\Carbon::parse($value)->format($this->dateFormat);
    }

    protected $fillable = [
        'email',
        'password',
        'role',
        'first_name',
        'last_name',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'created_at' => 'string',
            'updated_at' => 'string',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function driver(): HasOne
    {
        return $this->hasOne(Driver::class);
    }

    public function passenger(): HasOne
    {
        return $this->hasOne(Passenger::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'passenger_id');
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'superadmin']);
    }

    public function isDriver(): bool
    {
        return $this->role === 'driver';
    }

    public function isDispatcher(): bool
    {
        return $this->role === 'dispatcher';
    }

    public function isPassenger(): bool
    {
        return $this->role === 'passenger';
    }

    /**
     * Получить имя роли пользователя
     */
    public function getRoleNameAttribute(): ?string
    {
        $role = $this->role;
        if ($role && is_object($role)) {
            return $role->name;
        }
        return null;
    }
}
