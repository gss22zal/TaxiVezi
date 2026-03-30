<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  drivers: {
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
  }
})

const searchQuery = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || 'all')
const showDropdown = ref(null)

// Статусы водителей (real-time)
const driverStatuses = ref({})
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
      const statuses = {}
      data.drivers?.forEach(d => {
        statuses[d.id] = d
      })
      driverStatuses.value = statuses
    }
  } catch (e) {
    console.warn('Load driver statuses error:', e)
  }
}

const getDriverStatus = (driverId) => {
  return driverStatuses.value[driverId] || { is_online: false, is_busy: false }
}

const getStatusBadge = (driver) => {
  const status = getDriverStatus(driver.id)
  const driverActive = driver.user?.is_active
  
  if (!driverActive) {
    return { text: 'Заблокирован', class: 'bg-red-600' }
  }
  if (!status.is_online) {
    return { text: 'Офлайн', class: 'bg-gray-600' }
  }
  if (status.is_busy) {
    return { text: 'Занят', class: 'bg-yellow-600' }
  }
  return { text: 'Свободен', class: 'bg-green-600' }
}

const getStatusDot = (driverId) => {
  const status = getDriverStatus(driverId)
  if (!status.is_online) return 'bg-gray-500'
  return status.is_busy ? 'bg-yellow-500' : 'bg-green-500'
}

onMounted(() => {
  document.addEventListener('click', closeDropdowns)
  loadDriverStatuses()
  pollTimer = setInterval(loadDriverStatuses, 5000)
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdowns)
  if (pollTimer) clearInterval(pollTimer)
})

// Модальное окно автомобиля
const showCarModal = ref(false)
const editingDriver = ref(null)
const driverCar = ref(null)
const allDriverCars = ref([])
const carForm = ref({
  brand: '',
  model: '',
  year: '',
  color: '',
  plate_number: '',
  region_code: '',
  car_class: 'econom',
  vin_number: '',
  insurance_number: '',
  insurance_expiry: '',
  tech_inspection_expiry: '',
  is_active: true,
  is_primary: true
})

const carClassOptions = [
  { id: 'econom', name: 'Эконом' },
  { id: 'comfort', name: 'Комфорт' },
  { id: 'business', name: 'Бизнес' },
  { id: 'minivan', name: 'Минивэн' },
  { id: 'premium', name: 'Премиум' },
]

// Закрыть dropdown при клике вне
const closeDropdowns = (event) => {
  if (!event.target.closest('.dropdown-container')) {
    showDropdown.value = null
  }
}

// Debounce поиск
let searchTimeout = null
watch(searchQuery, (value) => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(route('admin.users.drivers'), { 
      search: value, 
      status: statusFilter.value 
    }, { preserveState: true })
  }, 300)
})

watch(statusFilter, (value) => {
  router.get(route('admin.users.drivers'), { 
    search: searchQuery.value, 
    status: value 
  }, { preserveState: true })
})

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ru-RU')
}

const toggleBlock = (driver) => {
  router.post(route('admin.users.toggleBlock'), {
    user_id: driver.user.id,
    type: 'driver'
  }, { preserveState: true })
  showDropdown.value = null
}

const deleteUser = (driver) => {
  if (confirm('Вы уверены, что хотите удалить этого водителя? Это действие необратимо.')) {
    router.delete(route('admin.users.destroy'), {
      data: {
        user_id: driver.user.id,
        type: 'driver'
      },
      preserveState: true
    })
  }
  showDropdown.value = null
}

// Работа с автомобилями
const openCarModal = async (driver) => {
  editingDriver.value = driver
  showDropdown.value = null
  
  try {
    const response = await fetch(`/admin/drivers/${driver.id}/car`, {
      headers: { 'Accept': 'application/json' }
    })
    const data = await response.json()
    driverCar.value = data.car
    allDriverCars.value = data.all_cars || []
    
    if (data.car) {
      carForm.value = {
        brand: data.car.brand || '',
        model: data.car.model || '',
        year: data.car.year || '',
        color: data.car.color || '',
        plate_number: data.car.plate_number || '',
        region_code: data.car.region_code || '',
        car_class: data.car.car_class || 'econom',
        vin_number: data.car.vin_number || '',
        insurance_number: data.car.insurance_number || '',
        insurance_expiry: data.car.insurance_expiry || '',
        tech_inspection_expiry: data.car.tech_inspection_expiry || '',
        is_active: data.car.is_active ?? true,
        is_primary: data.car.is_primary ?? true
      }
    } else {
      // Новый автомобиль
      carForm.value = {
        brand: '',
        model: '',
        year: '',
        color: '',
        plate_number: '',
        region_code: '',
        car_class: 'econom',
        vin_number: '',
        insurance_number: '',
        insurance_expiry: '',
        tech_inspection_expiry: '',
        is_active: true,
        is_primary: true
      }
    }
  } catch (e) {
    console.error('Ошибка загрузки автомобиля:', e)
  }
  
  showCarModal.value = true
}

