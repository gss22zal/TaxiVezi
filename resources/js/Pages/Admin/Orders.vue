<script setup>
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  orders: {
    type: Array,
    default: () => []
  },
  pagination: {
    type: Object,
    default: () => ({})
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  stats: {
    type: Object,
    default: () => ({})
  },
  serverDate: {
    type: String,
    default: ''
  }
})

const activeTab = ref(props.filters?.status || 'all')

// Фильтры по дате
const dateFrom = ref(props.filters?.date_from || '')
const dateTo = ref(props.filters?.date_to || '')

const activeDateFilter = ref('')

// Кнопки-фильтры - вычисляемые, зависят от serverDate
const dateButtons = computed(() => {
  const getServerDate = () => {
    if (props.serverDate) {
      // Просто используем строку даты напрямую, не парсим через Date
      // Сервер уже присылает дату в формате YYYY-MM-DD
      return props.serverDate
    }
    // Запасной вариант - используем UTC дату
    const now = new Date()
    return now.toISOString().split('T')[0]
  }

  const serverDate = getServerDate()

  return [
    { id: 'today', name: 'Сегодня', getDates: () => {
      return { from: serverDate, to: serverDate }
    }},
    { id: 'yesterday', name: 'Вчера', getDates: () => {
      // Вычисляем вчерашний день
      const d = new Date(serverDate + 'T00:00:00+00:00')
      d.setDate(d.getDate() - 1)
      const yesterday = d.toISOString().split('T')[0]
      return { from: yesterday, to: yesterday }
    }},
    { id: 'week', name: 'За неделю', getDates: () => {
      const to = serverDate
      const d = new Date(serverDate + 'T00:00:00+00:00')
      d.setDate(d.getDate() - 7)
      const from = d.toISOString().split('T')[0]
      return { from, to }
    }},
    { id: 'month', name: 'За месяц', getDates: () => {
      const to = serverDate
      const d = new Date(serverDate + 'T00:00:00+00:00')
      d.setMonth(d.getMonth() - 1)
      const from = d.toISOString().split('T')[0]
      return { from, to }
    }},
    { id: 'month_start', name: 'С начала месяца', getDates: () => {
      const d = new Date(serverDate + 'T00:00:00+00:00')
      d.setDate(1)
      const from = d.toISOString().split('T')[0]
      return { from, to: '' }
    }},
  ]
})

const setDateFilter = (button) => {
  activeDateFilter.value = button.id
  const dates = button.getDates()
  dateFrom.value = dates.from
  dateTo.value = dates.to
  applyFilters()
}

const clearDateFilter = () => {
  activeDateFilter.value = ''
  dateFrom.value = ''
  dateTo.value = ''
  applyFilters()
}

const applyFilters = () => {
  const params = {
    status: activeTab.value,
    date_from: dateFrom.value,
    date_to: dateTo.value,
  }
  router.get(route('admin.orders'), params, { preserveState: true })
}

const tabs = [
  { id: 'all', name: 'Все', color: 'bg-gray-500' },
  { id: 'new', name: 'Новые', color: 'bg-green-600' },
  { id: 'accepted', name: 'Принятые', color: 'bg-blue-600' },
  { id: 'arrived', name: 'Прибыл', color: 'bg-yellow-600' },
  { id: 'in_transit', name: 'В пути', color: 'bg-orange-500' },
  { id: 'completed', name: 'Завершённые', color: 'bg-gray-600' },
  { id: 'cancelled', name: 'Отменённые', color: 'bg-red-600' },
]

// Подсчёт количества заказов по статусам - с сервера
const tabCounts = computed(() => {
  if (!props.stats) return {}
  return {
    all: props.stats.all || 0,
    new: props.stats.new || 0,
    accepted: props.stats.accepted || 0,
    arrived: props.stats.arrived || 0,
    in_transit: props.stats.in_transit || 0,
    completed: props.stats.completed || 0,
    cancelled: props.stats.cancelled || 0,
  }
})

const filteredOrders = computed(() => {
  if (!props.orders) return []
  // Заказы уже отфильтрованы сервером по статусу
  return props.orders
})

const setTab = (id) => {
  activeTab.value = id
  router.get(route('admin.orders'), { 
    status: id,
    date_from: dateFrom.value,
    date_to: dateTo.value,
  }, { preserveState: true })
}

