<script setup>
import { ref, onMounted, onUnmounted, computed, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import DispatcherLayout from '@/Layouts/DispatcherLayout.vue'

const props = defineProps({
  mapSettings: {
    type: Object,
    default: () => ({})
  },
  mapDrivers: {
    type: Array,
    default: () => []
  },
  mapOrders: {
    type: Array,
    default: () => []
  }
})

// Состояние - реактивные переменные для данных
const drivers = ref([...props.mapDrivers])
const orders = ref([...props.mapOrders])

// Обновляем данные когда props меняются
import { watch } from 'vue'
watch(() => props.mapDrivers, (newVal) => {
  drivers.value = [...newVal]
}, { deep: true })
watch(() => props.mapOrders, (newVal) => {
  orders.value = [...newVal]
}, { deep: true })

// Состояние
const showFilters = ref(false)
const mapInstance = ref(null)
const placemarks = ref([])
const orderPlacemarks = ref([])
const isLoading = ref(true)
const error = ref('')
const mapContainerReady = ref(false)

// Фильтры
const filters = ref({
  showFreeDrivers: true,
  showBusyDrivers: true,
  showNewOrders: true,
  showAcceptedOrders: true
})

// Вычисляемые координаты центра
const mapCenter = computed(() => {
  const center = props.mapSettings?.default_map_center || '53.990061,84.746699'
  const [lat, lng] = center.split(',').map(Number)
  return [lat || 53.990061, lng || 84.746699]
})

const mapZoom = computed(() => {
  return props.mapSettings?.default_map_zoom || 15
})

const hasYandexApiKey = computed(() => {
  const key = props.mapSettings?.yandex_maps_api_key
  return key && key.length > 0
})

// Загрузка скрипта Яндекс карт
const loadYandexMapsScript = () => {
  return new Promise((resolve, reject) => {
    if (window.ymaps) {
      resolve()
      return
    }
    
    const apiKey = props.mapSettings?.yandex_maps_api_key || ''
    if (!apiKey) {
      reject(new Error('No API key'))
      return
    }
    
    const script = document.createElement('script')
    script.src = `https://api-maps.yandex.ru/2.1/?apikey=${apiKey}&lang=ru_RU`
    script.onload = resolve
    script.onerror = reject
    document.head.appendChild(script)
  })
}

// Инициализация карты
const initMap = async () => {
  // Если нет ключа - показываем сообщение
  if (!hasYandexApiKey.value) {
    isLoading.value = false
    return
  }

  try {
    await loadYandexMapsScript()
    
    // Даём время DOM обновиться
    await new Promise(resolve => setTimeout(resolve, 500))
    
    const container = document.getElementById('yandex-map')
    if (!container) {
      error.value = 'Контейнер карты не найден'
      isLoading.value = false
      return
    }
    
    window.ymaps.ready(() => {
      try {
        mapInstance.value = new window.ymaps.Map('yandex-map', {
          center: mapCenter.value,
          zoom: mapZoom.value,
          controls: ['zoomControl', 'fullscreenControl']
        })

        // Добавляем маркеры водителей
        updateDriverPlacemarks()
        
        // Добавляем маркеры заказов
        updateOrderPlacemarks()

        isLoading.value = false
      } catch (e) {
        console.error('Error creating map:', e)
        error.value = 'Ошибка инициализации карты: ' + e.message
        isLoading.value = false
      }
    })
  } catch (e) {
    console.error('Error loading map:', e)
    error.value = 'Не удалось загрузить карту'
    isLoading.value = false
  }
}

// Обновление маркеров водителей
const updateDriverPlacemarks = () => {
  if (!mapInstance.value) return
  
  // Удаляем старые маркеры
  placemarks.value.forEach(p => {
    try { mapInstance.value.overlay.remove(p) } catch(e) {}
  })
  placemarks.value = []
  
  const driversList = drivers.value.filter(d => {
    if (!d.lat || !d.lng) return false
    if (d.status === 'free' && !filters.value.showFreeDrivers) return false
    if (d.status === 'busy' && !filters.value.showBusyDrivers) return false
    return true
  })
  
  // Простые иконки - круги
  const freeIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><circle cx="10" cy="10" r="9" fill="%2322c55e" stroke="%231F2937" stroke-width="2"/></svg>'
  const busyIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><circle cx="10" cy="10" r="9" fill="%23eab308" stroke="%231F2937" stroke-width="2"/></svg>'
  
  driversList.forEach(driver => {
    const iconData = 'data:image/svg+xml;base64,' + btoa(driver.status === 'free' ? freeIcon : busyIcon)
    
    const placemark = new window.ymaps.Placemark(
      [driver.lat, driver.lng],
      {
        iconContent: '',
        balloonContentHeader: `<strong>${driver.name}</strong>`,
        balloonContentBody: `
          <div>Статус: ${driver.status === 'free' ? 'Свободен' : 'Занят'}</div>
          ${driver.car ? `<div>${driver.car.color} ${driver.car.brand} ${driver.car.model}</div>
          <div>${driver.car.license_plate}</div>` : ''}
        `
      },
      {
        iconLayout: 'default#image',
        iconImageHref: iconData,
        iconImageSize: [20, 20],
        iconImageOffset: [-10, -10]
      }
    )
    
    try {
      mapInstance.value.overlay.add(placemark)
      placemarks.value.push(placemark)
    } catch(e) {}
  })
}

// Обновление маркеров заказов
const updateOrderPlacemarks = () => {
  if (!mapInstance.value) return
  
  // Удаляем старые маркеры
  orderPlacemarks.value.forEach(p => {
    try { mapInstance.value.overlay.remove(p) } catch(e) {}
  })
  orderPlacemarks.value = []
  
  const ordersList = orders.value.filter(o => {
    if (!o.lat || !o.lng) return false
    if (o.status === 'new' && !filters.value.showNewOrders) return false
    if (['accepted', 'arrived', 'in_transit'].includes(o.status) && !filters.value.showAcceptedOrders) return false
    return true
  })
  
  const statusColors = {
    'new': '#22c55e',
    'accepted': '#3b82f6',
    'arrived': '#eab308',
    'in_transit': '#f97316'
  }
  
  const statusLabels = {
    'new': 'Новый',
    'accepted': 'Принят',
    'arrived': 'Прибыл',
    'in_transit': 'В пути'
  }
  
  ordersList.forEach(order => {
    const color = statusColors[order.status] || '#22c55e'
    
    const pinIcon = `<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 20 20"><path d="M10 2C6.13 2 3 5.13 3 9c0 5.25 7 11 7 11s7-5.75 7-11c0-3.87-3.13-7-7-7z" fill="${color}" stroke="%231F2937" stroke-width="1"/><circle cx="10" cy="9" r="3" fill="white"/></svg>`
    const iconData = 'data:image/svg+xml;base64,' + btoa(pinIcon)
    
    const placemark = new window.ymaps.Placemark(
      [order.lat, order.lng],
      {
        iconContent: '',
        balloonContentHeader: `<strong>${order.order_number}</strong>`,
        balloonContentBody: `
          <div>Статус: ${statusLabels[order.status] || order.status}</div>
          <div>Откуда: ${order.pickup_address}</div>
          <div>Куда: ${order.dropoff_address}</div>
          ${order.passenger_name ? `<div>Пассажир: ${order.passenger_name}</div>` : ''}
        `
      },
      {
        iconLayout: 'default#image',
        iconImageHref: iconData,
        iconImageSize: [20, 20],
        iconImageOffset: [-10, -20]
      }
    )
    
    try {
      mapInstance.value.overlay.add(placemark)
      orderPlacemarks.value.push(placemark)
    } catch(e) {}
  })
}

// Переключение фильтров
const toggleFilter = (filter) => {
  filters.value[filter] = !filters.value[filter]
  updateDriverPlacemarks()
  updateOrderPlacemarks()
}

// Polling для обновления данных
let pollingInterval = null

const getCsrfToken = () => {
  const cookieToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1]
  if (cookieToken) return decodeURIComponent(cookieToken)
  return document.querySelector('meta[name="csrf-token"]')?.content || ''
}

