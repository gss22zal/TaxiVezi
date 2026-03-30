<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import DispatcherLayout from '@/Layouts/DispatcherLayout.vue'

const props = defineProps({
  orders: Array,
  pagination: Object,
  filters: Object,
  stats: Object
})

const page = usePage()
const settings = computed(() => page.props.settings?.api || {})
const ordersRepeatTime = computed(() => settings.value.orders_repeat_time || 5000)

const activeTab = ref(props.filters?.status || 'all')
const orders = ref(props.orders || [])
let pollTimer = null

// Polling для обновления заказов в реальном времени
const refreshOrders = async () => {
  try {
    const response = await fetch(`/api/dispatcher/orders?status=${activeTab.value}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      orders.value = data.orders || []
    }
  } catch (e) {
    console.warn('Refresh orders error:', e)
  }
}

onMounted(() => {
  // Обновляем с интервалом из настроек
  pollTimer = setInterval(refreshOrders, ordersRepeatTime.value)
})

onUnmounted(() => {
  if (pollTimer) {
    clearInterval(pollTimer)
  }
})

const tabs = [
  { id: 'all', name: 'Все' },
  { id: 'new', name: 'Новые' },
  { id: 'accepted', name: 'Принятые' },
  { id: 'arrived', name: 'Прибыл' },
  { id: 'in_transit', name: 'В пути' },
  { id: 'completed', name: 'Завершённые' },
  { id: 'cancelled', name: 'Отменённые' },
]

const filteredOrders = computed(() => {
  if (!orders.value) return []
  if (activeTab.value === 'all') return orders.value
  return orders.value.filter(o => o.status === activeTab.value)
})

const getStatusBadge = (status) => {
  const badges = {
    new: { text: 'НОВЫЙ', class: 'bg-blue-600' },
    pending: { text: 'НОВЫЙ', class: 'bg-blue-600' },
    accepted: { text: 'ПРИНЯТ', class: 'bg-yellow-600' },
    arrived: { text: 'ПРИБЫЛ', class: 'bg-yellow-500' },
    started: { text: 'В ПУТИ', class: 'bg-green-600' },
    in_transit: { text: 'В ПУТИ', class: 'bg-blue-600' },
    in_progress: { text: 'В ПУТИ', class: 'bg-green-600' },
    completed: { text: 'ЗАВЕРШЁН', class: 'bg-gray-600' },
    cancelled: { text: 'ОТМЕНЁН', class: 'bg-red-600' }
  }
  return badges[status] || badges.new
}

const getClassBadge = (tariff) => {
  if (!tariff) return { text: 'Эконом', class: 'text-gray-400' }
  const classes = {
    business: { text: 'Бизнес', class: 'text-yellow-500' },
    comfort: { text: 'Комфорт', class: 'text-blue-500' },
    econom: { text: 'Эконом', class: 'text-gray-400' }
  }
  return classes[tariff] || classes.econom
}

const setTab = (id) => {
  activeTab.value = id
  router.get('/dispatcher/orders', { status: id }, { preserveState: true })
}

const formatPrice = (price) => {
  return price ? parseFloat(price).toLocaleString() : '0'
}

const getPassengerName = (order) => {
  if (order.passenger?.user) {
    return `${order.passenger.user.first_name} ${order.passenger.user.last_name || ''}`
  }
  return order.passenger_name || 'Пассажир'
}

const getDriverName = (order) => {
  if (order.driver?.user) {
    return `${order.driver.user.first_name} ${order.driver.user.last_name || ''}`
  }
  return null
}

const getTime = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}

const getDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit' })
}
</script>

<template>
  <DispatcherLayout activeTab="orders">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-white">Журнал заказов</h1>
      <button class="rounded-lg bg-yellow-500 px-4 py-2 font-medium text-gray-900 transition-all hover:bg-yellow-600">
        + Новый заказ
      </button>
    </div>

    <!-- Tabs -->
    <div class="mb-4 flex gap-1 border-b border-gray-700">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="setTab(tab.id)"
        :class="[
          'px-4 py-2 text-sm font-medium transition-all',
          activeTab === tab.id
            ? 'border-b-2 border-yellow-500 text-yellow-500'
            : 'text-gray-400 hover:text-white'
        ]"
      >
        {{ tab.name }}
      </button>
    </div>

    <!-- Orders List -->
    <div class="space-y-3">
      <div
        v-for="order in filteredOrders"
        :key="order.id"
        class="cursor-pointer rounded-xl border border-gray-700 bg-gray-800 p-4 transition-all hover:border-gray-600"
      >
        <div class="mb-3 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-sm text-gray-400">{{ order.order_number || '№' + order.id }}</span>
            <span :class="['rounded px-2 py-0.5 text-xs font-medium uppercase', getStatusBadge(order.status).class]">
              {{ getStatusBadge(order.status).text }}
            </span>
            <!-- Рейтинг если есть отзыв -->
            <span v-if="order.review?.passenger_rating" class="flex items-center gap-1 text-yellow-400 text-sm">
              <span>★</span>
              <span>{{ order.review.passenger_rating }}</span>
            </span>
          </div>
          <div class="text-right">
            <div class="flex items-center justify-end gap-2">
              <span :class="['text-sm font-medium', getClassBadge(order.tariff?.code).class]">
                {{ getClassBadge(order.tariff?.code).text }}
              </span>
              <div class="text-xl font-bold text-white">{{ formatPrice(order.final_price) }} ₽</div>
            </div>
            <div class="text-sm text-gray-400">{{ order.distance || 0 }} км</div>
          </div>
        </div>

        <div class="mb-3 space-y-2">
          <div class="flex items-center gap-2">
            <div class="h-2 w-2 rounded-full bg-green-500"></div>
            <span class="text-white">{{ order.pickup_address || 'Адрес подачи' }}</span>
          </div>
          <div class="flex items-center gap-2">
            <div class="h-2 w-2 rounded-full bg-orange-500"></div>
            <span class="text-gray-400">{{ order.dropoff_address || 'Адрес назначения' }}</span>
          </div>
        </div>

        <div class="flex items-center justify-between text-sm">
          <div class="flex items-center gap-2 text-gray-400">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            {{ getPassengerName(order) }}
          </div>
          <div class="flex items-center gap-4">
            <span v-if="getDriverName(order)" class="flex items-center gap-1 text-yellow-500">
              <span class="h-2 w-2 rounded-full bg-yellow-500"></span>
              {{ getDriverName(order) }}
            </span>
            <span v-if="order.notes" class="text-gray-500">{{ order.notes }}</span>
            <span class="text-gray-500">{{ getTime(order.created_at) }}</span>
          </div>
        </div>
        
        <!-- Информация об отмене -->
        <div v-if="order.status === 'cancelled'" class="mt-3 rounded-lg bg-red-900/30 p-2 text-sm">
          <div class="flex items-center gap-2">
            <span v-if="order.cancelled_by === 'passenger'" class="text-blue-400">Отменено пассажиром</span>
            <span v-else-if="order.cancelled_by === 'driver'" class="text-orange-400">Отменено водителем</span>
            <span v-else class="text-red-400">Отменено</span>
          </div>
          <div v-if="order.cancellation_reason" class="text-gray-400 mt-1">
            Причина: {{ order.cancellation_reason }}
          </div>
        </div>
      </div>

      <div v-if="filteredOrders.length === 0" class="py-8 text-center text-gray-400">
        Заказов не найдено
      </div>
    </div>
  </DispatcherLayout>
</template>