const getStatusBadge = (status) => {
  const badges = {
    new: { text: 'НОВЫЙ', class: 'bg-green-600' },
    pending: { text: 'НОВЫЙ', class: 'bg-green-600' },
    accepted: { text: 'ПРИНЯТ', class: 'bg-blue-600' },
    arrived: { text: 'ПРИБЫЛ', class: 'bg-yellow-600' },
    started: { text: 'В ПУТИ', class: 'bg-orange-500' },
    in_transit: { text: 'В ПУТИ', class: 'bg-orange-500' },
    in_progress: { text: 'В ПУТИ', class: 'bg-orange-500' },
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

const formatPrice = (price) => {
  return price ? parseFloat(price).toLocaleString() : '0'
}

const getPassengerName = (order) => {
  if (order.passenger?.user) {
    return `${order.passenger.user.first_name} ${order.passenger.user.last_name || ''}`
  }
  return order.passenger_name || 'Пассажир'
}

const getPassengerPhone = (order) => {
  return order.passenger?.user?.phone || '-'
}

const getDriverName = (order) => {
  if (order.driver?.user) {
    return `${order.driver.user.first_name} ${order.driver.user.last_name || ''}`
  }
  return null
}

const getDriverPhone = (order) => {
  return order.driver?.user?.phone || '-'
}

const getTime = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleTimeString('ru-RU', { hour: '2-digit', minute: '2-digit' })
}

const getDate = (date) => {
  if (!date) return ''
  return new Date(date).toLocaleDateString('ru-RU', { day: '2-digit', month: '2-digit', year: 'numeric' })
}
</script>

<template>
  <AdminLayout activeTab="orders">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-white">Заказы</h1>
    </div>

    <!-- Date Filters -->
    <div class="mb-4 flex flex-wrap items-center gap-4">
      <!-- Кнопки-фильтры -->
      <div class="flex gap-2">
        <button 
          v-for="btn in dateButtons"
          :key="btn.id"
          @click="setDateFilter(btn)"
          :class="[
            'px-3 py-1.5 text-sm rounded-lg transition-all',
            activeDateFilter === btn.id
              ? 'bg-red-500 text-white' 
              : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
          ]"
        >
          {{ btn.name }}
        </button>
      </div>

      <!-- Выбор дат -->
      <div class="flex items-center gap-2">
        <input
          type="date"
          v-model="dateFrom"
          @change="activeDateFilter = ''"
          class="rounded-lg bg-gray-700 px-3 py-1.5 text-sm text-white border border-gray-600"
        />
        <span class="text-gray-400">—</span>
        <input
          type="date"
          v-model="dateTo"
          @change="activeDateFilter = ''"
          class="rounded-lg bg-gray-700 px-3 py-1.5 text-sm text-white border border-gray-600"
        />
        <button
          v-if="dateFrom || dateTo"
          @click="clearDateFilter"
          class="px-3 py-1.5 text-sm text-gray-400 hover:text-white"
        >
          ✕
        </button>
        <button
          @click="applyFilters"
          class="px-4 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-500"
        >
          Применить
        </button>
      </div>
    </div>

    <!-- Tabs -->
    <div class="mb-4 flex gap-1 border-b border-gray-700">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="setTab(tab.id)"
        :class="[
          'px-4 py-2 text-sm font-medium transition-all flex items-center gap-2',
          activeTab === tab.id
            ? 'border-b-2 border-red-500 text-red-500'
            : 'text-gray-400 hover:text-white'
        ]"
      >
        <span :class="['w-2 h-2 rounded-full', tab.color]"></span>
        {{ tab.name }}
        <span v-if="tabCounts[tab.id] > 0" class="ml-1 text-xs opacity-70">({{ tabCounts[tab.id] }})</span>
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
            <div class="text-xl font-bold text-white">{{ formatPrice(order.final_price) }} ₽</div>
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
          <div class="flex items-center gap-4">
            <div class="flex items-center gap-2 text-gray-400">
              <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
              </svg>
              <span class="text-white">{{ getPassengerName(order) }}</span>
              <span class="text-gray-500">{{ getPassengerPhone(order) }}</span>
            </div>
          </div>
          <div class="flex items-center gap-4">
            <span v-if="getDriverName(order)" class="flex items-center gap-2 text-gray-400">
              <svg class="h-4 w-4 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
              <span class="text-yellow-500">{{ getDriverName(order) }}</span>
              <span class="text-gray-500">{{ getDriverPhone(order) }}</span>
            </span>
            <span v-else class="text-gray-500">Без водителя</span>
            <span class="text-gray-500">{{ getDate(order.created_at) }} {{ getTime(order.created_at) }}</span>
          </div>
        </div>
      </div>

      <div v-if="filteredOrders.length === 0" class="py-8 text-center text-gray-400">
        Заказов не найдено
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="mt-4 flex items-center justify-between">
      <div class="text-sm text-gray-400">
        Показано {{ filteredOrders.length }} из {{ pagination.total }}
      </div>
      <div class="flex gap-2">
        <button 
          v-for="page in pagination.last_page" 
          :key="page"
          @click="router.get(route('admin.orders'), { page, status: activeTab, date_from: dateFrom, date_to: dateTo }, { preserveState: true })"
          :class="[
            'rounded px-3 py-1 text-sm',
            page === pagination.current_page 
              ? 'bg-red-500 text-white' 
              : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
          ]"
        >
          {{ page }}
        </button>
      </div>
    </div>
  </AdminLayout>
</template>