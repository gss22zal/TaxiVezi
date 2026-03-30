<?php

namespace App\Models;

class Setting extends BaseModel
{
    protected $table = 'settings';
    protected $fillable = ['key_name', 'value', 'type', 'group_name', 'description', 'is_public', 'updated_by'];

    /**
     * Получить значение настройки
     */
    public static function get(string $key, $default = null)
    {
        $setting = static::where('key_name', $key)->first();
        if (!$setting) return $default;

        return static::castValue($setting->value, $setting->type);
    }

    /**
     * Установить значение настройки
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general', ?string $description = null)
    {
        $value = static::encodeValue($value, $type);

        return static::updateOrCreate(
            ['key_name' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group_name' => $group,
                'description' => $description ?? ''
            ]
        );
    }

    /**
     * Получить все настройки группы
     */
    public static function getByGroup(string $group): array
    {
        $settings = static::where('group_name', $group)->get();
        $result = [];
        foreach ($settings as $setting) {
            $result[$setting->key_name] = static::castValue($setting->value, $setting->type);
        }
        return $result;
    }

    /**
     * Преобразование значения в нужный тип
     */
    private static function castValue($value, string $type)
    {
        if ($value === null) return null;

        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'number' => is_numeric($value) ? (float)$value : 0,
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Кодирование значения для хранения
     */
    private static function encodeValue($value, string $type): string
    {
        return match ($type) {
            'json' => json_encode($value),
            'boolean' => $value ? 'true' : 'false',
            default => (string)$value,
        };
    }
}
