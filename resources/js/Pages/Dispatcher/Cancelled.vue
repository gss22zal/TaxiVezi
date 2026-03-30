<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import MainLayout from '@/Layouts/MainLayout.vue'

const orders = ref([])
const stats = ref({ total: 0, by_passenger: 0, by_driver: 0 })
const isLoading = ref(true)
let pollInterval = null

const loadOrders = async () => {
  try {
    const response = await fetch('/api/dispatcher/orders/cancelled', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      orders.value = data.orders
      stats.value = data.stats
    }
  } catch (e) {
    console.error('Ошибка загрузки:', e)
  } finally {
    isLoading.value = false
  }
}

const getCancelledByLabel = (value) => {
  switch (value) {
    case 'passenger': return 'Пассажир'
    case 'driver': return 'Водитель'
    case 'dispatcher': return 'Диспетчер'
    default: return value
  }
}

const getCancelledByColor = (value) => {
  switch (value) {
    case 'passenger': return 'bg-blue-500'
    case 'driver': return 'bg-orange-500'
    case 'dispatcher': return 'bg-purple-500'
    default: return 'bg-gray-500'
  }
}

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleString('ru-RU')
}

onMounted(() => {
  loadOrders()
  // Обновляем каждые 30 секунд
  pollInterval = setInterval(loadOrders, 30000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Заголовок -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-white">Отменённые заказы</h1>
          <p class="text-gray-400">История отменённых заказов</p>
        </div>
        <a
          href="/dispatcher/orders"
          class="rounded-lg bg-gray-700 px-4 py-2 text-white hover:bg-gray-600"
        >
          ← К активным заказам
        </a>
      </div>

      <!-- Статистика -->
      <div class="grid grid-cols-3 gap-4">
        <div class="rounded-lg bg-gray-800 p-4">
          <div class="text-2xl font-bold text-white">{{ stats.total }}</div>
          <div class="text-sm text-gray-400">Всего отменено</div>
        </div>
        <div class="rounded-lg bg-gray-800 p-4">
          <div class="text-2xl font-bold text-blue-400">{{ stats.by_passenger }}</div>
          <div class="text-sm text-gray-400">Пассажирами</div>
        </div>
        <div class="rounded-lg bg-gray-800 p-4">
          <div class="text-2xl font-bold text-orange-400">{{ stats.by_driver }}</div>
          <div class="text-sm text-gray-400">Водителями</div>
        </div>
      </div>

      <!-- Таблица заказов -->
      <div v-if="isLoading" class="flex items-center justify-center py-12">
        <div class="text-gray-400">Загрузка...</div>
      </div>
      
      <div v-else-if="orders.length === 0" class="rounded-lg bg-gray-800 p-8 text-center">
        <div class="text-gray-400">Нет отменённых заказов</div>
      </div>

      <div v-else class="overflow-x-auto rounded-lg bg-gray-800">
        <table class="w-full">
          <thead class="bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">№</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Дата отмены</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Маршрут</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Пассажир</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Водитель</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Кто отменил</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Причина</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Сумма</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-700">
            <tr v-for="order in orders" :key="order.id" class="hover:bg-gray-700/50">
              <td class="px-4 py-3 text-sm text-white">{{ order.order_number }}</td>
              <td class="px-4 py-3 text-sm text-gray-400">{{ formatDate(order.cancelled_at) }}</td>
              <td class="px-4 py-3 text-sm text-white">
                <div class="max-w-xs truncate">{{ order.pickup_address }}</div>
                <div class="max-w-xs truncate text-gray-500">→ {{ order.dropoff_address }}</div>
              </td>
              <td class="px-4 py-3 text-sm text-white">
                <div v-if="order.passenger?.user">
                  {{ order.passenger.user.first_name }} {{ order.passenger.user.last_name }}
                </div>
                <div v-else class="text-gray-500">-</div>
              </td>
              <td class="px-4 py-3 text-sm text-white">
                <div v-if="order.driver?.user">
                  {{ order.driver.user.first_name }} {{ order.driver.user.last_name }}
                </div>
                <div v-else class="text-gray-500">-</div>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="[
                    'rounded-full px-2 py-1 text-xs font-medium text-white',
                    getCancelledByColor(order.cancelled_by)
                  ]"
                >
                  {{ getCancelledByLabel(order.cancelled_by) }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-gray-300 max-w-xs">
                <div v-if="order.cancellation_reason" class="truncate">
                  {{ order.cancellation_reason }}
                </div>
                <div v-else class="text-gray-500">-</div>
              </td>
              <td class="px-4 py-3 text-sm text-white">{{ order.final_price || 0 }} ₽</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </MainLayout>
</template>
