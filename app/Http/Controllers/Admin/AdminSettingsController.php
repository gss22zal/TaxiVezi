<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminSettingsController extends Controller
{
    /**
     * Отображение страницы настроек
     */
    public function index()
    {
        // Группы настроек
        $groups = [
            'general' => 'Общие настройки',
            'notifications' => 'Уведомления',
            'maps' => 'Карты и геолокация',
            'tariffs' => 'Тарифы и цены',
            'api' => 'API и интеграции',
            'security' => 'Безопасность',
            'system' => 'Система',
        ];

        // Получаем все настройки (используем group_name для группировки)
        $settings = Setting::all()->groupBy('group_name');

        // Значения по умолчанию для новых настроек
        $defaults = $this->getDefaultSettings();

        // Формируем данные для каждой группы
        $data = [];
        foreach ($groups as $group => $title) {
            $groupSettings = $settings->get($group, collect());
            $data[$group] = [
                'title' => $title,
                'settings' => [],
            ];

            // Добавляем существующие настройки (извлекаем короткий ключ без префикса группы)
            foreach ($groupSettings as $setting) {
                // Для группы general ключи могут иметь префикс app.
                if ($group === 'general' && str_starts_with($setting->key_name, 'app.')) {
                    $shortKey = substr($setting->key_name, 4); // убираем 'app.'
                } else {
                    $shortKey = str_replace($group . '.', '', $setting->key_name);
                }
                $data[$group]['settings'][$shortKey] = [
                    'value' => $setting->value,
                    'type' => $setting->type,
                    'description' => $setting->description,
                ];
            }
                
            // Добавляем настройки по умолчанию, если их нет
            if (isset($defaults[$group])) {
                foreach ($defaults[$group] as $key => $config) {
                    if (!isset($data[$group]['settings'][$key])) {
                        $data[$group]['settings'][$key] = [
                            'value' => $config['default'] ?? '',
                            'type' => $config['type'] ?? 'string',
                            'description' => $config['description'] ?? '',
                        ];
                    }
                }
            }
        }

        return Inertia::render('Admin/Settings', [
            'settingsData' => $data,
        ]);
    }

    /**
     * Сохранение настроек
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
        ]);

        $defaults = $this->getDefaultSettings();

        // Специальные ключи, которые должны сохраняться с префиксом app.
        $appPrefixKeys = ['app_name', 'currency', 'timezone', 'locale'];

        foreach ($validated['settings'] as $group => $groupSettings) {
            if (!is_array($groupSettings)) continue;
            
            foreach ($groupSettings as $key => $value) {
                // Определяем полный ключ
                if (in_array($key, $appPrefixKeys)) {
                    $fullKey = 'app.' . $key;
                } else {
                    $fullKey = $group . '.' . $key;
                }
                
                $defaultConfig = $defaults[$group][$key] ?? [];
                $type = $defaultConfig['type'] ?? 'string';
                $description = $defaultConfig['description'] ?? null;

                Setting::set($fullKey, $value, $type, $group, $description);
            }
        }

        return back()->with('success', 'Настройки сохранены');
    }

    /**
     * Настройки по умолчанию
     */
    private function getDefaultSettings(): array
    {
        return [
            'general' => [
                'app_name' => [
                    'type' => 'string',
                    'default' => 'TaxiVezi',
                    'description' => 'Название приложения',
                ],
                'company_name' => [
                    'type' => 'string',
                    'default' => 'Такси-сервис',
                    'description' => 'Название компании',
                ],
                'currency' => [
                    'type' => 'string',
                    'default' => 'RUB',
                    'description' => 'Валюта (RUB, USD, EUR)',
                ],
                'timezone' => [
                    'type' => 'string',
                    'default' => 'Europe/Moscow',
                    'description' => 'Часовой пояс',
                ],
                'locale' => [
                    'type' => 'string',
                    'default' => 'ru',
                    'description' => 'Язык интерфейса',
                ],
            ],
            'notifications' => [
                'email_notifications' => [
                    'type' => 'boolean',
                    'default' => true,
                    'description' => 'Отправлять email уведомления',
                ],
                'sms_notifications' => [
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Отправлять SMS уведомления',
                ],
                'push_notifications' => [
                    'type' => 'boolean',
                    'default' => true,
                    'description' => 'Push-уведомления',
                ],
                'notify_new_order' => [
                    'type' => 'boolean',
                    'default' => true,
                    'description' => 'Уведомлять о новых заказах',
                ],
            ],
            'maps' => [
                'yandex_maps_api_key' => [
                    'type' => 'string',
                    'default' => '',
                    'description' => 'API ключ Яндекс.Карт',
                ],
                'google_maps_api_key' => [
                    'type' => 'string',
                    'default' => '',
                    'description' => 'API ключ Google Maps',
                ],
                'default_map_center' => [
                    'type' => 'string',
                    'default' => '53.990061,84.746699',
                    'description' => 'Центр карты по умолчанию (широта, долгота)',
                ],
                'default_map_zoom' => [
                    'type' => 'number',
                    'default' => 15,
                    'description' => 'Масштаб карты по умолчанию',
                ],
            ],
            'tariffs' => [
                'base_price' => [
                    'type' => 'number',
                    'default' => 50,
                    'description' => 'Базовая цена (руб)',
                ],
                'price_per_km' => [
                    'type' => 'number',
                    'default' => 15,
                    'description' => 'Цена за 1 км (руб)',
                ],
                'price_per_minute' => [
                    'type' => 'number',
                    'default' => 5,
                    'description' => 'Цена за 1 минуту ожидания (руб)',
                ],
                'min_order_amount' => [
                    'type' => 'number',
                    'default' => 100,
                    'description' => 'Минимальная сумма заказа (руб)',
                ],
                'commission_percent' => [
                    'type' => 'number',
                    'default' => 10,
                    'description' => 'Комиссия сервиса (%)',
                ],
            ],
            'api' => [
                'orders_repeat_time' => [
                    'type' => 'number',
                    'default' => 5000,
                    'description' => 'Время повторения запроса заказов (мс)',
                ],
                'driver_repeat_time' => [
                    'type' => 'number',
                    'default' => 3000,
                    'description' => 'Время повторения запроса статуса водителя (мс)',
                ],
                'available_orders_limit' => [
                    'type' => 'number',
                    'default' => 10,
                    'description' => 'Максимальное количество заказов в ответе',
                ],
                'available_orders_hours' => [
                    'type' => 'number',
                    'default' => 2,
                    'description' => 'Глубина выборки заказов (часы)',
                ],
                'requests_per_minute' => [
                    'type' => 'number',
                    'default' => 60,
                    'description' => 'Лимит запросов в минуту',
                ],
                'order_stats_repeat_time' => [
                    'type' => 'number',
                    'default' => 30000,
                    'description' => 'Время обновления статистики на главной (мс)',
                ],
            ],
            'security' => [
                'require_phone_verification' => [
                    'type' => 'boolean',
                    'default' => true,
                    'description' => 'Требовать подтверждение телефона',
                ],
                'require_email_verification' => [
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Требовать подтверждение email',
                ],
                'session_timeout' => [
                    'type' => 'number',
                    'default' => 120,
                    'description' => 'Таймаут сессии (минуты)',
                ],
                'max_login_attempts' => [
                    'type' => 'number',
                    'default' => 5,
                    'description' => 'Макс. попыток входа',
                ],
            ],
            'system' => [
                'maintenance_mode' => [
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Режим технического обслуживания',
                ],
                'debug_mode' => [
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'Режим отладки',
                ],
                'log_level' => [
                    'type' => 'string',
                    'default' => 'error',
                    'description' => 'Уровень логирования (debug, info, warning, error)',
                ],
            ],
        ];
    }
}
