<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'
import { useOrderNotifications } from '@/composables/useOrderNotifications'

const props = defineProps({
  activeTab: {
    type: String,
    default: 'orders'
  },
  stats: {
    type: Object,
    default: () => null
  }
})

const page = usePage()
const orderStats = computed(() => page.props?.orderStats || { new: 0, accepted: 0, in_progress: 0 })

// Реальная статистика из БД (передаётся через props или из page.props)
const realStats = computed(() => {
  // Если передано через props - используем
  if (props.stats) return props.stats

  // Иначе пробуем получить из page.props
  const dispatcherStats = page.props?.dispatcherStats
  if (dispatcherStats) return dispatcherStats

  // Fallback - дефолтные значения
  return {
    completed: 0,
    active: 0,
    revenue: 0
  }
})

// Статистика водителей из БД
const driverStats = computed(() => {
  return page.props?.driverStats || { total: 0, online: 0, busy: 0, free: 0 }
})

// Включаем real-time оповещения для диспетчера
useOrderNotifications({
  onNewOrder: (stats) => {
    console.log('🔔 Новый заказ для диспетчера!', stats)
  }
})

const currentDate = computed(() => {
  const now = new Date()
  const months = ['ЯНВАРЯ', 'ФЕВРАЛЯ', 'МАРТА', 'АПРЕЛЯ', 'МАЯ', 'ИЮНЯ', 'ИЮЛЯ', 'АВГУСТА', 'СЕНТЯБРЯ', 'ОКТЯБРЯ', 'НОЯБРЯ', 'ДЕКАБРЯ']
  return `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`
})

const navItems = computed(() => [
  { id: 'orders', name: 'Заказы', icon: 'clipboard', route: '/dispatcher/orders', stats: orderStats.value },
  { id: 'drivers', name: 'Водители', icon: 'users', route: '/dispatcher/drivers' },
  { id: 'reviews', name: 'Отзывы', icon: 'star', route: '/dispatcher/reviews' },
  { id: 'analytics', name: 'Аналитика', icon: 'chart', route: '/dispatcher/analytics' },
  { id: 'map', name: 'Карта', icon: 'map', route: '/dispatcher/map' },
])

const switchRole = (role) => {
  if (role === 'passenger') window.location.href = '/passenger'
  else if (role === 'driver') window.location.href = '/driver'
  else if (role === 'dispatcher') window.location.href = '/dispatcher/orders'
}

const logout = () => {
  router.post(route('logout'))
}
</script>

