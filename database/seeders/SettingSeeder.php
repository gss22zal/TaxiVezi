<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Setting;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // General
            ['key_name' => 'app.name', 'value' => 'TaxiVezi', 'type' => 'string', 'group_name' => 'general', 'description' => 'Название системы', 'is_public' => true],
            ['key_name' => 'app.currency', 'value' => 'RUB', 'type' => 'string', 'group_name' => 'general', 'description' => 'Валюта по умолчанию', 'is_public' => true],
            ['key_name' => 'app.timezone', 'value' => 'Europe/Moscow', 'type' => 'string', 'group_name' => 'general', 'description' => 'Часовой пояс', 'is_public' => false],
            ['key_name' => 'app.locale', 'value' => 'ru', 'type' => 'string', 'group_name' => 'general', 'description' => 'Язык системы', 'is_public' => true],
            
            // Payment
            ['key_name' => 'payment.commission_default', 'value' => '20', 'type' => 'number', 'group_name' => 'payment', 'description' => 'Комиссия системы по умолчанию %', 'is_public' => false],
            ['key_name' => 'payment.min_payout', 'value' => '1000', 'type' => 'number', 'group_name' => 'payment', 'description' => 'Минимальная сумма выплаты', 'is_public' => false],
            ['key_name' => 'payment.provider', 'value' => 'yookassa', 'type' => 'string', 'group_name' => 'payment', 'description' => 'Платёжный провайдер', 'is_public' => false],
            
            // Map
            ['key_name' => 'map.provider', 'value' => 'yandex', 'type' => 'string', 'group_name' => 'map', 'description' => 'Провайдер карт', 'is_public' => false],
            ['key_name' => 'map.search_radius', 'value' => '5000', 'type' => 'number', 'group_name' => 'map', 'description' => 'Радиус поиска водителей (метры)', 'is_public' => false],
            ['key_name' => 'map.yandex_api_key', 'value' => '', 'type' => 'string', 'group_name' => 'map', 'description' => 'API ключ Яндекс.Карт', 'is_public' => false],
            ['key_name' => 'map.google_api_key', 'value' => '', 'type' => 'string', 'group_name' => 'map', 'description' => 'API ключ Google Maps', 'is_public' => false],
            
            // Notification
            ['key_name' => 'notification.push_enabled', 'value' => '1', 'type' => 'boolean', 'group_name' => 'notification', 'description' => 'Push уведомления включены', 'is_public' => false],
            ['key_name' => 'notification.sms_enabled', 'value' => '1', 'type' => 'boolean', 'group_name' => 'notification', 'description' => 'SMS уведомления включены', 'is_public' => false],
            ['key_name' => 'notification.email_enabled', 'value' => '1', 'type' => 'boolean', 'group_name' => 'notification', 'description' => 'Email уведомления включены', 'is_public' => false],
            
            // Order
            ['key_name' => 'order.search_timeout', 'value' => '300', 'type' => 'number', 'group_name' => 'order', 'description' => 'Время поиска водителя (сек)', 'is_public' => false],
            ['key_name' => 'order.cancel_timeout', 'value' => '1800', 'type' => 'number', 'group_name' => 'order', 'description' => 'Время отмены заказа (сек)', 'is_public' => false],
            
            // API
            ['key_name' => 'api.orders_repeat_time', 'value' => '5000', 'type' => 'number', 'group_name' => 'api', 'description' => 'Время повторения запроса заказов (мс)', 'is_public' => false],
            ['key_name' => 'api.driver_repeat_time', 'value' => '3000', 'type' => 'number', 'group_name' => 'api', 'description' => 'Время повторения запроса статуса водителя (мс)', 'is_public' => false],
            ['key_name' => 'api.available_orders_limit', 'value' => '10', 'type' => 'number', 'group_name' => 'api', 'description' => 'Максимальное количество заказов в ответе', 'is_public' => false],
            ['key_name' => 'api.available_orders_hours', 'value' => '2', 'type' => 'number', 'group_name' => 'api', 'description' => 'Глубина выборки заказов (часы)', 'is_public' => false],
            ['key_name' => 'api.requests_per_minute', 'value' => '60', 'type' => 'number', 'group_name' => 'api', 'description' => 'Лимит запросов в минуту', 'is_public' => false],
            ['key_name' => 'api.order_stats_repeat_time', 'value' => '30000', 'type' => 'number', 'group_name' => 'api', 'description' => 'Время обновления статистики на главной (мс)', 'is_public' => false],
            
            // Driver
            ['key_name' => 'driver.min_rating', 'value' => '4.0', 'type' => 'number', 'group_name' => 'driver', 'description' => 'Минимальный рейтинг водителя', 'is_public' => false],
            ['key_name' => 'driver.auto_accept', 'value' => '0', 'type' => 'boolean', 'group_name' => 'driver', 'description' => 'Автоприём заказов', 'is_public' => false],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key_name' => $setting['key_name']],
                $setting
            );
        }

        $this->command->info('Настройки успешно обновлены!');
    }
}
