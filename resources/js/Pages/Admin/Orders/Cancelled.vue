<script setup>
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
  orders: Object,
  stats: Object,
  filters: Object
})

const localFilters = ref({
  cancelled_by: props.filters.cancelled_by || '',
  date_from: props.filters.date_from || '',
  date_to: props.filters.date_to || ''
})

const applyFilters = () => {
  router.get('/admin/orders/cancelled', localFilters.value, { replace: true })
}

const resetFilters = () => {
  localFilters.value = {
    cancelled_by: '',
    date_from: '',
    date_to: ''
  }
  router.get('/admin/orders/cancelled', {}, { replace: true })
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
</script>

<template>
  <MainLayout>
    <div class="space-y-6">
      <!-- Заголовок -->
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-2xl font-bold text-white">Отменённые заказы</h1>
          <p class="text-gray-400">История отменённых заказов с причинами</p>
        </div>
        <a
          href="/admin/orders"
          class="rounded-lg bg-gray-700 px-4 py-2 text-white hover:bg-gray-600"
        >
          ← К активным заказам
        </a>
      </div>

      <!-- Статистика -->
      <div class="grid grid-cols-4 gap-4">
        <div class="rounded-lg bg-gray-800 p-4">
          <div class="text-2xl font-bold text-white">{{ stats.total }}</div>
          <div class="text-sm text-gray-400">Всего отменено</div>
        </div>
        <div class="rounded-lg bg-gray-800 p-4">
          <div class="text-2xl font-bold text-blue-400">{{ stats.by_passenger }}</div>
          <div class="text-sm text-gray-400">Отменено пассажирами</div>
        </div>
        <div class="rounded-lg bg-gray-800 p-4">
          <div class="text-2xl font-bold text-orange-400">{{ stats.by_driver }}</div>
          <div class="text-sm text-gray-400">Отменено водителями</div>
        </div>
        <div class="rounded-lg bg-gray-800 p-4">
          <div class="text-2xl font-bold text-purple-400">{{ stats.by_dispatcher }}</div>
          <div class="text-sm text-gray-400">Отменено диспетчерами</div>
        </div>
      </div>

      <!-- Фильтры -->
      <div class="rounded-lg bg-gray-800 p-4">
        <div class="flex flex-wrap items-end gap-4">
          <div>
            <label class="mb-1 block text-sm text-gray-400">Кто отменил</label>
            <select
              v-model="localFilters.cancelled_by"
              class="rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
            >
              <option value="">Все</option>
              <option value="passenger">Пассажир</option>
              <option value="driver">Водитель</option>
              <option value="dispatcher">Диспетчер</option>
            </select>
          </div>
          <div>
            <label class="mb-1 block text-sm text-gray-400">Дата от (c)</label>
            <input
              v-model="localFilters.date_from"
              type="date"
              class="rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm text-gray-400">Дата от (до)</label>
            <input
              v-model="localFilters.date_to"
              type="date"
              class="rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
            />
          </div>
          <button
            @click="applyFilters"
            class="rounded-lg bg-yellow-500 px-4 py-2 font-medium text-gray-900 hover:bg-yellow-600"
          >
            Применить
          </button>
          <button
            @click="resetFilters"
            class="rounded-lg border border-gray-600 px-4 py-2 text-gray-300 hover:bg-gray-700"
          >
            Сбросить
          </button>
        </div>
      </div>

      <!-- Таблица заказов -->
      <div class="overflow-x-auto rounded-lg bg-gray-800">
        <table class="w-full">
          <thead class="bg-gray-700">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">№</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Дата</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Маршрут</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Пассажир</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Водитель</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Кто отменил</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Причина</th>
              <th class="px-4 py-3 text-left text-sm font-medium text-gray-300">Сумма</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-700">
            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-700/50">
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

        <!-- Пагинация -->
        <div v-if="orders.total > orders.per_page" class="flex items-center justify-between border-t border-gray-700 px-4 py-3">
          <div class="text-sm text-gray-400">
            Показано {{ orders.from }}-{{ orders.to }} из {{ orders.total }}
          </div>
          <div class="flex gap-2">
            <a
              v-if="orders.prev_page_url"
              :href="orders.prev_page_url"
              class="rounded-lg border border-gray-600 px-3 py-1 text-sm text-gray-300 hover:bg-gray-700"
            >
              Назад
            </a>
            <a
              v-if="orders.next_page_url"
              :href="orders.next_page_url"
              class="rounded-lg border border-gray-600 px-3 py-1 text-sm text-gray-300 hover:bg-gray-700"
            >
              Вперёд
            </a>
          </div>
        </div>
      </div>
    </div>
  </MainLayout>
</template>
