<script setup>
import { Link, usePage, router } from '@inertiajs/vue3'
import { computed, ref, onMounted } from 'vue'
import { useOrderNotifications } from '@/composables/useOrderNotifications'

const props = defineProps({
  activeTab: {
    type: String,
    default: 'dashboard'
  }
})

const page = usePage()
const currentPath = computed(() => page.url)
const orderStats = computed(() => page.props?.orderStats || { new: 0, accepted: 0, in_progress: 0 })

// Включаем real-time оповещения для админа
useOrderNotifications({
  onNewOrder: (stats) => {
    console.log('🔔 Новый заказ!', stats)
  }
})

// Проверка - админ ли текущий пользователь
const isAdmin = computed(() => {
  const userRole = page.props?.auth?.user?.role
  return userRole === 'admin' || userRole === 'super_admin'
})

const expandedMenus = ref([])

// Проверка: активен ли пункт меню (по URL)
const isMenuItemActive = (item) => {
  if (item.children) {
    return item.children.some(child => currentPath.value.startsWith(child.route))
  }
  if (item.id === 'dashboard') {
    return currentPath.value === '/admin' || currentPath.value === '/admin/'
  }
  return currentPath.value.startsWith(item.route + '/') || currentPath.value === item.route
}

const isMenuExpanded = (item) => {
  if (!item.children) return false
  return item.children.some(child => currentPath.value.startsWith(child.route)) || 
         expandedMenus.value.includes(item.id)
}

const toggleMenu = (id) => {
  const index = expandedMenus.value.indexOf(id)
  if (index > -1) {
    expandedMenus.value.splice(index, 1)
  } else {
    expandedMenus.value.push(id)
  }
}

const currentDateTime = computed(() => {
  const now = new Date()
  const months = ['ЯНВАРЯ', 'ФЕВРАЛЯ', 'МАРТА', 'АПРЕЛЯ', 'МАЯ', 'ИЮНЯ', 'ИЮЛЯ', 'АВГУСТА', 'СЕНТЯБРЯ', 'ОКТЯБРЯ', 'НОЯБРЯ', 'ДЕКАБРЯ']
  return `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}, ${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`
})

const navItems = computed(() => [
  { id: 'dashboard', name: 'Дашборд', icon: 'chart', route: '/admin' },
  { 
    id: 'users', name: 'Пользователи', icon: 'users', route: '/admin/users/drivers',
    children: [
      { name: 'Водители', route: '/admin/users/drivers' },
      { name: 'Пассажиры', route: '/admin/users/passengers' },
      { name: 'Диспетчеры', route: '/admin/users/dispatchers' }
    ]
  },
  { 
    id: 'orders', name: 'Заказы', icon: 'clipboard', route: '/admin/orders',
    stats: orderStats.value
  },
  { id: 'reviews', name: 'Отзывы', icon: 'star', route: '/admin/reviews' },
  { 
    id: 'finance', name: 'Финансы', icon: 'currency', route: '/admin/finance',
    children: [
      { name: 'Транзакции', route: '/admin/finance/transactions' },
      { name: 'Выплаты', route: '/admin/finance/payouts' },
      { name: 'Налоги', route: '/admin/finance/taxes' }
    ]
  },
  { id: 'analytics', name: 'Аналитика', icon: 'analytics', route: '/admin/analytics' },
  { id: 'tariffs', name: 'Тарифы', icon: 'tariff', route: '/admin/tariffs' },
  { id: 'settings', name: 'Настройки', icon: 'settings', route: '/admin/settings' },
  { id: 'roles', name: 'Роли и права', icon: 'shield', route: '/admin/roles' },
  { id: 'notifications', name: 'Уведомления', icon: 'bell', route: '/admin/notifications' },
  { id: 'database', name: 'База данных', icon: 'database', route: '/admin/database' },
  { id: 'logs', name: 'Логи системы', icon: 'document', route: '/admin/logs' },
])

const switchRole = (role) => {
  const routes = { passenger: '/passenger', driver: '/driver', dispatcher: '/dispatcher/orders' }
  if (routes[role]) window.location.href = routes[role]
}

const logout = () => {
  router.post(route('logout'))
}

// Простой рендер иконок
const renderIcon = (iconName, className = 'h-5 w-5') => {
  const icons = {
    chart: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>',
    users: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>',
    clipboard: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>',
    star: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
    currency: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    analytics: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
    tariff: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>',
    settings: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
    shield: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>',
    bell: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
    database: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>',
    document: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
  }
  return `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24">${icons[iconName] || ''}</svg>`
}
</script>

