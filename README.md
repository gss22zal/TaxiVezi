# 🚕 TaxiVezi — Такси-сервис

Современная платформа для управления службой такси с четырьмя ролями пользователей.

## 🏗 Архитектура

- **Backend**: Laravel 12.x (PHP 8.4)
- **Frontend**: Inertia.js 2.x + Vue 3
- **Стили**: Tailwind CSS 3.x
- **База данных**: Microsoft SQL Server 2022 / MySQL
- **Кэш/Очереди**: Redis 7.x

## 👥 Роли пользователей

| Роль | URL | Описание |
|------|-----|----------|
| Пассажир | `/passenger` | Заказ такси, отслеживание, оплата |
| Водитель | `/driver` | Приём заказов, навигация, статистика |
| Диспетчер | `/dispatcher/*` | Управление заказами, мониторинг водителей |
| Администратор | `/admin/*` | Полный контроль, аналитика, настройки |

## 📁 Структура проекта

```
TaxiVezi/
├── app/
│   ├── Http/Controllers/
│   │   ├── Admin/          # Админ-контроллеры
│   │   ├── Dispatcher/     # Диспетчер-контроллеры
│   │   ├── Driver/         # Водитель-контроллеры
│   │   └── Passenger/      # Пассажир-контроллеры
│   ├── Models/             # Eloquent модели
│   ├── Services/           # Бизнес-логика
│   └── ...
├── database/
│   ├── migrations/         # Миграции БД
│   ├── seeders/            # Сиды
│   └── factories/          # Фабрики
├── resources/
│   └── js/
│       ├── Components/     # Переиспользуемые компоненты
│       ├── Layouts/        # Макеты страниц
│       └── Pages/          # Страницы по ролям
├── routes/
│   └── web.php             # Маршруты
└── ...
```

## 📁 Структура страниц (Pages)

```
resources/js/Pages/
├── Landing.vue              # Главная (лендинг)
├── Dashboard.vue            # Дашборд (авторизованный)
├── Passenger/               # Страницы пассажира
│   ├── Home.vue
│   ├── OrderRide.vue
│   └── OrderComplete.vue
├── Driver/                  # Страницы водителя
│   ├── Dashboard.vue
│   ├── ActiveOrder.vue
│   └── OrderHistory.vue
├── Dispatcher/              # Страницы диспетчера
│   ├── Orders.vue           # Заказы
│   ├── Drivers.vue          # Водители
│   ├── Analytics.vue        # Аналитика
│   └── Map.vue              # Карта
└── Admin/                   # Админ-панель
    ├── Dashboard.vue        # Дашборд
    ├── Users/               # Управление пользователями
    ├── Orders.vue           # Заказы
    ├── Tariffs.vue          # Тарифы
    ├── Finance/             # Финансы
    └── Settings.vue         # Настройки
```

## 🚀 Запуск

```bash
# Установка зависимостей
composer install
npm install

# Настройка .env
cp .env.example .env
php artisan key:generate

# Настройка подключения к БД (Microsoft SQL Server)
# DB_CONNECTION=sqlsrv
# DB_HOST=localhost
# DB_PORT=1433
# DB_DATABASE=taxivezi
# DB_USERNAME=sa
# DB_PASSWORD=your_password

# Миграции и сиды
php artisan migrate
php artisan db:seed

# Запуск dev-сервера
php artisan serve
npm run dev
```

## 🔑 Основные роуты

```
/                   -> Landing (лендинг)
/passenger          -> Пассажир
/driver             -> Водитель
/dispatcher         -> Диспетчер (редирект на orders)
/admin              -> Админ-панель
/dashboard          -> Дашборд авторизованного
```

## 🗄️ База данных

### Основные таблицы

| Таблица | Описание |
|---------|----------|
| `users` | Пользователи системы |
| `passengers` | Профили пассажиров |
| `drivers` | Профили водителей |
| `cars` | Автомобили |
| `orders` | Заказы |
| `tariffs` | Тарифы |
| `transactions` | Финансовые операции |
| `payouts` | Выплаты водителям |
| `reviews` | Отзывы |
| `settings` | Настройки системы |

## 🛠 Команды

```bash
# Очистка кэша
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## ✅ Реализовано

- [x] Мультиролевая авторизация (пассажир, водитель, диспетчер, админ)
- [x] Админ-панель с управлением пользователями
- [x] Управление заказами, тарифами, финансами
- [x] Страница аналитики для диспетчера
- [x] Профили пользователей (пассажир, водитель)
- [x] Функционал заказа такси
- [x] Частично система рейтингов и отзывов

## 📋 TODO

- [ ] Доработать функционал заказа такси
- [ ] Интеграция карт (Яндекс/Google)
- [ ] Push-уведомления водителям
- [ ] Оплата и финансы
- [ ] Система рейтингов и отзывов
- [ ] Real-time обновления (Laravel Reverb)

## 🔐 Безопасность

- CSRF Protection
- XSS Protection (автоматическое экранирование)
- SQL Injection Protection (Eloquent ORM)
- Role-based Access Control (RBAC)
- Пароли: bcrypt хеширование

## 📊 Технологический стек

| Компонент | Технология |
|-----------|------------|
| Backend | Laravel 12.x, PHP 8.4 |
| Frontend | Vue 3, Inertia.js 2.x, Vite 7.x |
| Стили | Tailwind CSS 3.x |
| База данных | MS SQL Server 2022 / MySQL |
| Кэш | Redis 7.x |
| Карты | Яндекс.Карты / Google Maps |

## 📝 Версия

- **Версия**: 1.0.2
- **Дата**: 2026-04-04