<template>
  <div class="min-h-screen bg-[#0F172A]">
    <!-- Header -->
    <header class="fixed left-0 right-0 top-0 z-50 bg-[#1F2937] px-4 py-3">
      <div class="flex items-center justify-between pl-64">
        <!-- Logo -->
        <div class="flex items-center gap-2">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-500">
            <svg class="h-6 w-6 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
              <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
            </svg>
          </div>
          <div>
            <div class="text-lg font-bold text-white">TaxiVezi</div>
            <div class="text-xs text-gray-400">Диспетчерская система</div>
          </div>
        </div>

        <!-- Role Switcher -->
        <div class="flex gap-2">
          <button
            @click="switchRole('passenger')"
            :class="[
              'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all',
              'bg-gray-700 text-gray-400 hover:bg-gray-600'
            ]"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Пассажир
          </button>
          <button
            @click="switchRole('driver')"
            :class="[
              'flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition-all',
              'bg-gray-700 text-gray-400 hover:bg-gray-600'
            ]"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            Водитель
          </button>
          <button
            @click="switchRole('dispatcher')"
            class="flex items-center gap-2 rounded-lg bg-yellow-500 px-3 py-2 text-sm font-medium text-gray-900"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
            </svg>
            Диспетчер
          </button>
        </div>

        <!-- Date & Online -->
        <div class="text-right">
          <div class="text-sm font-medium text-gray-300">{{ currentDate }}</div>
          <div class="flex items-center justify-end gap-1">
            <span class="h-2 w-2 rounded-full bg-green-500"></span>
            <span class="text-xs text-green-500">Онлайн</span>
          </div>
        </div>
      </div>
    </header>

    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 h-full w-64 bg-[#1F2937] pt-20">
      <nav class="p-4">
        <div class="space-y-1">
          <Link
            v-for="item in navItems"
            :key="item.id"
            :href="item.route"
            :class="[
              'flex items-center justify-between rounded-lg px-4 py-3 transition-all',
              activeTab === item.id
                ? 'bg-yellow-500/20 text-yellow-500'
                : 'text-gray-400 hover:bg-gray-700 hover:text-white'
            ]"
          >
            <div class="flex items-center gap-3">
              <!-- Icon -->
              <svg v-if="item.icon === 'clipboard'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
              <svg v-else-if="item.icon === 'users'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
              </svg>
              <svg v-else-if="item.icon === 'star'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
              </svg>
              <svg v-else-if="item.icon === 'chart'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
              </svg>
              <svg v-else-if="item.icon === 'map'" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
              </svg>
              <span class="font-medium">{{ item.name }}</span>
            </div>
            <!-- Счетчики для Заказов -->
            <div v-if="item.id === 'orders'" class="flex items-center gap-1">
              <span v-if="item.stats?.new > 0" class="px-1.5 py-0.5 rounded text-xs font-bold bg-blue-500 text-white">{{ item.stats.new }}</span>
              <span v-if="item.stats?.accepted > 0" class="px-1.5 py-0.5 rounded text-xs font-bold bg-yellow-500 text-gray-900">{{ item.stats.accepted }}</span>
              <span v-if="item.stats?.in_progress > 0" class="px-1.5 py-0.5 rounded text-xs font-bold bg-green-500 text-white">{{ item.stats.in_progress }}</span>
            </div>
            <span
              v-else-if="item.badge > 0"
              class="rounded-full bg-yellow-500 px-2 py-0.5 text-xs font-bold text-gray-900"
            >
              {{ item.badge }}
            </span>
          </Link>
        </div>
      </nav>

      <!-- Bottom Actions & Stats -->
      <div class="absolute bottom-0 left-0 right-0 border-t border-gray-700">
        <!-- Кнопка выхода -->
        <button 
          @click="logout"
          class="flex w-full items-center gap-3 px-4 py-3 text-red-500 hover:bg-red-500/10 transition"
        >
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
          </svg>
          Выйти
        </button>
        
        <!-- Stats -->
        <div class="border-t border-gray-700 p-4">
          <!-- Заказы -->
          <div class="text-sm text-gray-400">Заказы сегодня</div>
          <div class="mt-2 flex items-center gap-4">
            <div class="rounded-lg bg-gray-700 px-3 py-2">
              <div class="text-lg font-bold text-white">{{ realStats.completed || 0 }}</div>
              <div class="text-xs text-gray-400">выполнено</div>
            </div>
            <div class="rounded-lg bg-gray-700 px-3 py-2">
              <div class="text-lg font-bold text-yellow-500">{{ realStats.active || 0 }}</div>
              <div class="text-xs text-gray-400">активных</div>
            </div>
          </div>
          <div class="mt-3 rounded-lg bg-gray-800 px-3 py-2">
            <div class="text-xl font-bold text-white">{{ realStats.revenue || 0 }} ₽</div>
            <div class="text-xs text-gray-400">выручка</div>
          </div>

          <!-- Водители -->
          <div class="mt-4 text-sm text-gray-400">Водители</div>
          <div class="mt-2 flex items-center gap-4">
            <div class="rounded-lg bg-green-500/20 px-3 py-2">
              <div class="text-lg font-bold text-green-400">{{ driverStats.online || 0 }}</div>
              <div class="text-xs text-gray-400">онлайн</div>
            </div>
            <div class="rounded-lg bg-yellow-500/20 px-3 py-2">
              <div class="text-lg font-bold text-yellow-400">{{ driverStats.free || 0 }}</div>
              <div class="text-xs text-gray-400">свободны</div>
            </div>
            <div class="rounded-lg bg-orange-500/20 px-3 py-2">
              <div class="text-lg font-bold text-orange-400">{{ driverStats.busy || 0 }}</div>
              <div class="text-xs text-gray-400">в работе</div>
            </div>
          </div>
        </div>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="pl-64 pt-20">
      <div class="p-6">
        <slot />
      </div>
    </main>
  </div>
</template>
