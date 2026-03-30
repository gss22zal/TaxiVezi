<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link } from '@inertiajs/vue3'
import { 
    UserIcon, 
    ArrowRightIcon, 
    ArrowDownIcon,
    ClockIcon, 
    ShieldCheckIcon, 
    CurrencyRupeeIcon,
    MapPinIcon,
    CreditCardIcon, 
    BuildingOfficeIcon,
    CheckIcon, 
    PhoneIcon, 
    EnvelopeIcon,
    MapIcon,
    BoltIcon
} from '@heroicons/vue/24/outline'

// Анимация при скролле
onMounted(() => {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-fade-in')
            }
        })
    }, { threshold: 0.1 })
    
    document.querySelectorAll('.animate-on-scroll').forEach((el) => {
        observer.observe(el)
    })
})
</script>

<template>
    <Head title="TaxiVezi - Умная система управления такси" />
    
    <div class="min-h-screen bg-gray-900">
        <!-- Навигация -->
        <nav class="fixed top-0 w-full z-50 bg-gray-900/80 backdrop-blur-md border-b border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Логотип -->
                    <Link href="/" class="flex items-center gap-2">
                        <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                        </svg>
                        </div>
                        <span class="text-white text-xl font-bold">TaxiVezi</span>
                    </Link>
                    
                    <!-- Меню -->
                    <div class="hidden md:flex items-center gap-8">
                        <a href="#features" class="text-gray-300 hover:text-yellow-500 transition">Возможности</a>
                        <a href="#solutions" class="text-gray-300 hover:text-yellow-500 transition">Решения</a>
                        <a href="#pricing" class="text-gray-300 hover:text-yellow-500 transition">Тарифы</a>
                        <a href="#contact" class="text-gray-300 hover:text-yellow-500 transition">Контакты</a>
                    </div>
                    
                    <!-- Кнопки -->
                    <div class="flex items-center gap-4">
                        <Link href="/login" class="text-gray-300 hover:text-white transition">
                            Войти
                        </Link>
                        <Link href="/register/role" class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-semibold px-6 py-2 rounded-lg transition">
                            Начать
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative min-h-screen flex items-center justify-center pt-16 overflow-hidden">
            <!-- Фоновые элементы -->
            <div class="absolute inset-0 bg-gradient-to-br from-gray-900 via-indigo-950 to-gray-900"></div>
            <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\'60\' height=\'60\' viewBox=\'0 0 60 60\' xmlns=\'http://www.w3.org/2000/svg\'%3E%3Cg fill=\'none\' fill-rule=\'evenodd\'%3E%3Cg fill=\'%239C92AC\' fill-opacity=\'0.1\'%3E%3Cpath d=\'M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-20"></div>
            
            <!-- Анимированные круги -->
            <div class="absolute top-1/4 left-1/4 w-96 h-96 bg-yellow-500/20 rounded-full blur-3xl animate-pulse"></div>
            <div class="absolute bottom-1/4 right-1/4 w-96 h-96 bg-indigo-500/20 rounded-full blur-3xl animate-pulse delay-1000"></div>
            
            <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <!-- Badge -->
                <div class="inline-flex items-center gap-2 bg-yellow-500/10 border border-yellow-500/20 rounded-full px-4 py-2 mb-8">
                    <span class="flex h-2 w-2 rounded-full bg-yellow-500 animate-pulse"></span>
                    <span class="text-yellow-500 text-sm font-medium">Новое поколение такси-сервиса</span>
                </div>
                
                <!-- Заголовок -->
                <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 leading-tight">
                    Умная система<br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-400 to-yellow-600">
                        управления такси
                    </span>
                </h1>
                
                <!-- Подзаголовок -->
                <p class="text-xl md:text-2xl text-gray-400 mb-12 max-w-3xl mx-auto">
                    TaxiVezi — это современная платформа для пассажиров, водителей и диспетчеров. 
                    Быстро, удобно, выгодно.
                </p>
                
                <!-- CTA Кнопки -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
                    <Link href="/register/passenger" 
                        class="group bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold text-lg px-8 py-4 rounded-xl transition-all hover:scale-105 flex items-center justify-center gap-2">
                        <UserIcon class="w-5 h-5" />
                        Я пассажир
                        <ArrowRightIcon class="w-5 h-5 group-hover:translate-x-1 transition" />
                    </Link>
                    <Link href="/register/driver" 
                        class="group bg-gray-800 hover:bg-gray-700 border-2 border-gray-700 hover:border-yellow-500 text-white font-bold text-lg px-8 py-4 rounded-xl transition-all hover:scale-105 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                            </svg>
                        Я водитель
                    </Link>
                    <Link href="/register/dispatcher" 
                        class="group bg-gray-800 hover:bg-gray-700 border-2 border-gray-700 hover:border-yellow-500 text-white font-bold text-lg px-8 py-4 rounded-xl transition-all hover:scale-105 flex items-center justify-center gap-2">
                        <BuildingOfficeIcon class="w-5 h-5" />
                        Я диспетчер
                    </Link>
                </div>
                
                <!-- Статистика -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-8 max-w-4xl mx-auto">
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-yellow-500 mb-2">10K+</div>
                        <div class="text-gray-400 text-sm">Пассажиров</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-yellow-500 mb-2">500+</div>
                        <div class="text-gray-400 text-sm">Водителей</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-yellow-500 mb-2">50K+</div>
                        <div class="text-gray-400 text-sm">Поездок</div>
                    </div>
                    <div class="text-center">
                        <div class="text-3xl md:text-4xl font-bold text-yellow-500 mb-2">4.9</div>
                        <div class="text-gray-400 text-sm">Рейтинг</div>
                    </div>
                </div>
            </div>
            
            <!-- Scroll indicator -->
            <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
                <ArrowDownIcon class="w-6 h-6 text-gray-400" />
            </div>
        </section>

        <!-- Features Section -->
        <section id="features" class="py-24 bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                        Почему выбирают <span class="text-yellow-500">TaxiVezi</span>
                    </h2>
                    <p class="text-xl text-gray-400 max-w-2xl mx-auto">
                        Современные технологии для комфорта пассажиров и эффективности бизнеса
                    </p>
                </div>
                
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-8 hover:border-yellow-500/50 hover:shadow-2xl hover:shadow-yellow-500/10 transition-all duration-300 hover:-translate-y-2 animate-on-scroll">
                        <div class="w-14 h-14 bg-yellow-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-yellow-500/20 transition">
                            <ClockIcon class="w-7 h-7 text-yellow-500" />
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Быстрый заказ</h3>
                        <p class="text-gray-400">Подача автомобиля от 5 минут. Умный алгоритм поиска ближайших водителей.</p>
                    </div>
                    
                    <!-- Feature 2 -->
                    <div class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-8 hover:border-yellow-500/50 hover:shadow-2xl hover:shadow-yellow-500/10 transition-all duration-300 hover:-translate-y-2 animate-on-scroll">
                        <div class="w-14 h-14 bg-yellow-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-yellow-500/20 transition">
                            <ShieldCheckIcon class="w-7 h-7 text-yellow-500" />
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Безопасность</h3>
                        <p class="text-gray-400">Проверенные водители, отслеживание маршрута, страхование каждой поездки.</p>
                    </div>
                    
                    <!-- Feature 3 -->
                    <div class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-8 hover:border-yellow-500/50 hover:shadow-2xl hover:shadow-yellow-500/10 transition-all duration-300 hover:-translate-y-2 animate-on-scroll">
                        <div class="w-14 h-14 bg-yellow-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-yellow-500/20 transition">
                            <CurrencyRupeeIcon class="w-7 h-7 text-yellow-500" />
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Выгодные цены</h3>
                        <p class="text-gray-400">Честные тарифы без скрытых комиссий. Скидки постоянным клиентам.</p>
                    </div>
                    
                    <!-- Feature 4 -->
                    <div class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-8 hover:border-yellow-500/50 hover:shadow-2xl hover:shadow-yellow-500/10 transition-all duration-300 hover:-translate-y-2 animate-on-scroll">
                        <div class="w-14 h-14 bg-yellow-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-yellow-500/20 transition">
                            <MapIcon class="w-7 h-7 text-yellow-500" />
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Отслеживание</h3>
                        <p class="text-gray-400">Real-time отслеживание автомобиля на карте. Точное время подачи.</p>
                    </div>
                    
                    <!-- Feature 5 -->
                    <div class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-8 hover:border-yellow-500/50 hover:shadow-2xl hover:shadow-yellow-500/10 transition-all duration-300 hover:-translate-y-2 animate-on-scroll">
                        <div class="w-14 h-14 bg-yellow-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-yellow-500/20 transition">
                            <CreditCardIcon class="w-7 h-7 text-yellow-500" />
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Удобная оплата</h3>
                        <p class="text-gray-400">Наличные, банковские карты, Apple Pay, Google Pay. Электронные чеки.</p>
                    </div>
                    
                    <!-- Feature 6 -->
                    <div class="group bg-gray-800/50 border border-gray-700 rounded-2xl p-8 hover:border-yellow-500/50 hover:shadow-2xl hover:shadow-yellow-500/10 transition-all duration-300 hover:-translate-y-2 animate-on-scroll">
                        <div class="w-14 h-14 bg-yellow-500/10 rounded-xl flex items-center justify-center mb-6 group-hover:bg-yellow-500/20 transition">
                            <BoltIcon class="w-7 h-7 text-yellow-500" />
                        </div>
                        <h3 class="text-xl font-bold text-white mb-3">Поддержка 24/7</h3>
                        <p class="text-gray-400">Круглосуточная служба поддержки. Быстрое решение любых вопросов.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Solutions Section -->
        <section id="solutions" class="py-24 bg-gradient-to-b from-gray-900 to-indigo-950">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                        Решения для <span class="text-yellow-500">каждого</span>
                    </h2>
                    <p class="text-xl text-gray-400">Выберите свою роль и начните прямо сейчас</p>
                </div>
                
                <div class="grid lg:grid-cols-3 gap-8">
                    <!-- Passenger Card -->
                    <div class="relative group">
                        <div class="absolute inset-0 bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-3xl blur-lg opacity-0 group-hover:opacity-30 transition duration-500"></div>
                        <div class="relative bg-gray-800 border-2 border-gray-700 group-hover:border-yellow-500 rounded-3xl p-8 transition-all duration-300">
                            <div class="w-16 h-16 bg-yellow-500 rounded-2xl flex items-center justify-center mb-6">
                                <UserIcon class="w-8 h-8 text-gray-900" />
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-4">Пассажирам</h3>
                            <ul class="space-y-3 mb-8">
                                <li class="flex items-center gap-3 text-gray-300">
                                    <CheckIcon class="w-5 h-5 text-yellow-500 flex-shrink-0" />
                                    Быстрый заказ поездки
                                </li>
                                <li class="flex items-center gap-3 text-gray-300">
                                    <CheckIcon class="w-5 h-5 text-yellow-500 flex-shrink-0" />
                                    Фиксированные цены
                                </li>
                                <li class="flex items-center gap-3 text-gray-300">
                                    <CheckIcon class="w-5 h-5 text-yellow-500 flex-shrink-0" />
                                    История поездок
                                </li>
                                <li class="flex items-center gap-3 text-gray-300">
                                    <CheckIcon class="w-5 h-5 text-yellow-500 flex-shrink-0" />
                                    Система скидок
                                </li>
                            </ul>
                            <Link href="/register/passenger" 
                                class="block w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold text-center py-3 rounded-xl transition">
                                Стать пассажиром
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Driver Card -->
                    <div class="relative group">
                        <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-3xl blur-lg opacity-0 group-hover:opacity-30 transition duration-500"></div>
                        <div class="relative bg-gray-800 border-2 border-gray-700 group-hover:border-indigo-500 rounded-3xl p-8 transition-all duration-300">
                            <div class="w-16 h-16 bg-indigo-500 rounded-2xl flex items-center justify-center mb-6">
                                <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                            </svg>
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-4">Водителям</h3>
                            <ul class="space-y-3 mb-8">
                                <li class="flex items-center gap-3 text-gray-300">
                                    <CheckIcon class="w-5 h-5 text-indigo-500 flex-shrink-0" />
                                    Высокий заработок
                                </li>
                                <li class="flex items-center gap-3 text-gray-300">
                                    <CheckIcon class="w-5 h-5 text-indigo-500 flex-shrink-0" />
                                    Гибкий график
                                </li>
                                <li class="flex items-center gap-3 text-gray-300">
                                    <CheckIcon class="w-5 h-5 text-indigo-500 flex-shrink-0" />
                                    Моментальные выплаты
                                </li>
                                <li class="flex items-center gap-3 text-gray-300">
                                    <CheckIcon class="w-5 h-5 text-indigo-500 flex-shrink-0" />
                                    Поддержка 24/7
                                </li>
                            </ul>
                            <Link href="/register/driver" 
                                class="block w-full bg-indigo-500 hover:bg-indigo-600 text-white font-bold text-center py-3 rounded-xl transition">
                                Стать водителем
                            </Link>
                        </div>
                    </div>
                    
                    <!-- Dispatcher Card -->
                    <div class="relative group">
                        <div class="absolute inset-0 bg-gradient-to-r from-green-500 to-teal-600 rounded-3xl blur-lg opacity-0 group-hover:opacity-30 transition duration-500"></div>
                        <div class="relative bg-gray-800 border-2 border-gray-700 group-hover:border-green-500 rounded-3xl p-8 transition-all duration-300">
                            <div class="w-16 h-16 bg-green-500 rounded-2xl flex items-center justify-center mb-6">
                                <BuildingOfficeIcon class="w-8 h-8 text-white" />
                            </div>
                            <h3 class="text-2xl font-bold text-white mb-4">Диспетчерам</h3>
                            <ul class="space-y-3 mb-8">
                                <li class="flex items-center gap-3 text-gray-300">
                                    <CheckIcon class="w-5 h-5 text-green-500 flex-shrink-0" />
                                    Удобная панель управления
                                </li>
                                <li class="flex items-center gap-3 text-gray-300">
                                    <CheckIcon class="w-5 h-5 text-green-500 flex-shrink-0" />
                                    Контроль всех заказов
                                </li>
                                <li class="flex items-center gap-3 text-gray-300">
                                    <CheckIcon class="w-5 h-5 text-green-500 flex-shrink-0" />
                                    Аналитика и отчеты
                                </li>
                                <li class="flex items-center gap-3 text-gray-300">
                                    <CheckIcon class="w-5 h-5 text-green-500 flex-shrink-0" />
                                    Управление водителями
                                </li>
                            </ul>
                            <Link href="/register/dispatcher" 
                                class="block w-full bg-green-500 hover:bg-green-600 text-white font-bold text-center py-3 rounded-xl transition">
                                Стать диспетчером
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Section -->
        <section id="pricing" class="py-24 bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-4">
                        Прозрачные <span class="text-yellow-500">тарифы</span>
                    </h2>
                    <p class="text-xl text-gray-400">Выберите класс автомобиля под ваши задачи</p>
                </div>
                
                <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
                    <!-- Economy -->
                    <div class="bg-gray-800 border border-gray-700 rounded-2xl p-8 hover:border-gray-600 transition">
                        <div class="text-gray-400 text-sm mb-2">Эконом</div>
                        <div class="text-4xl font-bold text-white mb-6">от 150 ₽</div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center gap-3 text-gray-300">
                                <CheckIcon class="w-5 h-5 text-gray-400" />
                                Базовая стоимость: 100 ₽
                            </li>
                            <li class="flex items-center gap-3 text-gray-300">
                                <CheckIcon class="w-5 h-5 text-gray-400" />
                                За км: 15 ₽
                            </li>
                            <li class="flex items-center gap-3 text-gray-300">
                                <CheckIcon class="w-5 h-5 text-gray-400" />
                                За мин: 3 ₽
                            </li>
                            <li class="flex items-center gap-3 text-gray-300">
                                <CheckIcon class="w-5 h-5 text-gray-400" />
                                Hyundai Solaris, Kia Rio
                            </li>
                        </ul>
                        <button class="w-full border-2 border-gray-600 hover:border-yellow-500 text-white font-semibold py-3 rounded-xl transition">
                            Рассчитать стоимость
                        </button>
                    </div>
                    
                    <!-- Comfort (Popular) -->
                    <div class="relative bg-gray-800 border-2 border-yellow-500 rounded-2xl p-8 transform scale-105 shadow-2xl shadow-yellow-500/20">
                        <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 bg-yellow-500 text-gray-900 font-bold px-4 py-1 rounded-full text-sm">
                            Популярный
                        </div>
                        <div class="text-yellow-500 text-sm mb-2">Комфорт</div>
                        <div class="text-4xl font-bold text-white mb-6">от 250 ₽</div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center gap-3 text-gray-300">
                                <CheckIcon class="w-5 h-5 text-yellow-500" />
                                Базовая стоимость: 200 ₽
                            </li>
                            <li class="flex items-center gap-3 text-gray-300">
                                <CheckIcon class="w-5 h-5 text-yellow-500" />
                                За км: 25 ₽
                            </li>
                            <li class="flex items-center gap-3 text-gray-300">
                                <CheckIcon class="w-5 h-5 text-yellow-500" />
                                За мин: 5 ₽
                            </li>
                            <li class="flex items-center gap-3 text-gray-300">
                                <CheckIcon class="w-5 h-5 text-yellow-500" />
                                Toyota Camry, Kia K5
                            </li>
                        </ul>
                        <button class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-3 rounded-xl transition">
                            Рассчитать стоимость
                        </button>
                    </div>
                    
                    <!-- Business -->
                    <div class="bg-gray-800 border border-gray-700 rounded-2xl p-8 hover:border-gray-600 transition">
                        <div class="text-gray-400 text-sm mb-2">Бизнес</div>
                        <div class="text-4xl font-bold text-white mb-6">от 600 ₽</div>
                        <ul class="space-y-4 mb-8">
                            <li class="flex items-center gap-3 text-gray-300">
                                <CheckIcon class="w-5 h-5 text-gray-400" />
                                Базовая стоимость: 500 ₽
                            </li>
                            <li class="flex items-center gap-3 text-gray-300">
                                <CheckIcon class="w-5 h-5 text-gray-400" />
                                За км: 45 ₽
                            </li>
                            <li class="flex items-center gap-3 text-gray-300">
                                <CheckIcon class="w-5 h-5 text-gray-400" />
                                За мин: 10 ₽
                            </li>
                            <li class="flex items-center gap-3 text-gray-300">
                                <CheckIcon class="w-5 h-5 text-gray-400" />
                                BMW 5, Mercedes E-class
                            </li>
                        </ul>
                        <button class="w-full border-2 border-gray-600 hover:border-yellow-500 text-white font-semibold py-3 rounded-xl transition">
                            Рассчитать стоимость
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-24 bg-gradient-to-r from-yellow-500/10 to-indigo-600/10">
            <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                    Готовы начать?
                </h2>
                <p class="text-xl text-gray-400 mb-12 max-w-2xl mx-auto">
                    Присоединяйтесь к тысячам довольных клиентов и водителей уже сегодня
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <Link href="/register/role" 
                        class="bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold text-lg px-10 py-4 rounded-xl transition-all hover:scale-105">
                        Зарегистрироваться
                    </Link>
                    <a href="#contact" 
                        class="bg-gray-800 hover:bg-gray-700 border-2 border-gray-700 text-white font-bold text-lg px-10 py-4 rounded-xl transition">
                        Связаться с нами
                    </a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer id="contact" class="bg-gray-900 border-t border-gray-800 py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid md:grid-cols-4 gap-8 mb-8">
                    <!-- Logo & Info -->
                    <div class="col-span-2">
                        <div class="flex items-center gap-2 mb-4">
                            <div class="w-10 h-10 bg-yellow-500 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
                        </svg>
                            </div>
                            <span class="text-white text-xl font-bold">TaxiVezi</span>
                        </div>
                        <p class="text-gray-400 mb-4 max-w-sm">
                            Современная система управления такси для пассажиров, водителей и диспетчеров
                        </p>
                        <div class="flex gap-4">
                            <a href="#" class="w-10 h-10 bg-gray-800 hover:bg-yellow-500 rounded-lg flex items-center justify-center transition">
                                <svg class="w-5 h-5 text-gray-400 hover:text-gray-900" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                            </a>
                            <a href="#" class="w-10 h-10 bg-gray-800 hover:bg-yellow-500 rounded-lg flex items-center justify-center transition">
                                <svg class="w-5 h-5 text-gray-400 hover:text-gray-900" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Links -->
                    <div>
                        <h4 class="text-white font-semibold mb-4">Компания</h4>
                        <ul class="space-y-2">
                            <li><a href="#" class="text-gray-400 hover:text-yellow-500 transition">О нас</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-yellow-500 transition">Карьера</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-yellow-500 transition">Пресса</a></li>
                            <li><a href="#" class="text-gray-400 hover:text-yellow-500 transition">Блог</a></li>
                        </ul>
                    </div>
                    
                    <!-- Contact -->
                    <div>
                        <h4 class="text-white font-semibold mb-4">Контакты</h4>
                        <ul class="space-y-2 text-gray-400">
                            <li class="flex items-center gap-2">
                                <PhoneIcon class="w-4 h-4" />
                                8 (800) 555-35-35
                            </li>
                            <li class="flex items-center gap-2">
                                <EnvelopeIcon class="w-4 h-4" />
                                info@taxivezi.ru
                            </li>
                            <li class="flex items-start gap-2">
                                <MapPinIcon class="w-4 h-4 flex-shrink-0 mt-1" />
                                Москва, ул. Примерная, 123
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                    <p class="text-gray-500 text-sm">
                        © 2024 TaxiVezi. Все права защищены.
                    </p>
                    <div class="flex gap-6 text-sm">
                        <a href="#" class="text-gray-500 hover:text-yellow-500 transition">Политика конфиденциальности</a>
                        <a href="#" class="text-gray-500 hover:text-yellow-500 transition">Условия использования</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.animate-fade-in {
    animation: fadeIn 0.8s ease-out forwards;
}

.animate-on-scroll {
    opacity: 0;
    transform: translateY(20px);
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.delay-1000 {
    animation-delay: 1s;
}
</style>