<template>
  <div class="min-h-screen bg-[#0F172A] text-white">
    
    <!-- Header -->
    <header class="fixed left-0 right-0 top-0 z-50 bg-[#1F2937] px-4 py-3 border-b border-gray-700">
      <div class="flex items-center justify-between pl-64">
        <!-- Logo -->
        <div class="flex items-center gap-2">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-500">
            <svg class="h-6 w-6 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
              <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
            </svg>
          </div>
          <div>
            <div class="text-lg font-bold">TaxiVezi</div>
            <div class="text-xs text-red-500 font-medium">Админ-панель</div>
          </div>
        </div>

        <!-- Role Switcher - только для админа -->
        <div v-if="isAdmin" class="flex gap-2">
          <button @click="switchRole('passenger')" class="px-3 py-2 rounded-lg text-sm bg-gray-700 hover:bg-gray-600 transition">Пассажир</button>
          <button @click="switchRole('driver')" class="px-3 py-2 rounded-lg text-sm bg-gray-700 hover:bg-gray-600 transition">Водитель</button>
          <button @click="switchRole('dispatcher')" class="px-3 py-2 rounded-lg text-sm bg-gray-700 hover:bg-gray-600 transition">Диспетчер</button>
          <button class="px-3 py-2 rounded-lg text-sm bg-red-500 text-white font-medium">Администратор</button>
        </div>

        <!-- Right Section -->
        <div class="flex items-center gap-4">
          <div class="text-right">
            <div class="text-sm font-medium text-gray-300">{{ currentDateTime }}</div>
            <div class="flex items-center justify-end gap-1">
              <span class="h-2 w-2 rounded-full bg-green-500"></span>
              <span class="text-xs text-green-500">Онлайн</span>
            </div>
          </div>
          <button
            @click="logout"
            class="flex items-center gap-2 rounded-lg bg-gray-700 px-3 py-2 text-sm text-gray-300 hover:bg-red-600 hover:text-white transition"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Выйти
          </button>
        </div>
      </div>
    </header>

    <!-- Sidebar -->
    <aside class="fixed left-0 top-0 h-full w-64 bg-[#1F2937] pt-20 overflow-y-auto border-r border-gray-700 z-40">
      <nav class="p-4 pb-24">
        <div class="space-y-1">
          <template v-for="item in navItems" :key="item.id">
            
            <!-- Parent with children -->
            <div v-if="item.children">
              <button
                @click="toggleMenu(item.id)"
                :class="[
                  'flex w-full items-center justify-between rounded-lg px-4 py-3 transition-all',
                  isMenuItemActive(item) ? 'bg-red-500/20 text-red-500' : 'text-gray-400 hover:bg-gray-700 hover:text-white'
                ]"
              >
                <div class="flex items-center gap-3">
                  <span v-html="renderIcon(item.icon)" class="flex-shrink-0"></span>
                  <span class="font-medium">{{ item.name }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <!-- Счетчики для Заказов -->
                  <template v-if="item.id === 'orders'">
                    <span v-if="item.stats?.new > 0" class="px-1.5 py-0.5 rounded text-xs font-bold bg-blue-500 text-white">{{ item.stats.new }}</span>
                    <span v-if="item.stats?.accepted > 0" class="px-1.5 py-0.5 rounded text-xs font-bold bg-yellow-500 text-gray-900">{{ item.stats.accepted }}</span>
                    <span v-if="item.stats?.in_progress > 0" class="px-1.5 py-0.5 rounded text-xs font-bold bg-green-500 text-white">{{ item.stats.in_progress }}</span>
                  </template>
                  <span v-else-if="item.badge" class="bg-red-500 px-2 py-0.5 rounded-full text-xs font-bold">{{ item.badge }}</span>
                  <svg class="h-4 w-4 transition-transform" :class="isMenuExpanded(item) ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                  </svg>
                </div>
              </button>
              
              <!-- Children -->
              <div v-if="isMenuExpanded(item)" class="ml-4 mt-1 space-y-1">
                <Link
                  v-for="child in item.children"
                  :key="child.route"
                  :href="child.route"
                  :class="[
                    'block rounded-lg px-4 py-2 text-sm transition-all',
                    currentPath === child.route ? 'text-red-500 bg-red-500/10' : 'text-gray-400 hover:bg-gray-700 hover:text-white'
                  ]"
                >
                  {{ child.name }}
                </Link>
              </div>
            </div>
            
            <!-- Simple item -->
            <Link
              v-else
              :href="item.route"
              :class="[
                'flex items-center justify-between rounded-lg px-4 py-3 transition-all',
                isMenuItemActive(item) ? 'bg-red-500/20 text-red-500' : 'text-gray-400 hover:bg-gray-700 hover:text-white'
              ]"
            >
              <div class="flex items-center gap-3">
                <span v-html="renderIcon(item.icon)" class="flex-shrink-0"></span>
                <span class="font-medium">{{ item.name }}</span>
              </div>
              <!-- Счетчики для Заказов -->
              <div v-if="item.id === 'orders'" class="flex items-center gap-1">
                <span v-if="item.stats?.new > 0" class="px-1.5 py-0.5 rounded text-xs font-bold bg-blue-500 text-white">{{ item.stats.new }}</span>
                <span v-if="item.stats?.accepted > 0" class="px-1.5 py-0.5 rounded text-xs font-bold bg-yellow-500 text-gray-900">{{ item.stats.accepted }}</span>
                <span v-if="item.stats?.in_progress > 0" class="px-1.5 py-0.5 rounded text-xs font-bold bg-green-500 text-white">{{ item.stats.in_progress }}</span>
              </div>
              <span v-else-if="item.badge" class="bg-red-500 px-2 py-0.5 rounded-full text-xs font-bold">{{ item.badge }}</span>
            </Link>
            
          </template>
        </div>

        <!-- Bottom Actions -->
        <div class="absolute bottom-0 left-0 right-0 border-t border-gray-700 bg-[#1F2937] p-4">
          <button class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-gray-400 hover:bg-gray-700 hover:text-white transition">
            <span v-html="renderIcon('document')" class="flex-shrink-0"></span>
            Помощь
          </button>
          <button @click="logout" class="flex w-full items-center gap-3 rounded-lg px-4 py-3 text-red-500 hover:bg-red-500/10 transition">
            <svg class="h-5 w-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
            Выйти
          </button>
        </div>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="pl-64 pt-20 min-h-screen">
      <div class="p-6">
        <slot />
      </div>
    </main>
    
  </div>
</template>
