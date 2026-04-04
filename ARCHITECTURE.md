# 🏗️ ARCHITECTURE.md — TaxiVezi

**Такси-диспетчерская система нового поколения**

---

## 📋 Содержание

1. [Обзор проекта](#обзор-проекта)
2. [Технологический стек](#технологический-стек)
3. [Архитектура системы](#архитектура-системы)
4. [Структура проекта](#структура-проекта)
5. [База данных](#база-данных)
6. [Аутентификация и авторизация](#аутентификация-и-авторизация)
7. [Контроллеры и маршруты](#контроллеры-и-маршруты)
8. [Frontend архитектура](#frontend-архитектура)
9. [Безопасность](#безопасность)
10. [Развёртывание](#развёртывание)

---

## 📖 Обзор проекта

**TaxiVezi** — это комплексная платформа для управления службой такси, включающая 4 роли пользователей:

| Роль | Описание | Доступ |
|------|----------|--------|
| **Пассажир** | Заказ поездок, отслеживание, оплата | Web-приложение |
| **Водитель** | Приём заказов, навигация, статистика | Web-приложение |
| **Диспетчер** | Управление заказами, мониторинг водителей | Админ-панель |
| **Администратор** | Полный контроль, аналитика, настройки | Админ-панель |

### Ключевые возможности

- ✅ Создание и управление заказами в реальном времени
- ✅ Отслеживание местоположения водителей
- ✅ Динамическое ценообразование (surge pricing)
- ✅ Система рейтингов и отзывов
- ✅ Финансовая отчётность и выплаты
- ✅ Мультиролевая авторизация
- ✅ Аналитика и дашборды

---

## 🛠️ Технологический стек

### Backend

| Компонент | Технология | Версия |
|-----------|-----------|--------|
| **Фреймворк** | Laravel | 12.x |
| **Язык** | PHP | 8.4 |
| **База данных** | Microsoft SQL Server | 2022 |
| **Кэш/Очереди** | Redis | 7.x |
| **Аутентификация** | Laravel Breeze + Session | - |
| **Real-time** | Laravel Reverb / Pusher | - |

### Frontend

| Компонент | Технология | Версия |
|-----------|-----------|--------|
| **Фреймворк** | Vue.js | 3.x |
| **Сборщик** | Vite | 7.x |
| **Мост к Laravel** | Inertia.js | 2.x |
| **Стили** | TailwindCSS | 3.x |
| **Иконки** | Heroicons | 2.x |
| **Карты** | Яндекс.Карты / Google Maps | - |

### Инфраструктура

| Компонент | Технология |
|-----------|-----------|
| **Web-сервер** | Nginx |
| **PHP-FPM** | 8.4 |
| **ОС** | Ubuntu 22.04 LTS / Windows Server |
| **CI/CD** | GitHub Actions |

---

## 🏛️ Архитектура системы

```
┌──────────────────────────────────────────────────────────┐
│                         КЛИЕНТЫ                          │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │Пассажир  │  │ Водитель │  │Диспетчер │  │  Админ   │  │
│  │  Vue SPA │  │  Vue SPA │  │  Vue SPA │  │  Vue SPA │  │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘  │
└───────┼─────────────┼─────────────┼─────────────┼────────┘
        │             │             │             │
        └─────────────┴──────┬──────┴─────────────┘
                             │
                    ┌────────▼────────┐
                    │   Nginx + SSL   │
                    │  (Reverse Proxy)│
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │   Laravel 12    │
                    │   Application   │
                    └────────┬────────┘
                             │
        ┌────────────────────┼────────────────────┐
        │                    │                    │
┌───────▼───────┐   ┌───────▼───────┐   ┌───────▼───────┐
│   SQL Server  │   │    Redis      │   │   File Storage│
│   Database    │   │   Cache/Queue │   │   (S3/Local)  │
└───────────────┘   └───────────────┘   └───────────────┘
```

### Слои архитектуры

| Слой | Ответственность | Компоненты |
|------|----------------|------------|
| **Presentation** | UI, маршрутизация | Vue компоненты, Inertia |
| **Application** | Бизнес-логика | Контроллеры, Form Requests |
| **Domain** | Модели, правила | Eloquent Models, Events |
| **Infrastructure** | Данные, внешние сервисы | БД, кэш, API |

---

## 📁 Структура проекта

```
TaxiVezi/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Controller.php              # Базовый контроллер
│   │   │   ├── AnalyticsController.php     # Аналитика (admin/dispatcher)
│   │   │   ├── OrderController.php         # Управление заказами
│   │   │   ├── PassengerController.php     # Панель пассажира
│   │   │   ├── ProfileController.php       # Профиль пользователя
│   │   │   ├── Admin/                      # Админ-контроллеры
│   │   │   │   ├── AdminUsersController.php
│   │   │   │   ├── AdminOrdersController.php
│   │   │   │   ├── AdminCancelledOrdersController.php
│   │   │   │   ├── AdminTariffsController.php
│   │   │   │   ├── AdminCarsController.php
│   │   │   │   └── AdminSettingsController.php
│   │   │   ├── Api/                        # API контроллеры
│   │   │   │   ├── AvailableOrdersController.php
│   │   │   │   ├── AcceptOrderController.php
│   │   │   │   ├── StartTripController.php
│   │   │   │   ├── ArrivedAtCustomerController.php
│   │   │   │   ├── CompleteOrderController.php
│   │   │   │   ├── DriverCancelOrderController.php
│   │   │   │   ├── DriverStatusController.php
│   │   │   │   ├── DriverStatusesController.php
│   │   │   │   ├── DriverActiveOrderController.php
│   │   │   │   ├── DriverProfileController.php
│   │   │   │   ├── PassengerOrderController.php
│   │   │   │   ├── DispatcherOrdersController.php
│   │   │   │   ├── DispatcherCancelledOrdersController.php
│   │   │   │   └── OrderStatsController.php
│   │   │   ├── Auth/                       # Аутентификация
│   │   │   │   ├── AuthenticatedSessionController.php
│   │   │   │   ├── RegisteredUserController.php
│   │   │   │   ├── RegisterPassengerController.php
│   │   │   │   ├── RegisterDriverController.php
│   │   │   │   ├── RegisterDispatcherController.php
│   │   │   │   └── ... (Breeze контроллеры)
│   │   │   └── Driver/
│   │   │       └── CarController.php       # Управление авто водителя
│   │   ├── Middleware/
│   │   │   ├── HandleInertiaRequests.php   # Shared props для Inertia
│   │   │   └── RoleCheck.php               # Проверка роли
│   │   └── Kernel.php
│   ├── Models/
│   │   ├── BaseModel.php                   # Базовая модель
│   │   ├── User.php                        # Пользователь
│   │   ├── Order.php                       # Заказ
│   │   ├── Driver.php                      # Водитель
│   │   ├── Passenger.php                   # Пассажир
│   │   ├── Car.php                         # Автомобиль
│   │   ├── Tariff.php                      # Тариф
│   │   ├── Role.php                        # Роль
│   │   ├── Setting.php                     # Настройки системы
│   │   └── ...
│   └── Providers/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   │   ├── DatabaseSeeder.php
│   │   ├── OrdersWeekSeeder.php
│   │   └── ...
│   └── factories/
├── public/
├── resources/
│   ├── js/
│   │   ├── app.js                          # Точка входа Vue
│   │   ├── bootstrap.js
│   │   ├── Components/                     # Переиспользуемые компоненты
│   │   ├── composables/                    # Vue composables
│   │   ├── Layouts/
│   │   │   ├── AdminLayout.vue
│   │   │   ├── AuthenticatedLayout.vue
│   │   │   ├── DispatcherLayout.vue
│   │   │   ├── GuestLayout.vue
│   │   │   └── MainLayout.vue
│   │   └── Pages/
│   │       ├── Landing.vue                 # Главная (лендинг)
│   │       ├── Dashboard.vue               # Дашборд авторизованный
│   │       ├── Welcome.vue
│   │       ├── Auth/                       # Страницы авторизации
│   │       ├── Admin/
│   │       │   ├── Dashboard.vue
│   │       │   ├── Analytics.vue
│   │       │   ├── Settings.vue
│   │       │   ├── Tariffs.vue
│   │       │   ├── Roles.vue
│   │       │   ├── Users/
│   │       │   ├── Orders/
│   │       │   ├── Finance/
│   │       │   ├── Database.vue
│   │       │   ├── Logs.vue
│   │       │   └── Notifications.vue
│   │       ├── Dispatcher/
│   │       │   ├── Orders.vue
│   │       │   ├── Drivers.vue
│   │       │   ├── Analytics.vue
│   │       │   ├── Map.vue
│   │       │   └── Cancelled.vue
│   │       ├── Driver/
│   │       │   └── Home.vue
│   │       ├── Passenger/
│   │       │   └── Home.vue
│   │       └── Profile/
│   └── css/
├── routes/
│   ├── web.php                             # Маршруты приложения
│   └── auth.php                            # Маршруты аутентификации
├── storage/
├── tests/
├── .env
├── .env.example
├── composer.json
├── package.json
├── vite.config.js
└── ARCHITECTURE.md
```

---

## 🗄️ База данных

### Основные таблицы

| Таблица | Описание | Ключевые поля |
|---------|----------|---------------|
| `users` | Пользователи системы | id, name, email, password, role, is_active, phone, avatar |
| `passengers` | Профили пассажиров | id, user_id, balance, bonus_balance, total_rides, total_spent, rating |
| `drivers` | Профили водителей | id, user_id, driver_license_number, rating, status, current_lat, current_lng, is_online, can_accept_orders |
| `cars` | Автомобили | id, driver_id, brand, model, year, plate_number, car_class, color, is_primary |
| `orders` | Заказы | id, order_number, passenger_id, driver_id, tariff_id, status, pickup/dropoff_address, distance, duration, final_price |
| `order_statuses` | История статусов заказа | id, order_id, status, changed_at |
| `tariffs` | Тарифы | id, name, code, base_price, price_per_km, price_per_min, is_active |
| `zones` | Зоны (тарифные) | id, name, coordinates, tariff_id |
| `transactions` | Финансовые операции | id, user_id, order_id, type, amount, status, payment_method |
| `payouts` | Выплаты водителям | id, driver_id, amount, period_start, period_end, status |
| `reviews` | Отзывы | id, order_id, passenger_rating, driver_rating, passenger_comment, driver_comment |
| `notifications` | Уведомления | id, user_id, type, title, message, is_read, data |
| `driver_locations` | История местоположений | id, driver_id, latitude, longitude, recorded_at |
| `driver_documents` | Документы водителей | id, driver_id, type, file_path, expiry_date, is_verified |
| `roles` | Роли доступа | id, name, description |
| `user_roles` | Связь пользователей и ролей | id, user_id, role_id |
| `settings` | Настройки системы | id, key_name, value, type, group_name, description |
| `promotions` | Акции и промокоды | id, code, discount_type, discount_value, valid_from, valid_until |
| `user_promotions` | Использование акций | id, user_id, promotion_id, order_id, used_at |
| `system_logs` | Логи системы | id, level, message, context, created_at |

### Структура таблицы `orders`

```
orders
├── id                           # PK
├── order_number                 # Уникальный номер (ORD-XXXXXX)
├── passenger_id                 # FK -> passengers.id
├── driver_id                    # FK -> drivers.id (nullable)
├── dispatcher_id                # FK -> users.id (nullable)
├── tariff_id                    # FK -> tariffs.id (nullable)
├── status                       # new, accepted, arrived, started, completed, cancelled
├── pickup_address               # Адрес подачи
├── pickup_lat, pickup_lng       # Координаты подачи
├── dropoff_address              # Адрес назначения
├── dropoff_lat, dropoff_lng     # Координаты назначения
├── distance                     # Расстояние (км)
├── duration                     # Длительность (мин)
├── final_price                  # Итоговая цена
├── base_price                   # Базовая цена
├── distance_price               # Цена за расстояние
├── time_price                   # Цена за время
├── surge_multiplier             # Коэффициент (1.0 - 3.0)
├── payment_method               # cash, card, wallet
├── payment_status               # pending, paid, refunded
├── driver_earnings              # Заработок водителя
├── commission_amount            # Комиссия системы
├── passenger_name, passenger_phone
├── notes, driver_notes
├── cancelled_by                 # FK -> users.id
├── cancellation_reason
├── accepted_at, arrived_at, started_at, completed_at, cancelled_at
└── created_at, updated_at
```

### Структура таблицы `drivers`

```
drivers
├── id                           # PK
├── user_id                      # FK -> users.id
├── driver_license_number        # Номер водительского удостоверения
├── driver_license_expiry        # Срок действия
├── total_rides                  # Количество поездок
├── total_earnings               # Общий заработок
├── rating                       # Рейтинг (0-5)
├── total_ratings                # Количество оценок
├── status                       # offline, online, busy
├── commission_rate              # Комиссия (%)
├── current_lat, current_lng     # Текущее местоположение
├── last_location_update         # Последнее обновление GPS
├── is_online                    # Онлайн статус
├── can_accept_orders            # Может принимать заказы
└── created_at, updated_at
```

### Структура таблицы `passengers`

```
passengers
├── id                           # PK
├── user_id                      # FK -> users.id
├── balance                      # Баланс кошелька
├── bonus_balance                # Бонусные баллы
├── total_rides                  # Количество поездок
├── total_spent                  # Потрачено всего
├── default_payment_method       # Метод оплаты по умолчанию
├── home_address, work_address   # Избранные адреса
├── preferred_car_class          # Предпочитаемый класс авто
└── created_at, updated_at
```

### ER-диаграмма (основные связи)

```
users (1) ── (1) passengers
users (1) ── (1) drivers
users (1) ── (N) notifications
users (1) ── (N) user_roles
users (1) ── (N) orders (as dispatcher)
users (1) ── (N) orders (as cancelled_by)

drivers (1) ── (N) cars
drivers (1) ── (N) driver_locations
drivers (1) ── (N) driver_documents
drivers (1) ── (N) payouts

passengers (1) ── (N) orders
passengers (1) ── (N) transactions
passengers (1) ── (N) user_promotions

tariffs (1) ── (N) orders
tariffs (1) ── (N) zones

orders (1) ── (N) order_statuses
orders (1) ── (1) reviews
orders (1) ── (N) transactions
orders (1) ── (N) user_promotions

roles (1) ── (N) user_roles
```
users (1) ── (1) passengers
users (1) ── (1) drivers
drivers (1) ── (N) cars
drivers (1) ── (N) driver_locations

passengers (1) ── (N) orders
drivers (1) ── (N) orders
tariffs (1) ── (N) orders
orders (1) ── (N) order_statuses
orders (1) ── (1) reviews

orders (1) ── (N) transactions
drivers (1) ── (N) payouts
users (1) ── (N) notifications
users (1) ── (N) user_roles
roles (1) ── (N) user_roles
```

---

## 🔐 Аутентификация и авторизация

### Механизм аутентификации

- **Session-based** (Laravel Breeze)
- **CSRF Protection** для всех POST-запросов
- **Remember Me** для долгой сессии

### Система ролей

```php
// Middleware: RoleCheck.php
public function handle($request, Closure $next, ...$roles)
{
    if (!auth()->check()) {
        return redirect()->route('login');
    }
    
    if (!in_array(auth()->user()->role, $roles)) {
        abort(403, 'Unauthorized access');
    }
    
    return $next($request);
}
```

### Маршруты с защитой по ролям

```php
// routes/web.php
```

---

## 🎮 Контроллеры и маршруты

### Основные контроллеры

| Контроллер | Методы | Назначение |
|-----------|--------|------------|
| **AnalyticsController** | `dispatcher()`, `admin()` | Сбор статистики для дашбордов диспетчера и админа |
| **OrderController** | `store()`, `index()`, `adminIndex()` | Создание и просмотр заказов |
| **PassengerController** | `home()` | Главная страница пассажира |
| **ProfileController** | `edit()`, `update()`, `destroy()` | Управление профилем |

### Admin-контроллеры (`app/Http/Controllers/Admin/`)

| Контроллер | Методы | Назначение |
|-----------|--------|------------|
| **AdminUsersController** | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` | Управление пользователями |
| **AdminOrdersController** | `index()`, `show()`, `update()`, `destroy()` | Управление заказами |
| **AdminCancelledOrdersController** | `index()` | Просмотр отменённых заказов |
| **AdminTariffsController** | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` | Управление тарифами |
| **AdminCarsController** | `index()`, `create()`, `store()`, `edit()`, `update()`, `destroy()` | Управление автомобилями |
| **AdminSettingsController** | `index()`, `update()` | Настройки системы (включая API times) |

### API-контроллеры (`app/Http/Controllers/Api/`)

| Контроллер | Методы | Назначение |
|-----------|--------|------------|
| **AvailableOrdersController** | `__invoke()` | Получение доступных заказов для водителя |
| **AcceptOrderController** | `__invoke()` | Принятие заказа водителем |
| **StartTripController** | `__invoke()` | Начало поездки |
| **ArrivedAtCustomerController** | `__invoke()` | Водитель прибыл к клиенту |
| **CompleteOrderController** | `__invoke()` | Завершение поездки |
| **DriverCancelOrderController** | `__invoke()` | Отмена заказа водителем |
| **DriverStatusController** | `__invoke()` | Обновление статуса водителя |
| **DriverStatusesController** | `__invoke()` | Получение статусов всех водителей |
| **DriverActiveOrderController** | `__invoke()` | Получение активного заказа водителя |
| **DriverProfileController** | `__invoke()` | Профиль водителя |
| **PassengerOrderController** | `__invoke()` | Управление заказом пассажира |
| **DispatcherOrdersController** | `__invoke()` | Заказы для диспетчера |
| **DispatcherCancelledOrdersController** | `__invoke()` | Отменённые заказы (диспетчер) |
| **OrderStatsController** | `__invoke()` | Статистика заказов |
| **ReviewController** | `store()`, `driverReviews()`, `checkReview()` | Отзывы и рейтинги |

### Auth-контроллеры (`app/Http/Controllers/Auth/`)

| Контроллер | Методы | Назначение |
|-----------|--------|------------|
| **AuthenticatedSessionController** | `create()`, `store()`, `destroy()` | Вход в систему |
| **RegisteredUserController** | `create()`, `store()` | Регистрация (общая) |
| **RegisterPassengerController** | `create()`, `store()` | Регистрация пассажира |
| **RegisterDriverController** | `create()`, `store()` | Регистрация водителя |
| **RegisterDispatcherController** | `create()`, `store()` | Регистрация диспетчера |
| **PasswordResetLinkController** | `create()`, `store()` | Сброс пароля |
| **NewPasswordController** | `create()`, `store()` | Новый пароль |
| **PasswordController** | `update()` | Изменение пароля |
| **EmailVerificationPromptController** | `__invoke()` | Подтверждение email |
| **EmailVerificationNotificationController** | `store()` | Отправка подтверждения |
| **VerifyEmailController** | `__invoke()` | Проверка email |
| **ConfirmablePasswordController** | `confirm()` | Подтверждение пароля |

### Driver-контроллеры (`app/Http/Controllers/Driver/`)

| Контроллер | Методы | Назначение |
|-----------|--------|------------|
| **CarController** | `index()`, `store()`, `update()`, `destroy()` | Управление автомобилями водителя |

### Роуты (основные)

```php
// Web-маршруты
Route::get('/', fn() => redirect('/passenger'));

// Пассажир
Route::middleware(['auth', 'role:passenger'])->group(function () {
    Route::get('/passenger', [PassengerController::class, 'home']);
});

// Водитель
Route::middleware(['auth', 'role:driver'])->group(function () {
    Route::get('/driver', [DriverController::class, 'dashboard']);
});

// Диспетчер
Route::middleware(['auth', 'role:dispatcher'])->group(function () {
    Route::get('/dispatcher/orders', [DispatcherOrderController::class, 'index']);
    Route::get('/dispatcher/drivers', [DispatcherDriverController::class, 'index']);
    Route::get('/dispatcher/analytics', [AnalyticsController::class, 'dispatcher']);
    Route::get('/dispatcher/map', [DispatcherMapController::class, 'index']);
});

// Админ
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin', fn() => redirect('/admin/dashboard'));
    Route::resource('/admin/users', AdminUsersController::class);
    Route::resource('/admin/orders', AdminOrdersController::class);
    Route::resource('/admin/tariffs', AdminTariffsController::class);
    Route::resource('/admin/cars', AdminCarsController::class);
    Route::get('/admin/settings', [AdminSettingsController::class, 'index']);
    Route::put('/admin/settings', [AdminSettingsController::class, 'update']);
});

// API-маршруты
Route::middleware(['auth:sanctum'])->prefix('api')->group(function () {
    Route::get('/orders/available', AvailableOrdersController::class);
    Route::post('/orders/{order}/accept', AcceptOrderController::class);
    Route::post('/orders/{order}/start', StartTripController::class);
    Route::post('/orders/{order}/arrived', ArrivedAtCustomerController::class);
    Route::post('/orders/{order}/complete', CompleteOrderController::class);
    Route::post('/orders/{order}/cancel', DriverCancelOrderController::class);
    Route::get('/driver/status', DriverStatusController::class);
    Route::get('/driver/statuses', DriverStatusesController::class);
    Route::get('/driver/active-order', DriverActiveOrderController::class);
});
```



---

## 🎨 Frontend архитектура

### Компонентная структура

```
resources/js/
├── Components/
│   ├── UI/                          # Базовые UI-компоненты
│   │   ├── Button.vue
│   │   ├── Input.vue
│   │   ├── Modal.vue
│   │   └── Card.vue
│   ├── Layout/
│   │   ├── Header.vue
│   │   ├── Sidebar.vue
│   │   └── Footer.vue
│   ├── Orders/
│   │   ├── OrderCard.vue
│   │   ├── OrderList.vue
│   │   └── OrderStatus.vue
│   ├── Maps/
│   │   ├── MapView.vue
│   │   └── DriverMarker.vue
│   └── Charts/
│       ├── LineChart.vue
│       └── BarChart.vue
├── Layouts/
│   ├── AdminLayout.vue
│   ├── DispatcherLayout.vue
│   ├── DriverLayout.vue
│   └── PassengerLayout.vue
├── Pages/
│   ├── Admin/
│   │   ├── Dashboard.vue
│   │   ├── Orders.vue
│   │   └── Analytics.vue
│   ├── Dispatcher/
│   │   ├── Orders.vue
│   │   ├── Drivers.vue
│   │   └── Analytics.vue
│   ├── Driver/
│   │   ├── Dashboard.vue
│   │   └── Orders.vue
│   ├── Passenger/
│   │   ├── Home.vue
│   │   └── OrderRide.vue
│   └── Auth/
│       ├── Login.vue
│       └── Register.vue
└── app.js
```

### Inertia.js поток данных

```
┌─────────────┐      HTTP Request      ┌─────────────┐
│   Vue SPA   │ ─────────────────────► │   Laravel   │
│             │                        │ Controller  │
│             │ ◄───────────────────── │             │
│             │   Inertia Response     │             │
│             │   (JSON + Component)   │             │
└─────────────┘                        └─────────────┘
       │                                      │
       │                                      ▼
       │                              ┌─────────────┐
       │                              │   Database  │
       │                              └─────────────┘
       ▼
┌─────────────┐
│   Render    │
│   Component │
└─────────────┘
```

---

## 🔒 Безопасность

### Реализованные меры

| Угроза | Защита |
|--------|--------|
| **CSRF** | Laravel CSRF Token + `@csrf` |
| **XSS** | Выходные данные экранируются автоматически |
| **SQL Injection** | Eloquent ORM + параметризованные запросы |
| **Авторизация** | Middleware + Role Check |
| **Пароли** | bcrypt хеширование |
| **Сессии** | Encrypted cookies + HTTPOnly |
| **CORS** | Настроен для доверенных доменов |
| **Rate Limiting** | Laravel Throttle Middleware |

### Конфиденциальные данные

```php
// .env (не коммитить в Git!)
APP_KEY=base64:...
DB_PASSWORD=...
REDIS_PASSWORD=...
JWT_SECRET=...
```

### Audit Log

Все критические действия логируются:

```php
//описать где что логируется
```

---

## 🎨 Цветовая маркировка статусов заказов

В системе используется единая цветовая схема для отображения статусов заказов во всех интерфейсах (пассажир, водитель, диспетчер, админ):

| Статус | Название | Цвет | Tailwind CSS |
|--------|----------|------|--------------|
| `new` | Новый заказ | Зелёный | `bg-green-600` |
| `accepted` | Принят | Синий | `bg-blue-600` |
| `arrived` | Прибыл к клиенту | Жёлтый | `bg-yellow-600` |
| `in_transit` / `started` | В пути | Светло-оранжевый | `bg-orange-500` |
| `completed` | Завершён | Серый | `bg-gray-600` |
| `cancelled` | Отменён | Красный | `bg-red-600` |

**Пример использования в Vue:**

```javascript
const orderStatusColor = {
  'new': 'green',
  'accepted': 'blue',
  'arrived': 'yellow',
  'in_transit': 'orange',
  'started': 'orange',
  'completed': 'gray',
  'cancelled': 'red'
}

// В шаблоне:
<div :class="[
  'px-4 py-3 text-white',
  orderStatusColor[order.status] === 'green' ? 'bg-green-600' : '',
  orderStatusColor[order.status] === 'blue' ? 'bg-blue-600' : '',
  orderStatusColor[order.status] === 'yellow' ? 'bg-yellow-600' : '',
  orderStatusColor[order.status] === 'orange' ? 'bg-orange-500' : '',
  orderStatusColor[order.status] === 'red' ? 'bg-red-600' : '',
  orderStatusColor[order.status] === 'gray' ? 'bg-gray-600' : '',
]">
```

### Где используется

- **Passenger/Home.vue** — статус заказа, история заказов
- **Driver/Home.vue** — доступные заказы
- **Dispatcher/Orders.vue** — бейджи статусов в списке заказов
- **Dispatcher/Map.vue** — маркеры заказов на карте
- **Admin/Orders.vue** — бейджи статусов в админке

---

## 🚀 Развёртывание

### Требования к серверу

| Компонент | Минимум | Рекомендуется |
|-----------|---------|---------------|
| **CPU** | 2 ядра | 4+ ядра |
| **RAM** | 4 ГБ | 8+ ГБ |
| **Диск** | 20 ГБ SSD | 100 ГБ NVMe |
| **ОС** | Ubuntu 22.04 | Ubuntu 22.04 LTS |

### Шаги деплоя

```bash
# 1. Клонирование репозитория
git clone <repo> /var/www/TaxiVezi
cd /var/www/TaxiVezi

# 2. Установка зависимостей
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 3. Настройка окружения
cp .env.example .env
php artisan key:generate

# 4. Миграции
php artisan migrate --force

# 5. Кэширование
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Права доступа
chown -R www-data:www-data .
chmod -R 775 storage bootstrap/cache

# 7. Запуск очередей
supervisorctl start TaxiVezi-worker:*
```

### CI/CD Pipeline (GitHub Actions)

## 📊 Мониторинг и логирование

### Логи приложения

### Метрики для отслеживания

## 📝 Версионирование

| Версия | Дата | Изменения |
|--------|------|-----------|
| 1.0.0 | 2026-03 | Initial release |


---

##  Контакты

- **Проект**: TaxiVezi
- **Версия документа**: 1.0
- **Последнее обновление**: 2026-03-24

---

*Документация может обновляться по мере развития проекта.*