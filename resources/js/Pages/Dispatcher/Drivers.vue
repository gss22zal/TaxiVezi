<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import DispatcherLayout from '@/Layouts/DispatcherLayout.vue'

const page = usePage()
const settings = computed(() => page.props.settings?.api || {})
const driverRepeatTime = computed(() => settings.value.driver_repeat_time || 3000)

const drivers = ref([])
let pollTimer = null

// Загрузка статусов водителей
const loadDriverStatuses = async () => {
  try {
    const response = await fetch('/api/driver-statuses', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      drivers.value = data.drivers || []
    }
  } catch (e) {
    console.warn('Load driver statuses error:', e)
  }
}

const onlineCount = computed(() => drivers.value.filter(d => d.is_online).length)
const totalDrivers = computed(() => drivers.value.length)

onMounted(() => {
  loadDriverStatuses()
  // Обновляем с интервалом из настроек
  pollTimer = setInterval(loadDriverStatuses, driverRepeatTime.value)
})

onUnmounted(() => {
  if (pollTimer) {
    clearInterval(pollTimer)
  }
})

const getStatusBadge = (driver) => {
  if (!driver.is_online) {
    return { text: 'ОФЛАЙН', class: 'bg-gray-600' }
  }
  return driver.is_busy 
    ? { text: 'ЗАНЯТ', class: 'bg-yellow-600' }
    : { text: 'СВОБОДЕН', class: 'bg-green-600' }
}

const getStatusDot = (driver) => {
  if (!driver.is_online) {
    return 'bg-gray-500'
  }
  return driver.is_busy ? 'bg-yellow-500' : 'bg-green-500'
}
</script>

<template>
  <DispatcherLayout activeTab="drivers">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Водители</h1>
        <p class="text-sm text-gray-400">Онлайн: {{ onlineCount }} из {{ totalDrivers }}</p>
      </div>
      <button class="rounded-lg bg-yellow-500 px-4 py-2 font-medium text-gray-900 transition-all hover:bg-yellow-600">
        + Добавить
      </button>
    </div>

    <!-- Drivers Table -->
    <div class="overflow-hidden rounded-xl border border-gray-700 bg-gray-800">
      <table class="w-full">
        <thead class="border-b border-gray-700 bg-gray-900">
          <tr>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-400">Водитель</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-400">Автомобиль</th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-400">Рейтинг</th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-400">Поездок</th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-400">Статус</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-gray-400"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
          <tr
            v-for="driver in drivers"
            :key="driver.id"
            class="transition-colors hover:bg-gray-700/50"
          >
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-700">
                  <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                </div>
                <div>
                  <div class="flex items-center gap-2">
                    <span :class="['h-2 w-2 rounded-full', getStatusDot(driver)]"></span>
                    <span class="font-medium text-white">{{ driver.name }}</span>
                  </div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-400">
              {{ driver.car ? `${driver.car.brand} ${driver.car.model} • ${driver.car.plate_number}` : 'Нет авто' }}
            </td>
            <td class="px-4 py-3 text-center">
              <div class="flex items-center justify-center gap-1">
                <svg class="h-4 w-4 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                <span class="font-medium text-white">-</span>
              </div>
            </td>
            <td class="px-4 py-3 text-center text-sm text-gray-400">-</td>
            <td class="px-4 py-3 text-center">
              <span :class="['rounded px-2 py-0.5 text-xs font-medium uppercase', getStatusBadge(driver).class]">
                {{ getStatusBadge(driver).text }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <button class="rounded p-1 text-gray-400 hover:bg-gray-700 hover:text-white">
                <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </DispatcherLayout>
</template>