const refreshData = async () => {
  try {
    const response = await fetch('/api/dispatcher/map-data', {
      credentials: 'include',
      headers: {
        'X-XSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      }
    })
    if (response.ok) {
      const data = await response.json()
      if (data.drivers) {
        drivers.value = data.drivers
        updateDriverPlacemarks()
      }
      if (data.orders) {
        orders.value = data.orders
        updateOrderPlacemarks()
      }
    }
  } catch (e) {
    console.error('Error refreshing map data:', e)
  }
}

onMounted(() => {
  initMap()
  pollingInterval = setInterval(refreshData, 5000)
})

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval)
  if (mapInstance.value) {
    try { mapInstance.value.destroy() } catch(e) {}
  }
})
</script>

<template>
  <DispatcherLayout activeTab="map">
    <!-- Page Header -->
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-white">Карта</h1>
      <div class="flex gap-2">
        <button
          @click="showFilters = !showFilters"
          :class="[
            'rounded-lg px-3 py-2 text-sm transition',
            showFilters ? 'bg-yellow-500 text-gray-900' : 'bg-gray-700 text-gray-400 hover:bg-gray-600 hover:text-white'
          ]"
        >
          <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
          </svg>
          Фильтры
        </button>
      </div>
    </div>

    <!-- Filters Panel -->
    <div v-if="showFilters" class="mb-4 rounded-lg bg-gray-800 p-4">
      <div class="flex flex-wrap gap-4">
        <label class="flex items-center gap-2 cursor-pointer">
          <input 
            type="checkbox" 
            :checked="filters.showFreeDrivers"
            @change="toggleFilter('showFreeDrivers')"
            class="rounded bg-gray-700 text-green-500 focus:ring-green-500"
          >
          <span class="text-sm text-gray-300">Свободные водители</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input 
            type="checkbox" 
            :checked="filters.showBusyDrivers"
            @change="toggleFilter('showBusyDrivers')"
            class="rounded bg-gray-700 text-yellow-500 focus:ring-yellow-500"
          >
          <span class="text-sm text-gray-300">Занятые водители</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input 
            type="checkbox" 
            :checked="filters.showNewOrders"
            @change="toggleFilter('showNewOrders')"
            class="rounded bg-gray-700 text-green-500 focus:ring-green-500"
          >
          <span class="text-sm text-gray-300">Новые заказы</span>
        </label>
        <label class="flex items-center gap-2 cursor-pointer">
          <input 
            type="checkbox" 
            :checked="filters.showAcceptedOrders"
            @change="toggleFilter('showAcceptedOrders')"
            class="rounded bg-gray-700 text-blue-500 focus:ring-blue-500"
          >
          <span class="text-sm text-gray-300">Принятые заказы</span>
        </label>
      </div>
    </div>

    <!-- Map Container -->
    <div class="relative h-[calc(100vh-180px)] overflow-hidden rounded-xl border border-gray-700 bg-gray-800">
      <!-- Loading -->
      <div v-if="isLoading && hasYandexApiKey" class="absolute inset-0 z-10 flex items-center justify-center bg-gray-800">
        <div class="text-center">
          <div class="mb-2 h-8 w-8 animate-spin rounded-full border-4 border-gray-600 border-t-yellow-500"></div>
          <p class="text-gray-400">Загрузка карты...</p>
        </div>
      </div>

      <!-- No API Key -->
      <div v-if="!hasYandexApiKey" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-800 p-4">
        <svg class="mb-4 h-24 w-24 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
        </svg>
        <h2 class="mb-2 text-xl font-bold text-gray-400">Карта недоступна</h2>
        <p class="max-w-md text-center text-gray-500">
          Для отображения карты необходимо настроить API ключ Яндекс.Карт в разделе "Настройки" → "Карты"
        </p>
        <button
          @click="$inertia.get('/admin/settings')"
          class="mt-4 rounded-lg bg-yellow-500 px-4 py-2 font-medium text-gray-900 hover:bg-yellow-600"
        >
          Перейти к настройкам
        </button>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-800 p-4">
        <div class="mb-4 text-6xl">⚠️</div>
        <h2 class="mb-2 text-xl font-bold text-red-400">Ошибка загрузки карты</h2>
        <p class="text-center text-gray-500">{{ error }}</p>
      </div>

      <!-- Yandex Map -->
      <div v-show="hasYandexApiKey && !error" id="yandex-map" class="h-full w-full"></div>

      <!-- Legend -->
      <div v-if="hasYandexApiKey && !error && !isLoading" class="absolute left-4 top-4 rounded-lg bg-gray-900/90 p-3">
        <div class="space-y-2 text-xs">
          <div class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-green-500"></span>
            <span class="text-gray-400">Свободный водитель</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-yellow-500"></span>
            <span class="text-gray-400">Занятый водитель</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-green-500"></span>
            <span class="text-gray-400">Новый заказ</span>
          </div>
          <div class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-blue-500"></span>
            <span class="text-gray-400">Принятый заказ</span>
          </div>
        </div>
      </div>
    </div>
  </DispatcherLayout>
</template>