const closeCarModal = () => {
  showCarModal.value = false
  editingDriver.value = null
  driverCar.value = null
}

const saveCar = async () => {
  try {
    const isNew = !driverCar.value
    const url = isNew 
      ? `/admin/drivers/${editingDriver.value.id}/car`
      : `/admin/cars/${driverCar.value.id}`
    const method = isNew ? 'POST' : 'PUT'

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify(carForm.value)
    })

    if (response.ok) {
      // Перезагружаем страницу для обновления данных
      router.reload({ preserveState: true })
      closeCarModal()
    } else {
      const error = await response.json()
      alert('Ошибка: ' + (error.message || 'Не удалось сохранить'))
    }
  } catch (e) {
    alert('Ошибка сохранения')
  }
}

const deleteCar = async () => {
  if (!driverCar.value) return
  if (!confirm('Удалить этот автомобиль?')) return

  try {
    const response = await fetch(`/admin/cars/${driverCar.value.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
      }
    })

    if (response.ok) {
      router.reload({ preserveState: true })
      closeCarModal()
    }
  } catch (e) {
    alert('Ошибка удаления')
  }
}

const setPrimaryCar = async (car) => {
  try {
    const response = await fetch(`/admin/cars/${car.id}/primary`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
      }
    })

    if (response.ok) {
      router.reload({ preserveState: true })
      closeCarModal()
    }
  } catch (e) {
    alert('Ошибка')
  }
}
</script>

<template>
  <AdminLayout activeTab="users">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Водители</h1>
        <p class="text-gray-400">Управление водителями системы</p>
      </div>
      <button class="rounded-lg bg-red-500 px-4 py-2 font-medium text-white transition-all hover:bg-red-600">
        + Добавить водителя
      </button>
    </div>

    <!-- Filters -->
    <div class="mb-4 flex gap-4">
      <div class="flex-1">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Поиск по имени, телефону, авто..."
          class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white placeholder-gray-500 focus:border-red-500 focus:outline-none"
        />
      </div>
      <select
        v-model="statusFilter"
        class="rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white focus:border-red-500 focus:outline-none"
      >
        <option value="all">Все статусы</option>
        <option value="active">Активен</option>
        <option value="pending">На проверке</option>
        <option value="blocked">Заблокирован</option>
      </select>
    </div>

    <!-- Table -->
    <div class="overflow-visible rounded-xl border border-gray-700 bg-gray-800">
      <table class="w-full">
        <thead class="border-b border-gray-700 bg-gray-900">
          <tr>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-400">Водитель</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-400">Авто</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-400">Телефон</th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-400">Рейтинг</th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-400">Поездок</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-gray-400">Заработано</th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-400">Статус</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-gray-400">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700 overflow-visible">
          <tr
            v-for="driver in drivers"
            :key="driver.id"
            class="transition-colors hover:bg-gray-700/50"
          >
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-700">
                  <span class="text-sm font-bold text-gray-400">{{ driver.user?.first_name?.charAt(0) || '?' }}</span>
                </div>
                <div>
                  <div class="font-medium text-white">
                    {{ driver.user?.first_name }} {{ driver.user?.last_name }}
                  </div>
                  <div class="text-xs text-gray-500">С {{ formatDate(driver.user?.created_at) }}</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <div class="text-white">{{ driver.car ? `${driver.car.brand} ${driver.car.model}` : '-' }}</div>
                <button 
                  v-if="driver.car"
                  @click="openCarModal(driver)"
                  class="rounded p-1 text-gray-500 hover:bg-gray-700 hover:text-yellow-500"
                  title="Редактировать автомобиль"
                >
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                  </svg>
                </button>
              </div>
              <div class="text-xs text-gray-500">{{ driver.car?.plate_number || '-' }}</div>
            </td>
            <td class="px-4 py-3 text-gray-400">{{ driver.user?.phone || '-' }}</td>
            <td class="px-4 py-3 text-center">
              <div class="flex items-center justify-center gap-1">
                <svg class="h-4 w-4 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                <span class="font-medium text-white">{{ driver.rating || '-' }}</span>
              </div>
            </td>
            <td class="px-4 py-3 text-center text-gray-400">{{ driver.orders_count || 0 }}</td>
            <td class="px-4 py-3 text-right">
              <span class="font-medium text-yellow-500">{{ (driver.total_earnings || 0).toLocaleString() }} ₽</span>
            </td>
            <td class="px-4 py-3 text-center">
              <!-- Индикатор статуса с точкой -->
              <div class="flex items-center justify-center gap-2">
                <span :class="['h-2 w-2 rounded-full', getStatusDot(driver.id)]"></span>
                <span :class="['rounded px-2 py-0.5 text-xs font-medium uppercase', getStatusBadge(driver).class]">
                  {{ getStatusBadge(driver).text }}
                </span>
              </div>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="relative dropdown-container overflow-visible">
                <button 
                  @click.stop="showDropdown = showDropdown === driver.id ? null : driver.id" 
                  class="rounded p-1 text-gray-400 hover:bg-gray-700 hover:text-white"
                >
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                  </svg>
                </button>
                
                <!-- Dropdown Menu -->
                <div 
                  v-if="showDropdown === driver.id"
                  class="absolute right-0 top-full z-50 mt-1 w-48 rounded-lg border border-gray-600 bg-gray-800 shadow-xl"
                >
                  <button
                    @click="toggleBlock(driver)"
                    class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-white hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    {{ driver.user.is_active ? 'Заблокировать' : 'Разблокировать' }}
                  </button>
                  <button
                    @click="deleteUser(driver)"
                    class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-red-500 hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Удалить
                  </button>
                </div>
              </div>
            </td>
          </tr>
          <tr v-if="drivers.length === 0">
            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
              Водители не найдены
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="mt-4 flex items-center justify-between">
      <div class="text-sm text-gray-400">
        Показано {{ drivers.length }} из {{ pagination.total }}
      </div>
      <div class="flex gap-2">
        <button 
          v-for="page in pagination.last_page" 
          :key="page"
          @click="router.get(route('admin.users.drivers'), { page, search: searchQuery, status: statusFilter }, { preserveState: true })"
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

    <!-- Модальное окно автомобиля -->
    <div v-if="showCarModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="w-full max-w-lg rounded-xl border border-gray-700 bg-gray-800 p-6">
        <h3 class="mb-4 text-lg font-bold text-white">
          Автомобиль водителя: {{ editingDriver?.user?.first_name }} {{ editingDriver?.user?.last_name }}
        </h3>

        <form @submit.prevent="saveCar" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-1 block text-sm text-gray-400">Марка *</label>
              <input
                v-model="carForm.brand"
                type="text"
                required
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
                placeholder="KIA"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm text-gray-400">Модель *</label>
              <input
                v-model="carForm.model"
                type="text"
                required
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
                placeholder="Rio"
              />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="mb-1 block text-sm text-gray-400">Год</label>
              <input
                v-model="carForm.year"
                type="number"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
                placeholder="2023"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm text-gray-400">Цвет</label>
              <input
                v-model="carForm.color"
                type="text"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
                placeholder="Белый"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm text-gray-400">Класс *</label>
              <select
                v-model="carForm.car_class"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
              >
                <option v-for="opt in carClassOptions" :key="opt.id" :value="opt.id">
                  {{ opt.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-1 block text-sm text-gray-400">Госномер *</label>
              <input
                v-model="carForm.plate_number"
                type="text"
                required
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
                placeholder="А123ВГ77"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm text-gray-400">Регион</label>
              <input
                v-model="carForm.region_code"
                type="text"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
                placeholder="77"
              />
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm text-gray-400">VIN</label>
            <input
              v-model="carForm.vin_number"
              type="text"
              class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
              placeholder="XW8ABCD..."
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-1 block text-sm text-gray-400">Страховка</label>
              <input
                v-model="carForm.insurance_number"
                type="text"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
                placeholder="№ полиса"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm text-gray-400">Страховка до</label>
              <input
                v-model="carForm.insurance_expiry"
                type="date"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white"
              />
            </div>
          </div>

          <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
              <input
                v-model="carForm.is_primary"
                type="checkbox"
                id="is_primary"
                class="h-4 w-4 rounded border-gray-600 bg-gray-700 text-yellow-500"
              />
              <label for="is_primary" class="text-sm text-gray-300">Основной</label>
            </div>
            <div class="flex items-center gap-2">
              <input
                v-model="carForm.is_active"
                type="checkbox"
                id="is_active"
                class="h-4 w-4 rounded border-gray-600 bg-gray-700 text-green-500"
              />
              <label for="is_active" class="text-sm text-gray-300">Активен</label>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button
              v-if="driverCar"
              type="button"
              @click="deleteCar"
              class="rounded-lg bg-red-900/50 px-4 py-2 text-sm text-red-400 hover:bg-red-900"
            >
              Удалить
            </button>
            <div class="flex-1"></div>
            <button
              type="button"
              @click="closeCarModal"
              class="rounded-lg border border-gray-600 px-4 py-2 text-gray-300 hover:bg-gray-700"
            >
              Отмена
            </button>
            <button
              type="submit"
              class="rounded-lg bg-red-500 px-4 py-2 font-medium text-white hover:bg-red-600"
            >
              Сохранить
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
