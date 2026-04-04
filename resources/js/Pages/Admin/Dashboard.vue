<script setup>
import { computed } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  stats_today: Object,
  stats_week: Object,
  stats_month: Object,
  stats_total: Object,
  revenue_chart: Array,
  orders_chart: Array,
  active_orders: Number,
  completion_rate: Number,
  class_distribution: Array,
  recent_activity: Array,
  system_status: Object,
})

// Значения по умолчанию, если данные не загружены
const overview = computed(() => ({
  totalUsers: props.stats_total?.total_users ?? 0,
  totalDrivers: props.stats_total?.total_drivers ?? 0,
  activeDrivers: props.stats_today?.active_drivers ?? 0,
  activeOrders: props.active_orders ?? 0,
  revenueToday: props.stats_today?.earnings_today ?? 0,
  revenueMonth: props.stats_month?.earnings_month ?? 0,
  completionRate: props.completion_rate ?? 0
}))

const revenueChart = computed(() => props.revenue_chart ?? [])
const ordersChart = computed(() => props.orders_chart ?? [])
const classDistribution = computed(() => props.class_distribution ?? [])
const recentActivity = computed(() => props.recent_activity ?? [])
const systemStatus = computed(() => props.system_status ?? {
  server: true,
  database: true,
  api: true,
  connections: 0
})

const maxRevenue = computed(() => Math.max(...revenueChart.value.map(d => d.value), 1))
const maxOrders = computed(() => Math.max(...ordersChart.value.map(d => d.value), 1))
</script>

