<script setup>
import { ref, computed } from 'vue'
import DispatcherLayout from '@/Layouts/DispatcherLayout.vue'

const props = defineProps({
  stats_today: Object,
  stats_week: Object,
  stats_month: Object,
  stats_total: Object,
  orders_by_day: Array,
  top_drivers: Array
})

const period = ref('today')

const periods = [
  { id: 'today', name: 'Сегодня' },
  { id: 'week', name: 'Неделя' },
  { id: 'month', name: 'Месяц' },
]

const currentStats = computed(() => {
  switch (period.value) {
    case 'week':
      return props.stats_week || {}
    case 'month':
      return props.stats_month || {}
    default:
      return props.stats_today || {}
  }
})

const formatPrice = (price) => {
  return price ? parseFloat(price).toLocaleString() : '0'
}

const getMaxOrders = () => {
  if (!props.orders_by_day) return 1
  return Math.max(...props.orders_by_day.map(d => d.completed), 1)
}
</script>

<template>
  <DispatcherLayout activeTab="analytics">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-white">Аналитика</h1>
      <div class="flex gap-2">
        <button
          v-for="p in periods"
          :key="p.id"
          @click="period = p.id"
          :class="[
            'rounded-lg px-3 py-1.5 text-sm font-medium transition-all',
            period === p.id
              ? 'bg-yellow-500 text-gray-900'
              : 'bg-gray-700 text-gray-400 hover:bg-gray-600'
          ]"
        >
          {{ p.name }}
        </button>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="mb-6 grid grid-cols-4 gap-4">
      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <div class="mb-2 flex items-center gap-2 text-gray-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          <span class="text-sm">Заказов</span>
        </div>
        <div class="flex items-end gap-2">
          <span class="text-3xl font-bold text-white">{{ currentStats.orders_count || 0 }}</span>
        </div>
      </div>

      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <div class="mb-2 flex items-center gap-2 text-gray-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-sm">Выполнено</span>
        </div>
        <div class="flex items-end gap-2">
          <span class="text-3xl font-bold text-green-500">{{ currentStats.orders_completed || 0 }}</span>
        </div>
      </div>

      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <div class="mb-2 flex items-center gap-2 text-gray-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-sm">Выручка</span>
        </div>
        <div class="flex items-end gap-2">
          <span class="text-3xl font-bold text-yellow-500">{{ formatPrice(currentStats.earnings_today || currentStats.earnings_week || currentStats.earnings_month || 0) }} ₽</span>
        </div>
      </div>

      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <div class="mb-2 flex items-center gap-2 text-gray-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
          <span class="text-sm">Активные водители</span>
        </div>
        <div class="flex items-end gap-2">
          <span class="text-3xl font-bold text-blue-500">{{ stats_today?.active_drivers || 0 }}</span>
        </div>
      </div>
    </div>

    <!-- График заказов за неделю -->
    <div class="mb-6 rounded-xl bg-gray-800 p-6 border border-gray-700">
      <h2 class="mb-4 text-lg font-semibold text-white">Завершённые заказы за 7 дней</h2>
      <div class="flex items-end justify-between gap-2 h-40">
        <div 
          v-for="day in orders_by_day" 
          :key="day.date"
          class="flex flex-1 flex-col items-center gap-2"
        >
          <span class="text-xs text-green-400 font-bold">{{ day.completed || '' }}</span>
          <div 
            class="w-full max-w-8 rounded bg-green-500 transition-all hover:bg-green-400"
            :style="{ height: Math.max((day.completed / getMaxOrders() * 100), day.completed > 0 ? 8 : 0) + 'px', minHeight: day.completed > 0 ? '8px' : '0' }"
          ></div>
          <span class="text-xs text-gray-400">{{ day.day_name }}</span>
        </div>
      </div>
    </div>

    <!-- Top Drivers -->
    <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
      <h2 class="mb-4 text-lg font-bold text-white">Топ водителей за неделю</h2>
      <div class="space-y-2">
        <div
          v-for="(driver, index) in top_drivers"
          :key="driver.id"
          class="flex items-center justify-between rounded-lg bg-gray-700/50 p-3"
        >
          <div class="flex items-center gap-3">
            <span :class="[
              'flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold',
              index === 0 ? 'bg-yellow-500 text-gray-900' : 'bg-gray-600 text-white'
            ]">
              {{ index + 1 }}
            </span>
            <span class="font-medium text-white">{{ driver.name }}</span>
          </div>
          <span class="font-bold text-yellow-500">{{ driver.orders_count }} заказов</span>
        </div>
        <div v-if="!top_drivers || top_drivers.length === 0" class="text-center text-gray-500 py-4">
          Нет данных
        </div>
      </div>
    </div>
  </DispatcherLayout>
</template>
