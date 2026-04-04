<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key_name' => 'app.app_name', 'value' => 'TaxiVezi', 'type' => 'string', 'group_name' => 'general', 'description' => 'Название приложения'],
            ['key_name' => 'app.company_name', 'value' => 'Такси-сервис', 'type' => 'string', 'group_name' => 'general', 'description' => 'Название компании'],
            ['key_name' => 'app.currency', 'value' => 'RUB', 'type' => 'string', 'group_name' => 'general', 'description' => 'Валюта'],
            ['key_name' => 'app.timezone', 'value' => 'Asia/Novosibirsk', 'type' => 'string', 'group_name' => 'general', 'description' => 'Часовой пояс'],
            ['key_name' => 'app.locale', 'value' => 'ru', 'type' => 'string', 'group_name' => 'general', 'description' => 'Язык интерфейса'],

            // API - Время повторения запросов
            ['key_name' => 'api.orders_repeat_time', 'value' => '5000', 'type' => 'number', 'group_name' => 'api', 'description' => 'Время повторения запроса заказов (мс)'],
            ['key_name' => 'api.driver_repeat_time', 'value' => '3000', 'type' => 'number', 'group_name' => 'api', 'description' => 'Время повторения запроса статуса водителя (мс)'],
            ['key_name' => 'api.available_orders_limit', 'value' => '10', 'type' => 'number', 'group_name' => 'api', 'description' => 'Максимальное количество заказов в ответе'],
            ['key_name' => 'api.available_orders_hours', 'value' => '2', 'type' => 'number', 'group_name' => 'api', 'description' => 'Глубина выборки заказов (часы)'],
            ['key_name' => 'api.requests_per_minute', 'value' => '60', 'type' => 'number', 'group_name' => 'api', 'description' => 'Лимит запросов в минуту'],
            ['key_name' => 'api.order_stats_repeat_time', 'value' => '30000', 'type' => 'number', 'group_name' => 'api', 'description' => 'Время обновления статистики на главной (мс)'],

            // Maps
            ['key_name' => 'maps.yandex_maps_api_key', 'value' => '', 'type' => 'string', 'group_name' => 'maps', 'description' => 'API ключ Яндекс.Карт'],
            ['key_name' => 'maps.google_maps_api_key', 'value' => '', 'type' => 'string', 'group_name' => 'maps', 'description' => 'API ключ Google Maps'],
            ['key_name' => 'maps.default_map_center', 'value' => '53.990061,84.746699', 'type' => 'string', 'group_name' => 'maps', 'description' => 'Центр карты по умолчанию'],
            ['key_name' => 'maps.default_map_zoom', 'value' => '15', 'type' => 'number', 'group_name' => 'maps', 'description' => 'Масштаб карты по умолчанию'],

            // Notifications
            ['key_name' => 'notifications.email_notifications', 'value' => 'true', 'type' => 'boolean', 'group_name' => 'notifications', 'description' => 'Отправлять email уведомления'],
            ['key_name' => 'notifications.sms_notifications', 'value' => 'false', 'type' => 'boolean', 'group_name' => 'notifications', 'description' => 'Отправлять SMS уведомления'],
            ['key_name' => 'notifications.push_notifications', 'value' => 'true', 'type' => 'boolean', 'group_name' => 'notifications', 'description' => 'Push-уведомления'],
            ['key_name' => 'notifications.notify_new_order', 'value' => 'true', 'type' => 'boolean', 'group_name' => 'notifications', 'description' => 'Уведомлять о новых заказах'],

            // Security
            ['key_name' => 'security.require_phone_verification', 'value' => 'true', 'type' => 'boolean', 'group_name' => 'security', 'description' => 'Требовать подтверждение телефона'],
            ['key_name' => 'security.require_email_verification', 'value' => 'false', 'type' => 'boolean', 'group_name' => 'security', 'description' => 'Требовать подтверждение email'],
            ['key_name' => 'security.session_timeout', 'value' => '120', 'type' => 'number', 'group_name' => 'security', 'description' => 'Таймаут сессии (минуты)'],
            ['key_name' => 'security.max_login_attempts', 'value' => '5', 'type' => 'number', 'group_name' => 'security', 'description' => 'Макс. попыток входа'],

            // System
            ['key_name' => 'system.maintenance_mode', 'value' => 'false', 'type' => 'boolean', 'group_name' => 'system', 'description' => 'Режим технического обслуживания'],
            ['key_name' => 'system.debug_mode', 'value' => 'false', 'type' => 'boolean', 'group_name' => 'system', 'description' => 'Режим отладки'],
            ['key_name' => 'system.log_level', 'value' => 'error', 'type' => 'string', 'group_name' => 'system', 'description' => 'Уровень логирования'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key_name' => $setting['key_name']],
                $setting
            );
        }

        $this->command->info('Настройки успешно созданы/обновлены!');
    }
}