<template>
  <AdminLayout activeTab="dashboard">
    <!-- Page Header -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-white">Дашборд</h1>
      <p class="text-gray-400">Обзор системы TaxiVezi</p>
    </div>

    <!-- KPI Cards -->
    <div class="mb-6 grid grid-cols-6 gap-4">
      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <div class="mb-2 flex items-center gap-2 text-gray-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          <span class="text-sm">Всего пользователей</span>
        </div>
        <div class="flex items-end gap-2">
          <span class="text-3xl font-bold text-white">{{ overview.totalUsers.toLocaleString() }}</span>
        </div>
      </div>

      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <div class="mb-2 flex items-center gap-2 text-gray-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
          <span class="text-sm">Водителей онлайн</span>
        </div>
        <div class="flex items-end gap-2">
          <span class="text-3xl font-bold text-white">{{ overview.activeDrivers }}</span>
          <span class="text-sm text-gray-400">/ {{ overview.totalDrivers }}</span>
        </div>
      </div>

      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <div class="mb-2 flex items-center gap-2 text-gray-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          <span class="text-sm">Активных заказов</span>
        </div>
        <span class="text-3xl font-bold text-white">{{ overview.activeOrders }}</span>
      </div>

      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <div class="mb-2 flex items-center gap-2 text-gray-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-sm">Выручка сегодня</span>
        </div>
        <div class="flex items-end gap-2">
          <span class="text-3xl font-bold text-white">{{ Math.round(overview.revenueToday).toLocaleString() }} ₽</span>
        </div>
      </div>

      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <div class="mb-2 flex items-center gap-2 text-gray-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-sm">Выручка месяц</span>
        </div>
        <div class="flex items-end gap-2">
          <span class="text-3xl font-bold text-white">{{ Math.round(overview.revenueMonth / 1000).toFixed(0) }}K ₽</span>
        </div>
      </div>

      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <div class="mb-2 flex items-center gap-2 text-gray-400">
          <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span class="text-sm">Выполнение</span>
        </div>
        <div class="flex items-end gap-2">
          <span class="text-3xl font-bold text-white">{{ overview.completionRate }}%</span>
          <span class="text-sm text-green-500">✓</span>
        </div>
      </div>
    </div>

    <!-- Charts Row -->
    <div class="mb-6 grid grid-cols-2 gap-4">
      <!-- Revenue Chart -->
      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <h2 class="mb-4 text-lg font-bold text-white">Выручка (7 дней)</h2>
        <div style="height: 200px; display: flex; align-items: flex-end; justify-content: space-between; gap: 8px; padding-bottom: 24px;">
          <div v-for="day in revenueChart" :key="day.day" style="flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%;">
            <span class="text-xs text-green-400 font-bold mb-1">{{ day.value > 0 ? Math.round(day.value).toLocaleString() + ' ₽' : '0' }}</span>
            <div 
              :style="{ 
                width: '100%', 
                height: Math.max((day.value / maxRevenue * 100), 2) + '%', 
                backgroundColor: '#eab308',
                borderTopLeftRadius: '4px',
                borderTopRightRadius: '4px',
                minHeight: '4px'
              }"
            ></div>
            <span class="text-xs text-gray-400 mt-2">{{ day.day }}</span>
          </div>
        </div>
      </div>

      <!-- Orders Chart -->
      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <h2 class="mb-4 text-lg font-bold text-white">Заказы (сегодня)</h2>
        <div class="flex h-48 items-end justify-between gap-1">
          <div v-for="hour in ordersChart" :key="hour.hour" class="flex flex-1 flex-col items-center gap-2">
            <div 
              class="w-full rounded-t bg-blue-500 transition-all hover:bg-blue-600"
              :style="{ height: (hour.value / maxOrders * 100) + '%' }"
            ></div>
            <span class="text-xs text-gray-400">{{ hour.hour }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Second Row -->
    <div class="grid grid-cols-3 gap-4">
      <!-- Class Distribution -->
      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <h2 class="mb-4 text-lg font-bold text-white">Распределение по классам</h2>
        <div class="space-y-4">
          <div v-for="item in classDistribution" :key="item.name">
            <div class="mb-1 flex justify-between text-sm">
              <span class="text-gray-400">{{ item.name }}</span>
              <span class="text-white">{{ item.count?.toLocaleString() ?? 0 }} ({{ item.percent }}%)</span>
            </div>
            <div class="h-3 w-full rounded-full bg-gray-700">
              <div :class="['h-3 rounded-full', item.color]" :style="{ width: item.percent + '%' }"></div>
            </div>
          </div>
          <div v-if="classDistribution.length === 0" class="text-center text-gray-500 py-4">
            Нет данных
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <h2 class="mb-4 text-lg font-bold text-white">Активность</h2>
        <div class="space-y-3">
          <div v-for="(activity, index) in recentActivity" :key="index" class="flex items-start gap-3">
            <div :class="[
              'mt-0.5 flex h-6 w-6 items-center justify-center rounded-full',
              activity.type === 'order' ? 'bg-blue-500/20 text-blue-500' :
              activity.type === 'user' ? 'bg-green-500/20 text-green-500' :
              activity.type === 'driver' ? 'bg-yellow-500/20 text-yellow-500' :
              activity.type === 'payment' ? 'bg-green-500/20 text-green-500' :
              'bg-red-500/20 text-red-500'
            ]">
              <svg v-if="activity.type === 'order'" class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
              </svg>
              <svg v-else-if="activity.type === 'user'" class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              <svg v-else-if="activity.type === 'driver'" class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
              <svg v-else-if="activity.type === 'payment'" class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
              </svg>
              <svg v-else class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
            </div>
            <div class="flex-1">
              <div class="text-sm text-white">{{ activity.text }}</div>
              <div class="text-xs text-gray-500">{{ activity.time }}</div>
            </div>
          </div>
          <div v-if="recentActivity.length === 0" class="text-center text-gray-500 py-4">
            Нет активности
          </div>
        </div>
      </div>

      <!-- System Status -->
      <div class="rounded-xl border border-gray-700 bg-gray-800 p-4">
        <h2 class="mb-4 text-lg font-bold text-white">Статус системы</h2>
        <div class="space-y-3">
          <div class="flex items-center justify-between rounded-lg bg-gray-700/50 p-3">
            <div class="flex items-center gap-2">
              <span class="h-3 w-3 rounded-full" :class="systemStatus.server ? 'bg-green-500' : 'bg-red-500'"></span>
              <span class="text-gray-400">Сервер</span>
            </div>
            <span :class="systemStatus.server ? 'text-green-500' : 'text-red-500'">
              {{ systemStatus.server ? 'Онлайн' : 'Офлайн' }}
            </span>
          </div>
          <div class="flex items-center justify-between rounded-lg bg-gray-700/50 p-3">
            <div class="flex items-center gap-2">
              <span class="h-3 w-3 rounded-full" :class="systemStatus.database ? 'bg-green-500' : 'bg-red-500'"></span>
              <span class="text-gray-400">База данных</span>
            </div>
            <span :class="systemStatus.database ? 'text-green-500' : 'text-red-500'">
              {{ systemStatus.database ? 'Онлайн' : 'Офлайн' }}
            </span>
          </div>
          <div class="flex items-center justify-between rounded-lg bg-gray-700/50 p-3">
            <div class="flex items-center gap-2">
              <span class="h-3 w-3 rounded-full" :class="systemStatus.api ? 'bg-green-500' : 'bg-red-500'"></span>
              <span class="text-gray-400">API</span>
            </div>
            <span :class="systemStatus.api ? 'text-green-500' : 'text-red-500'">
              {{ systemStatus.api ? 'Онлайн' : 'Офлайн' }}
            </span>
          </div>
          <div class="mt-4 rounded-lg bg-gray-700/50 p-3 text-center">
            <div class="text-2xl font-bold text-white">{{ systemStatus.connections }}</div>
            <div class="text-sm text-gray-400">Активных подключений</div>
          </div>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
