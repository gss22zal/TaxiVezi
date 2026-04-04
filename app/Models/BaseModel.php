<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Базовая модель для SQL Server с правильным форматом даты
 */
class BaseModel extends Model
{
    /**
     * Формат даты для SQL Server (ISO 8601)
     */
    protected $dateFormat = 'Y-m-d\TH:i:s.v';

    /**
     * Переопределяем метод для корректного форматирования даты в UTC
     */
    public function freshTimestamp()
    {
        return Carbon::now('UTC')->format($this->dateFormat);
    }

    /**
     * Форматирование даты для хранения в БД (в UTC)
     */
    public function fromDateTime($value)
    {
        return Carbon::parse($value)->utc()->format($this->dateFormat);
    }
}
