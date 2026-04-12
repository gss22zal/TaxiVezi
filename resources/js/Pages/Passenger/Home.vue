<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { router } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'

const props = defineProps({
  tariffs: {
    type: Array,
    default: () => []
  },
  passenger: {
    type: Object,
    default: null
  },
  mapsSettings: {
    type: Object,
    default: () => ({})
  }
})

// Настройки карт из props
const mapsSettings = computed(() => props.mapsSettings || {})

// Центр карты из настроек (массив [lat, lon] или строка "lat,lon")
const mapCenter = computed(() => {
  // Новый формат: массив [lat, lon]
  if (mapsSettings.value?.map_center && Array.isArray(mapsSettings.value.map_center) && mapsSettings.value.map_center.length >= 2) {
    return mapsSettings.value.map_center
  }
  // Старый формат: строка "lat,lon" (для обратной совместимости)
  const center = mapsSettings.value?.default_map_center || '55.0415,82.9346'
  const [lat, lng] = center.split(',').map(Number)
  return [lat || 55.0415, lng || 82.9346]
})

// Масштаб карты из настроек
const mapZoom = computed(() => {
  return mapsSettings.value?.map_zoom || 12
})

const form = ref({
  from: '',
  to: '',
  tariff_id: '',
  distance: 12,
  duration: 20,
  notes: ''
})

// Состояние карты
const showMap = ref(false)
const selectingPoint = ref('from') // 'from' или 'to' - какую точку выбираем сейчас
const fromCoords = ref(null) // [lat, lon] от Яндекса
const toCoords = ref(null)
const mapInstance = ref(null)
let ymaps = null

const isSubmitting = ref(false)
const calculatedPrice = ref(null)

// Состояние заказа
const activeOrder = ref(null)
const hasActiveOrder = ref(false)
const freeDriversCount = ref(0)
const isLoadingOrder = ref(false)
const lastCancelledOrder = ref(null)
const showArrivedNotification = ref(false)
let pollingInterval = null
const previousStatus = ref(null)

// Состояние отзыва
const showReviewModal = ref(false)
const reviewRating = ref(5)
const reviewComment = ref('')
const reviewTags = ref([])
const isSubmittingReview = ref(false)
const lastCompletedOrderId = ref(null) //  Исправлено: теперь это ref
const hasReviewForLastOrder = ref(false) // Проверка: есть ли отзыв для последнего завершённого заказа

// История заказов
const orderHistory = ref([])
const isLoadingHistory = ref(false)
const pagination = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1
})

//  Проверка, оставлен ли отзыв на заказ
const checkReviewExists = async (orderId) => {
 try {
 const response = await fetch(`/api/passenger/orders/${orderId}/review/check`, {
   credentials: 'include',
   headers: {
   'X-XSRF-TOKEN': getCsrfToken(),
   'Accept': 'application/json'
   }
 })
    
 const data = await response.json()
 return data.has_review === true
 } catch (error) {
 console.error('Error checking review:', error)
 return false
 }
}

//  Загрузка истории заказов
const loadOrderHistory = async (page = 1) => {
  isLoadingHistory.value = true
  try {
    const response = await fetch(`/api/passenger/orders/history?page=${page}`, {
      credentials: 'include',
      headers: {
        'X-XSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      }
    })
    
    if (response.ok) {
      const data = await response.json()
      orderHistory.value = data.orders || []
      pagination.value = data.pagination || {
        current_page: 1,
        per_page: 10,
        total: 0,
        last_page: 1
      }
    }
  } catch (error) {
    console.error('Error loading order history:', error)
  } finally {
    isLoadingHistory.value = false
  }
}

//  Переключение страницы
const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadOrderHistory(page)
  }
}

//  Скрыть заказ из истории
const hideFromHistory = async (orderId) => {
  if (!confirm('Удалить этот заказ из истории?')) return
  
  try {
    const response = await fetch(`/api/passenger/orders/${orderId}/hide`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-XSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      }
    })
    
    const data = await response.json()

    if (response.ok && data.success) {
      // Удаляем заказ из локального списка
      orderHistory.value = orderHistory.value.filter(o => o.id !== orderId)
      // Обновляем пагинацию
      pagination.value.total--
      pagination.value.last_page = Math.ceil(pagination.value.total / pagination.value.per_page)
    } else {
      alert(data.message || 'Ошибка при удалении')
    }
  } catch (error) {
    console.error('Error hiding order:', error)
    alert('Не удалось удалить заказ')
  }
}

//  Повторить заказ из истории
const repeatFromHistory = (order) => {
  form.value.from = order.pickup_address
  form.value.to = order.dropoff_address
  form.value.distance = order.distance || 12
  form.value.notes = order.notes || ''
  form.value.tariff_id = order.tariff_id || ''
  
  updateDistance()
  
  // Прокрутка к форме
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

//  Форматирование даты
const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('ru-RU', {
    day: 'numeric',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit'
  })
}

// Доступные теги для отзыва
const availableTags = [
  'Вежливый водитель',
  'Чистый салон',
  'Аккуратная езда',
  'Быстро',
  'Опытный водитель',
  'Хорошая машина',
  'Не спешил',
  'Всё понравилось'
]

// Статусы заказа
const orderStatusText = {
  'new': 'Ваш заказ в обработке',
  'accepted': 'Водитель принял заказ',
  'arrived': 'Водитель прибыл',
  'in_transit': 'Вы в пути',
  'started': 'Поездка началась',
  'completed': 'Поездка завершена',
  'cancelled': 'Заказ отменён'
}

const orderStatusColor = {
  'new': 'green',
  'accepted': 'blue',
  'arrived': 'yellow',
  'in_transit': 'orange',
  'started': 'orange',
  'completed': 'gray',
  'cancelled': 'red'
}

//  AudioContext создаётся один раз при монтировании
let audioContext = null

// Загрузка Яндекс Карт
const loadYandexMaps = () => {
  return new Promise((resolve, reject) => {
    if (window.ymaps) {
      ymaps = window.ymaps
      resolve()
      return
    }
    
    const apiKey = mapsSettings.value?.yandex_maps_api_key || ''
    if (!apiKey) {
      reject(new Error('No API key'))
      return
    }
    
    const script = document.createElement('script')
    script.src = `https://api-maps.yandex.ru/2.1/?apikey=${apiKey}&lang=ru_RU`
    script.onload = () => {
      ymaps = window.ymaps
      resolve()
    }
    script.onerror = reject
    document.head.appendChild(script)
  })
}

// Инициализация поиска адресов
const initAddressSearch = async () => {
  const apiKey = mapsSettings.value?.yandex_maps_api_key
  
  if (!apiKey) {
    console.log('No Yandex Maps API key')
    return
  }
  
  try {
    await loadYandexMaps()
    console.log('Yandex Maps loaded')
    
    // Кастомный поиск адресов через input
    setupCustomAddressSearch('from-input', (address) => {
      form.value.from = address
      calculateRouteDistance()
    })
    
    setupCustomAddressSearch('to-input', (address) => {
      form.value.to = address
      calculateRouteDistance()
    })
  } catch (e) {
    console.warn('Yandex Maps not loaded:', e)
  }
}

// Кастомный поиск адресов
const setupCustomAddressSearch = (inputId, onSelect) => {
  const input = document.getElementById(inputId)
  if (!input) return
  
  let suggestPanel = null
  let currentRequest = null
  
  const showSuggestions = (results) => {
    // Удаляем старую панель
    if (suggestPanel) {
      suggestPanel.remove()
    }
    
    if (results.length === 0) return
    
    // Создаём панель подсказок
    suggestPanel = document.createElement('div')
    suggestPanel.className = 'suggest-panel'
    suggestPanel.style.cssText = `
      position: absolute;
      z-index: 9999;
      background: #1F2937;
      border: 1px solid #374151;
      border-radius: 8px;
      max-height: 250px;
      overflow-y: auto;
      margin-top: 4px;
      width: 100%;
      left: 0;
      right: 0;
    `
    
    results.forEach(item => {
      const div = document.createElement('div')
      div.className = 'suggest-item'
      div.style.cssText = `
        padding: 10px 12px;
        cursor: pointer;
        color: white;
        border-bottom: 1px solid #374151;
      `
      div.textContent = item.displayName
      div.addEventListener('click', () => {
        onSelect(item.displayName)
        if (suggestPanel) {
          suggestPanel.remove()
          suggestPanel = null
        }
      })
      div.addEventListener('mouseenter', () => {
        div.style.backgroundColor = '#374151'
      })
      div.addEventListener('mouseleave', () => {
        div.style.backgroundColor = 'transparent'
      })
      suggestPanel.appendChild(div)
    })
    
    // Добавляем после инпута
    const parent = input.parentElement
    parent.style.position = 'relative'
    parent.appendChild(suggestPanel)
  }
  
  const fetchSuggestions = async (query) => {
    if (query.length < 3) {
      if (suggestPanel) {
        suggestPanel.remove()
        suggestPanel = null
      }
      return
    }
    
    const apiKey = mapsSettings.value?.yandex_maps_api_key
    if (!apiKey) return
    
    // Отменяем предыдущий запрос
    if (currentRequest) {
      currentRequest.abort()
    }
    
    currentRequest = new AbortController()
    
    try {
      const url = `https://geocode-maps.yandex.ru/1.x/?apikey=${apiKey}&geocode=${encodeURIComponent(query)}&format=json&results=5&kind=house,street,metro`
      
      const response = await fetch(url, { signal: currentRequest.signal })
      const data = await response.json()
      
      if (data.response?.GeoObjectCollection?.featureMember) {
        const results = data.response.GeoObjectCollection.featureMember.map(item => ({
          displayName: item.GeoObject.metaDataProperty.GeocoderMetaData.text,
          address: item.GeoObject.metaDataProperty.GeocoderMetaData.address.formatted,
          pos: item.GeoObject.Point.pos
        }))
        showSuggestions(results)
      }
    } catch (e) {
      if (e.name !== 'AbortError') {
        console.warn('Geocoder error:', e)
      }
    }
  }
  
  let debounceTimer = null
  
  input.addEventListener('input', (e) => {
    const query = e.target.value.trim()
    
    // Очищаем таймер
    if (debounceTimer) {
      clearTimeout(debounceTimer)
    }
    
    // Задержка перед запросом (debounce)
    debounceTimer = setTimeout(() => {
      fetchSuggestions(query)
    }, 300)
  })
  
  // Закрываем панель при клике вне
  document.addEventListener('click', (e) => {
    if (suggestPanel && !input.contains(e.target) && !suggestPanel.contains(e.target)) {
      suggestPanel.remove()
      suggestPanel = null
    }
  })
}

// Открыть карту для выбора маршрута
const openMapSelector = () => {
  selectingPoint.value = 'from' // Начинаем с точки А
  showMap.value = true
  
  nextTick(() => {
    setTimeout(() => {
      initMap()
    }, 100)
  })
}

// Инициализация карты
const initMap = async () => {
  const mapContainer = document.getElementById('map-container')
  if (!mapContainer) return
  
  if (mapInstance.value) return
  
  try {
    await loadYandexMaps()
    
    ymaps.ready(() => {
      mapInstance.value = new ymaps.Map('map-container', {
        center: mapCenter.value,
        zoom: mapZoom.value,
        controls: ['zoomControl', 'geolocationControl']
      })
      
      // Клик по карте
      mapInstance.value.events.add('click', async (e) => {
        if (selectingPoint.value) {
          const coords = e.get('coords')
          await setPoint(selectingPoint.value, coords)
        }
      })
    })
  } catch (e) {
    console.error('Map init error:', e)
  }
}

// Установить точку
const setPoint = async (type, coords) => {
  if (!mapInstance.value || !ymaps) return
  
  if (!Array.isArray(coords) || coords.length < 2) {
    console.warn('Invalid coords:', coords)
    return
  }
  
  console.log(`Setting point ${type}:`, coords)
  
  if (type === 'from') {
    fromCoords.value = coords
  } else {
    toCoords.value = coords
  }
  
  // Переключаемся на следующую точку
  if (type === 'from') {
    selectingPoint.value = 'to'
  } else {
    selectingPoint.value = 'from' // После точки Б можно выбрать точку А снова
  }
}

// Построить маршрут
const buildRoute = async () => {
  if (!fromCoords.value || !toCoords.value || !ymaps || !mapInstance.value) {
    console.warn('Cannot build route: missing data')
    alert('Выберите обе точки на карте')
    return
  }
  
  console.log('Building route...', fromCoords.value, toCoords.value)
  
  try {
    // Получаем адреса для координат
    const fromAddress = await getAddressFromCoords(fromCoords.value)
    const toAddress = await getAddressFromCoords(toCoords.value)
    
    if (!fromAddress || !toAddress) {
      alert('Не удалось определить адреса точек')
      return
    }
    
    console.log('Route addresses:', fromAddress, toAddress)
    
    // Создаём маршрут через адреса
    const route = await ymaps.route([fromAddress, toAddress])
    
    // Получаем расстояние и время из свойств маршрута
    const distance = route.getLength()
    const timeParts = route.getHumanTime() // Возвращает строку типа "15 мин"
    
    // Парсим время из строки (например, "15 мин" → 15)
    let duration = 0
    if (timeParts) {
      const timeStr = typeof timeParts === 'string' ? timeParts : ''
      const match = timeStr.match(/(\d+)/)
      if (match) {
        duration = parseInt(match[1], 10)
      }
    }
    
    // Если не удалось получить время - рассчитываем примерно (2.5 мин на км)
    if (!duration) {
      duration = Math.round(distance / 1000 * 2.5)
    }
    
    console.log('Route built:', distance, duration)
    
    form.value.distance = Math.round(distance / 1000)
    form.value.duration = duration
    calculatePrice()
    
  } catch (e) {
    console.error('Route build error:', e)
    alert('Не удалось построить маршрут. Попробуйте другие точки.')
  }
}

// Сократить адрес (убрать область, район, тип населённого пункта)
const shortenAddress = (address) => {
  if (!address) return ''
  
  // Разбиваем адрес на части
  const parts = address.split(',').map(p => p.trim())
  
  // Фильтруем части - убираем области, края, районы
  const filtered = parts.filter(part => {
    const lower = part.toLowerCase()
    // Пропускаем если содержит эти слова
    if (lower.includes('область')) return false
    if (lower.includes('край')) return false
    if (lower.includes('район')) return false
    if (lower.includes('респ')) return false
    if (lower.includes('автономный')) return false
    if (lower.includes('округ')) return false
    return true
  })
  
  // Убираем префиксы типа "село", "город", "деревня" и т.д.
  const cleaned = filtered.map(part => {
    return part.replace(/^(село|город|деревня|поселок|пгт|станица)\.?\s*/gi, '').trim()
  })
  
  // Сокращаем типы улиц
  const streetTypes = [
    ['улица', 'ул.'],
    ['проспект', 'пр-кт'],
    ['бульвар', 'б-р'],
    ['переулок', 'пер.'],
    ['тупик', 'туп.'],
    ['шоссе', 'шос.'],
    ['площадь', 'пл.'],
    ['набережная', 'наб.'],
    ['проезд', 'пр-д'],
    ['микрорайон', 'м-н']
  ]
  
  const finalParts = cleaned.map(part => {
    for (const [full, short] of streetTypes) {
      const regex = new RegExp(`^${full}`, 'gi')
      if (regex.test(part)) {
        return part.replace(regex, short)
      }
    }
    return part
  })
  
  return finalParts.join(', ')
}

// Получить адрес из координат (с проверкой точности)
const getAddressFromCoords = async (coords) => {
  try {
    const result = await ymaps.geocode(coords)
    const first = result.geoObjects.get(0)
    
    if (!first) {
      return null
    }
    
    // Получаем адрес и его детали
    const metaData = first.metaDataProperty?.GeocoderMetaData
    const addressDetails = metaData?.AddressDetails
    
    // Проверяем точность адреса
    const kind = metaData?.kind // 'house', 'street', 'locality', и т.д.
    
    // Если найден только город/район/область - возвращаем null (будут координаты)
    if (kind === 'locality' || kind === 'province' || kind === 'country') {
      return null
    }
    
    // Возвращаем полный адрес для последующего сокращения
    return first.getAddressLine()
  } catch (e) {
    console.warn('Geocode error:', e)
    return null
  }
}

// Закрыть карту
const closeMapSelector = () => {
  showMap.value = false
  mapInstance.value = null
}

// Подтвердить выбор
const confirmMapSelection = () => {
  if (!fromCoords.value || !toCoords.value) {
    alert('Выберите обе точки на карте')
    return
  }
  
  // Геокодируем для получения адресов
  Promise.all([
    getAddressFromCoords(fromCoords.value),
    getAddressFromCoords(toCoords.value)
  ]).then(([fromAddress, toAddress]) => {
    console.log('Addresses from geocode:', fromAddress, toAddress)
    
    // Если адрес не определён или недостаточно точный - показываем координаты
    const fromLat = fromCoords.value[1].toFixed(4)
    const fromLon = fromCoords.value[0].toFixed(4)
    const toLat = toCoords.value[1].toFixed(4)
    const toLon = toCoords.value[0].toFixed(4)
    
    // Сокращаем адреса если они есть
    const shortFromAddress = fromAddress ? shortenAddress(fromAddress) : null
    const shortToAddress = toAddress ? shortenAddress(toAddress) : null
    
    console.log('Shortened addresses:', shortFromAddress, shortToAddress)
    
    form.value.from = shortFromAddress || `Координаты: ${fromLat}, ${fromLon}`
    form.value.to = shortToAddress || `Координаты: ${toLat}, ${toLon}`
    
    closeMapSelector()
  })
}

// Вычисление расстояния между адресами
const calculateRouteDistance = async () => {
  if (!form.value.from || !form.value.to || !ymaps) {
    return
  }
  
  // Если адреса слишком короткие, пропускаем
  if (form.value.from.length < 5 || form.value.to.length < 5) {
    return
  }
  
  try {
    const route = await ymaps.route([form.value.from, form.value.to])
    const distanceMeters = route.getLength()
    form.value.distance = Math.round(distanceMeters / 1000)
    form.value.duration = Math.round(route.getHumanLength().duration / 60) || Math.round(form.value.distance * 2.5)
    calculatePrice()
  } catch (e) {
    // Маршрут не найден - используем значение по умолчанию
    console.log('Route not found, using default distance')
    updateDistance()
  }
}

const isFormValid = computed(() => {
  return form.value.from.trim() !== '' &&
         form.value.to.trim() !== '' &&
         form.value.tariff_id !== ''
})

const selectTariff = (tariffId) => {
  form.value.tariff_id = tariffId
  calculatePrice()
}

const calculatePrice = () => {
  if (!form.value.tariff_id) {
    calculatedPrice.value = null
    return
  }
  
  const tariff = props.tariffs.find(t => t.id === parseInt(form.value.tariff_id))
  if (!tariff) return

  const distance = form.value.distance || 12
  const duration = form.value.duration || 20

  const basePrice = parseFloat(tariff.base_price) || 0
  const pricePerKm = parseFloat(tariff.price_per_km) || 0
  const pricePerMin = parseFloat(tariff.price_per_min || tariff.price_per_minute) || 0

  let finalPrice = basePrice + (distance * pricePerKm) + (duration * pricePerMin)

  if (tariff.min_price && finalPrice < parseFloat(tariff.min_price)) {
    finalPrice = parseFloat(tariff.min_price)
  }

  calculatedPrice.value = Math.round(finalPrice)
}

const updateDistance = () => {
  form.value.duration = Math.round(form.value.distance * 2.5)
  calculatePrice()
}

const submitForm = async () => {
  if (!isFormValid.value || isSubmitting.value) return

  isSubmitting.value = true

  router.post(route('orders.store'), {
    passenger_id: props.passenger?.id,
    tariff_id: form.value.tariff_id,
    pickup_address: form.value.from,
    dropoff_address: form.value.to,
    distance: form.value.distance,
    duration: form.value.duration,
    notes: form.value.notes || null,
    passenger_name: props.passenger?.user?.first_name + ' ' + props.passenger?.user?.last_name,
    passenger_phone: props.passenger?.user?.phone,
  }, {
    onSuccess: () => {
      isSubmitting.value = false
      form.value = {
        from: '',
        to: '',
        tariff_id: '',
        distance: 12,
        duration: 20,
        notes: ''
      }
      calculatedPrice.value = null
      startPolling()
    },
    onError: (errors) => {
      alert('Ошибка: ' + Object.values(errors).join(', '))
      isSubmitting.value = false
    }
  })
}

//  Получение CSRF-токена (сначала из cookie, потом из meta)
const getCsrfToken = () => {
  // Сначала пробуем получить из cookie (более надёжно)
  const cookieToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1]
  
  if (cookieToken) {
    return decodeURIComponent(cookieToken)
  }
  
  // Если нет в cookie - берём из meta
  return document.querySelector('meta[name="csrf-token"]')?.content || ''
}

//  Отправка отзыва с правильным заголовком
const submitReview = async () => {
  if (!lastCompletedOrderId.value) {
    alert('ID заказа не найден')
    return
  }
  
  isSubmittingReview.value = true
  
  try {
    const response = await fetch('/api/passenger/orders/' + lastCompletedOrderId.value + '/review', {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-XSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        order_id: lastCompletedOrderId.value,
        rating: reviewRating.value,
        comment: reviewComment.value,
        tags: reviewTags.value
      })
    })
    
    const data = await response.json()
    
    if (response.status === 419) {
      alert('Сессия истекла. Перезагрузите страницу.')
      window.location.reload()
      return
    }
    
    if (data.success) {
      closeReviewModal()
      hasActiveOrder.value = false
      activeOrder.value = null
      alert('Спасибо за отзыв! ' + (data.review?.passenger_rating ? `Вы поставили ${data.review.passenger_rating} звёзд` : ''))
    } else {
      alert(data.message || 'Ошибка при отправке отзыва')
    }
  } catch (error) {
    console.error('Review error:', error)
    alert('Ошибка отправки отзыва')
  } finally {
    isSubmittingReview.value = false
  }
}

//  Исправленная функция воспроизведения звука
const playArrivedSound = () => {
  // Если AudioContext не создан — создаём
  if (!audioContext) {
    const AudioContext = window.AudioContext || window.webkitAudioContext
    if (!AudioContext) {
      console.warn('Web Audio API not supported')
      return
    }
    audioContext = new AudioContext()
  }
  
  // Если контекст приостановлен (требует interaction) — пробуем возобновить
  if (audioContext.state === 'suspended') {
    audioContext.resume().catch(e => console.warn('Could not resume audio:', e))
  }

  // Мелодия "Ваш водитель прибыл"
  const melody = [523, 659, 784, 1047, 784, 659, 523] // C5, E5, G5, C6, G5, E5, C5
  const duration = 0.2

  melody.forEach((freq, index) => {
    setTimeout(() => {
      try {
        // Проверяем состояние контекста перед воспроизведением
        if (audioContext.state !== 'running') return
        
        const oscillator = audioContext.createOscillator()
        const gainNode = audioContext.createGain()

        oscillator.connect(gainNode)
        gainNode.connect(audioContext.destination)

        oscillator.frequency.value = freq
        oscillator.type = 'sine'

        gainNode.gain.setValueAtTime(0.5, audioContext.currentTime)
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration)

        oscillator.start(audioContext.currentTime)
        oscillator.stop(audioContext.currentTime + duration)
      } catch (e) {
        console.error('Error playing note:', e)
      }
    }, index * 200)
  })
}

const fetchOrderStatus = async () => {
  if (isLoadingOrder.value) return
  
  isLoadingOrder.value = true
  
  try {
    const response = await fetch('/api/passenger/active-order', {
      credentials: 'include',
      headers: {
        'X-XSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json'
      }
    })
    
    if (!response.ok) {
      // Обработка 419 (CSRF) и 404
      if (response.status === 419) {
        console.warn('CSRF token expired, reloading...')
        window.location.reload()
        return
      }
      if (response.status === 404) {
        // Нет активного заказа — это нормально
        hasActiveOrder.value = false
        activeOrder.value = null
        previousStatus.value = null
        stopPolling()
        return
      }
      console.error('Error response:', response.status, response.statusText)
      isLoadingOrder.value = false
      return
    }
    
    const data = await response.json()

    if (data.has_active_order) {
      //  Проверяем изменение статуса на "arrived"
      if (previousStatus.value && previousStatus.value !== 'arrived' && data.order.status === 'arrived') {
        playArrivedSound()
        showArrivedNotification.value = true
        setTimeout(() => {
          showArrivedNotification.value = false
        }, 10000)
      }

      previousStatus.value = data.order.status

      hasActiveOrder.value = true
      activeOrder.value = data.order
      freeDriversCount.value = data.free_drivers_count
      
      if (['completed', 'cancelled'].includes(data.order.status)) {
        stopPolling()
        showArrivedNotification.value = false

        // Если заказ завершён — проверяем отзыв
        if (data.order.status === 'completed') {
          lastCompletedOrderId.value = data.order.id
          
          // Проверяем, оставлен ли уже отзыв
          const reviewExists = await checkReviewExists(data.order.id)
          hasReviewForLastOrder.value = reviewExists
          
          if (!reviewExists) {
            // Отзыва нет — показываем заказ завершённым + окно отзыва
            hasActiveOrder.value = true
            setTimeout(() => {
              showReviewModal.value = true
            }, 500)
          } else {
            // Отзыв уже есть — скрываем заказ, показываем форму создания нового
            hasActiveOrder.value = false
            activeOrder.value = null
          }
        } else {
          // Заказ отменён — показываем блок заказа
          hasActiveOrder.value = true
        }
      }
    } else {
      hasActiveOrder.value = false
      activeOrder.value = null
      previousStatus.value = null
      stopPolling()
      showArrivedNotification.value = false
    }
  } catch (error) {
    console.error('Ошибка проверки статуса:', error)
  } finally {
    isLoadingOrder.value = false
  }
}

const startPolling = () => {
  fetchOrderStatus()
  pollingInterval = setInterval(fetchOrderStatus, 3000)
}

const stopPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
}

const cancelOrder = async () => {
  if (!activeOrder.value) return
  
  if (!confirm('Вы уверены, что хотите отменить заказ?')) return
  
  const reason = prompt('Причина отмены заказа (необязательно):')
  if (reason === null) return
  
  lastCancelledOrder.value = {
    from: activeOrder.value.pickup_address,
    to: activeOrder.value.dropoff_address,
    distance: activeOrder.value.distance,
    tariff_id: activeOrder.value.tariff_id,
    notes: activeOrder.value.notes || ''
  }
  
  try {
    const response = await fetch(`/api/passenger/orders/${activeOrder.value.id}/cancel`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'X-XSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ reason: reason || '' })
    })
    
    const data = await response.json()
    
    if (response.status === 419) {
      alert('Сессия истекла. Перезагрузите страницу.')
      window.location.reload()
      return
    }
    
    if (data.success) {
      hasActiveOrder.value = false
      activeOrder.value = null
      stopPolling()
      alert('Заказ отменён')
    } else {
      alert(data.error || 'Ошибка отмены заказа')
    }
  } catch (error) {
    console.error('Ошибка отмены:', error)
    alert('Не удалось отменить заказ')
  }
}

const repeatOrder = () => {
  if (!lastCancelledOrder.value) return
  
  form.value.from = lastCancelledOrder.value.from
  form.value.to = lastCancelledOrder.value.to
  form.value.distance = lastCancelledOrder.value.distance || 12
  form.value.notes = lastCancelledOrder.value.notes || ''
  form.value.tariff_id = lastCancelledOrder.value.tariff_id || ''
  
  updateDistance()
  
  hasActiveOrder.value = false
  activeOrder.value = null
}

const toggleTag = (tag) => {
  const index = reviewTags.value.indexOf(tag)
  if (index > -1) {
    reviewTags.value.splice(index, 1)
  } else {
    reviewTags.value.push(tag)
  }
}

// Закрытие модального окна отзыва
const closeReviewModal = () => {
  showReviewModal.value = false
  reviewRating.value = 5
  reviewComment.value = ''
  reviewTags.value = []
}

// Инициализация при монтировании
onMounted(() => {
  // Создаём AudioContext при первом взаимодействии пользователя
  const initAudio = () => {
    const AudioContext = window.AudioContext || window.webkitAudioContext
    if (AudioContext && !audioContext) {
      audioContext = new AudioContext()
    }
    // Убираем обработчики после первого клика
    document.removeEventListener('click', initAudio)
    document.removeEventListener('touchstart', initAudio)
  }
  
  document.addEventListener('click', initAudio, { once: true })
  document.addEventListener('touchstart', initAudio, { once: true })
  
  fetchOrderStatus()
  startPolling()
  loadOrderHistory()
  
  // Инициализация поиска адресов
  initAddressSearch()
})

onUnmounted(() => {
  stopPolling()
  //  Очищаем AudioContext при размонтировании
  if (audioContext && audioContext.state !== 'closed') {
    audioContext.close().catch(() => {})
  }
})
</script>

<template>
  <MainLayout activeRole="passenger">
    <!-- Уведомление о прибытии водителя -->
    <div v-if="showArrivedNotification" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
      <div class="w-full max-w-sm animate-bounce rounded-2xl bg-green-600 p-8 text-center shadow-2xl">
        <div class="mb-4 flex justify-center">
          <div class="flex h-24 w-24 items-center justify-center rounded-full bg-white">
            <span class="text-5xl">🚕</span>
          </div>
        </div>
        <h2 class="mb-2 text-2xl font-bold text-white">Водитель прибыл!</h2>
        <p class="mb-4 text-green-100">Подойдите к машине или ожидайте водителя</p>
        <button
          @click="showArrivedNotification = false"
          class="w-full rounded-lg bg-white py-3 font-bold text-green-600 transition-all hover:bg-gray-100"
        >
          ОК
        </button>
      </div>
    </div>

    <!-- Модальное окно отзыва -->
    <div v-if="showReviewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
      <div class="w-full max-w-md rounded-2xl bg-gray-800 p-6 shadow-2xl">
        <div class="mb-4 text-center">
          <div class="mb-2 text-4xl">⭐</div>
          <h2 class="text-xl font-bold text-white">Оцените поездку</h2>
          <p class="text-sm text-gray-400">Поездка завершена. Пожалуйста, оцените водителя</p>
        </div>

        <!-- Выбор оценки -->
        <div class="mb-4 flex justify-center gap-2">
          <button
            v-for="star in 5"
            :key="star"
            @click="reviewRating = star"
            class="text-3xl transition-transform hover:scale-110"
          >
            <span :class="star <= reviewRating ? 'text-yellow-400' : 'text-gray-600'">★</span>
          </button>
        </div>

        <div class="mb-2 text-center text-sm text-gray-400">
          {{ reviewRating }} из 5 звёзд
        </div>

        <!-- Теги -->
        <div class="mb-4">
          <p class="mb-2 text-sm text-gray-400">Что вам понравилось?</p>
          <div class="flex flex-wrap gap-2">
            <button
              v-for="tag in availableTags"
              :key="tag"
              @click="toggleTag(tag)"
              :class="[
                'rounded-full px-3 py-1 text-xs transition-colors',
                reviewTags.includes(tag)
                  ? 'bg-yellow-500 text-gray-900'
                  : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
              ]"
            >
              {{ tag }}
            </button>
          </div>
        </div>

        <!-- Комментарий -->
        <div class="mb-4">
          <textarea
            v-model="reviewComment"
            placeholder="Комментарий (необязательно)"
            rows="3"
            class="w-full rounded-lg border-0 bg-gray-700 p-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-yellow-500"
          ></textarea>
        </div>

        <!-- Кнопки -->
        <div class="flex gap-3">
          <button
            @click="closeReviewModal"
            class="flex-1 rounded-lg bg-gray-700 py-3 font-semibold text-white transition-colors hover:bg-gray-600"
          >
            Пропустить
          </button>
          <button
            @click="submitReview"
            :disabled="isSubmittingReview"
            class="flex-1 rounded-lg bg-yellow-500 py-3 font-semibold text-gray-900 transition-colors hover:bg-yellow-600 disabled:cursor-not-allowed disabled:opacity-50"
          >
            {{ isSubmittingReview ? 'Отправка...' : 'Отправить отзыв' }}
          </button>
        </div>
      </div>
    </div>

    <div class="mx-auto max-w-md space-y-4">
      <!-- Блок активного заказа -->
      <div v-if="hasActiveOrder && activeOrder" class="mb-6">
        <div class="overflow-hidden rounded-xl bg-gradient-to-br from-gray-800 to-gray-900 shadow-lg">
          <!-- Заголовок статуса -->
          <div :class="[
            'px-4 py-3 text-white',
            orderStatusColor[activeOrder.status] === 'green' ? 'bg-green-600' : '',
            orderStatusColor[activeOrder.status] === 'blue' ? 'bg-blue-600' : '',
            orderStatusColor[activeOrder.status] === 'yellow' ? 'bg-yellow-600' : '',
            orderStatusColor[activeOrder.status] === 'orange' ? 'bg-orange-500' : '',
            orderStatusColor[activeOrder.status] === 'red' ? 'bg-red-600' : '',
            orderStatusColor[activeOrder.status] === 'gray' ? 'bg-gray-600' : '',
          ]">
            <div class="flex items-center justify-between">
              <div>
                <div class="text-sm opacity-90">Заказ</div>
                <div class="text-lg font-bold">{{ activeOrder.order_number }}</div>
              </div>
              <div class="text-right">
                <div class="text-2xl font-bold">{{ activeOrder.final_price }} ₽</div>
                <div class="text-sm opacity-90">{{ activeOrder.distance }} км</div>
              </div>
            </div>
          </div>
          
          <!-- Статус заказа -->
          <div class="p-4">
            <div class="mb-4 text-center">
              <div class="mb-2 text-2xl">🚕</div>
              <div class="text-lg font-semibold text-white">
                {{ orderStatusText[activeOrder.status] }}
              </div>
              
              <div v-if="activeOrder.status === 'new' && freeDriversCount === 0" class="mt-2 rounded-lg bg-red-500/20 p-2 text-sm text-red-400">
                ⚠️ Свободных машин нет. Пожалуйста, подождите...
              </div>
              
              <div v-if="['accepted', 'arrived', 'in_transit'].includes(activeOrder.status) && activeOrder.driver" class="mt-2 rounded-lg p-3 text-sm min-h-[120px]" :class="activeOrder.status === 'in_transit' ? 'bg-orange-500/30' : (activeOrder.status === 'arrived' ? 'bg-yellow-500/30' : 'bg-blue-500/20')">
                <div class="font-semibold mb-2" :class="activeOrder.status === 'in_transit' ? 'text-orange-400' : (activeOrder.status === 'arrived' ? 'text-yellow-400' : 'text-blue-400')">
                  {{ activeOrder.status === 'in_transit' ? '🚗 Вы в пути! Приятной поездки' : (activeOrder.status === 'arrived' ? '🚕 Водитель прибыл! Ждёт вас' : '🚗 Скоро такси подъедет!') }}
                </div>
                <div class="text-white space-y-1">
                  <div class="font-bold">{{ activeOrder.driver.name }}</div>
                  <div v-if="activeOrder.driver.car" class="mt-2 flex items-center justify-between rounded-lg bg-gray-800/50 p-2">
                    <div>
                      <div class="text-xs text-gray-400">Машина</div>
                      <div class="font-semibold">
                        {{ activeOrder.driver.car.color }} {{ activeOrder.driver.car.brand }} {{ activeOrder.driver.car.model }}
                      </div>
                    </div>
                    <div v-if="activeOrder.driver.car.license_plate" class="text-right">
                      <div class="text-xs text-gray-400">Номер</div>
                      <div class="text-lg font-bold text-yellow-400">
                        {{ activeOrder.driver.car.license_plate }}
                      </div>
                    </div>
                  </div>
                  <div v-if="activeOrder.driver.phone" class="text-gray-400">
                    📞 {{ activeOrder.driver.phone }}
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Детали маршрута -->
            <div class="space-y-2 rounded-lg bg-gray-700/50 p-3">
              <div class="flex items-start gap-2">
                <div class="mt-1 h-3 w-3 flex-shrink-0 rounded-full bg-green-500"></div>
                <div class="text-sm text-gray-300">{{ activeOrder.pickup_address }}</div>
              </div>
              <div class="flex items-start gap-2">
                <div class="mt-1 h-3 w-3 flex-shrink-0 rounded-full bg-orange-500"></div>
                <div class="text-sm text-gray-300">{{ activeOrder.dropoff_address }}</div>
              </div>
            </div>
            
            <!-- Кнопка отмены -->
            <div v-if="['new', 'accepted', 'arrived'].includes(activeOrder.status)" class="mt-4">
              <button
                @click="cancelOrder"
                class="w-full rounded-lg bg-red-600 py-2 font-semibold text-white transition-colors hover:bg-red-700"
              >
                ❌ Отменить заказ
              </button>
            </div>
            
            <!-- Завершён/Отменён -->
            <div v-if="['completed', 'cancelled'].includes(activeOrder.status)" class="mt-4">
              <div v-if="activeOrder.status === 'cancelled'" class="mb-3 rounded-lg bg-red-500/20 p-3 text-center">
                <div class="text-sm text-red-400">
                  <span v-if="activeOrder.cancelled_by === 'driver'">🚗 Водитель отменил заказ</span>
                  <span v-else>❌ Вы отменили заказ</span>
                </div>
                <div v-if="activeOrder.cancellation_reason" class="text-xs text-gray-400 mt-1">
                  {{ activeOrder.cancellation_reason }}
                </div>
              </div>
              <div v-if="activeOrder.status === 'completed' && !hasReviewForLastOrder" class="mb-3 rounded-lg bg-yellow-500/20 p-3 text-center">
                <div class="text-sm text-yellow-400">
                  Поездка завершена! Оставьте отзыв водителю.
                </div>
              </div>
              <div class="flex gap-2">
                <button
                  v-if="lastCancelledOrder"
                  @click="repeatOrder"
                  class="flex-1 rounded-lg bg-yellow-500 py-2 font-semibold text-gray-900 transition-colors hover:bg-yellow-600"
                >
                  🔄 Повторить заказ
                </button>
                <button
                  @click="hasActiveOrder = false; activeOrder = null"
                  class="flex-1 rounded-lg bg-gray-600 py-2 font-semibold text-white transition-colors hover:bg-gray-700"
                >
                  Создать новый заказ
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Форма создания заказа -->
      <div v-if="!hasActiveOrder">
        <div class="mb-6 text-center">
          <h1 class="mb-2 text-2xl font-bold text-white">Заказать поездку</h1>
          <p class="text-gray-400">Укажите маршрут и выберите класс авто</p>
        </div>

        <form @submit.prevent="submitForm" class="rounded-xl bg-[#1F2937] p-4">
          <div class="mb-4">
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <div class="h-3 w-3 rounded-full bg-green-500"></div>
              </div>
              <input
                id="from-input"
                v-model="form.from"
                type="text"
                placeholder="Откуда"
                @input="calculateRouteDistance"
                class="w-full rounded-lg border-0 bg-gray-800 py-3 pl-12 pr-12 text-white placeholder-gray-500 focus:ring-2 focus:ring-yellow-500"
              />
              <button
                type="button"
                @click="openMapSelector"
                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg bg-gray-700 p-2 text-gray-400 hover:bg-gray-600 hover:text-white"
                title="Выбрать на карте"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </button>
            </div>
          </div>

          <div class="mb-4">
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <div class="h-3 w-3 rounded-full bg-orange-500"></div>
              </div>
              <input
                id="to-input"
                v-model="form.to"
                type="text"
                placeholder="Куда"
                @input="calculateRouteDistance"
                class="w-full rounded-lg border-0 bg-gray-800 py-3 pl-12 pr-12 text-white placeholder-gray-500 focus:ring-2 focus:ring-yellow-500"
              />
              <button
                type="button"
                @click="openMapSelector"
                class="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg bg-gray-700 p-2 text-gray-400 hover:bg-gray-600 hover:text-white"
                title="Выбрать на карте"
              >
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
              </button>
            </div>
          </div>

          <div class="mb-4">
            <label class="mb-2 block text-sm text-gray-400">Примерное расстояние (км)</label>
            <div class="flex items-center gap-3">
              <input
                v-model.number="form.distance"
                type="range"
                min="1"
                max="50"
                step="1"
                @input="updateDistance"
                class="flex-1 h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer"
              />
              <span class="w-12 text-center font-bold text-white">{{ form.distance }} км</span>
            </div>
          </div>

          <div class="mb-4 space-y-3">
            <div
              v-for="tariff in tariffs"
              :key="tariff.id"
              @click="selectTariff(tariff.id)"
              :class="[
                'cursor-pointer rounded-lg p-4 transition-all',
                form.tariff_id == tariff.id
                  ? 'border-2 border-yellow-500 bg-yellow-500/10'
                  : 'border border-transparent bg-gray-800 hover:border-gray-600'
              ]"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div :class="[
                    'flex h-10 w-10 items-center justify-center rounded-full',
                    form.tariff_id == tariff.id ? 'bg-yellow-500' : 'bg-gray-700'
                  ]">
                    <svg class="h-5 w-5" :class="form.tariff_id == tariff.id ? 'text-gray-900' : 'text-gray-400'" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5 1.5zM5 11 l1.5 -4.5 h11 L19 11 H5z"/>
                    </svg>
                  </div>
                  <div>
                    <div class="font-bold text-white">{{ tariff.name }}</div>
                    <div class="text-sm text-gray-400">от {{ tariff.base_price }} ₽ + {{ tariff.price_per_km }} ₽/км</div>
                  </div>
                </div>
                <div v-if="form.tariff_id == tariff.id">
                  <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
                  </svg>
                </div>
              </div>
              <div v-if="tariff.description && form.tariff_id == tariff.id" class="mt-2 text-sm text-gray-400">
                {{ tariff.description }}
              </div>
            </div>
          </div>

          <div v-if="calculatedPrice !== null" class="mb-4 rounded-lg bg-green-500/20 p-3 text-center">
            <div class="text-sm text-gray-400">Примерная стоимость</div>
            <div class="text-2xl font-bold text-green-400">{{ calculatedPrice }} ₽</div>
            <div class="text-xs text-gray-500">{{ form.distance }} км, ~{{ form.duration }} мин</div>
          </div>

          <div class="mb-4">
            <input
              v-model="form.notes"
              type="text"
              placeholder="Notes (optional)"
              class="w-full rounded-lg border-0 bg-gray-800 py-2 px-3 text-white placeholder-gray-500 text-sm"
            />
          </div>

          <button
            type="submit"
            :disabled="!isFormValid || isSubmitting"
            :class="[
              'w-full rounded-lg py-3 font-bold transition-all',
              isFormValid && !isSubmitting
                ? 'bg-yellow-500 text-gray-900 hover:bg-yellow-600'
                : 'cursor-not-allowed bg-gray-600 text-gray-400'
            ]"
          >
            {{ isSubmitting ? 'Создание заказа...' : 'Создать заказ' }}
          </button>
        </form>

        <!-- История заказов -->
        <div v-if="orderHistory.length > 0" class="mt-6">
          <h2 class="mb-3 text-lg font-bold text-white">История поездок</h2>
          <div class="space-y-3">
            <div
              v-for="order in orderHistory"
              :key="order.id"
              class="rounded-lg bg-[#1F2937] p-3 transition-all hover:bg-gray-800"
            >
              <div class="flex items-start justify-between">
                <div class="flex-1 min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-white">{{ order.order_number }}</span>
                    <span
                      :class="[
                        'text-xs px-2 py-0.5 rounded',
                        order.status === 'completed' ? 'bg-gray-500/20 text-gray-400' : 'bg-red-500/20 text-red-400'
                      ]"
                    >
                      {{ order.status === 'completed' ? 'Завершён' : 'Отменён' }}
                    </span>
                  </div>
                  <div class="mt-1 text-sm text-gray-400 truncate">
                    {{ order.pickup_address }} → {{ order.dropoff_address }}
                  </div>
                  <div class="mt-1 text-xs text-gray-500">
                    {{ formatDate(order.created_at) }}
                    <span v-if="order.distance" class="ml-2">• {{ order.distance }} км</span>
                    <span v-if="order.final_price" class="ml-2">• {{ order.final_price }} ₽</span>
                  </div>
                </div>
                <div class="ml-2 flex flex-shrink-0 flex-col gap-1">
                  <button
                    @click="repeatFromHistory(order)"
                    class="rounded-lg bg-yellow-500/20 px-3 py-1.5 text-sm font-medium text-yellow-500 hover:bg-yellow-500/30"
                  >
                    Повторить
                  </button>
                  <button
                    @click="hideFromHistory(order.id)"
                    class="rounded-lg bg-red-500/20 px-3 py-1.5 text-sm font-medium text-red-400 hover:bg-red-500/30"
                  >
                    Удалить
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Пагинация -->
          <div v-if="pagination.last_page > 1" class="mt-4 flex items-center justify-center gap-2">
            <button
              @click="changePage(pagination.current_page - 1)"
              :disabled="pagination.current_page === 1"
              class="rounded-lg bg-gray-700 px-3 py-1.5 text-sm text-gray-300 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              ← Назад
            </button>
            <span class="text-sm text-gray-400">
              {{ pagination.current_page }} / {{ pagination.last_page }}
            </span>
            <button
              @click="changePage(pagination.current_page + 1)"
              :disabled="pagination.current_page === pagination.last_page"
              class="rounded-lg bg-gray-700 px-3 py-1.5 text-sm text-gray-300 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Вперёд →
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Модальное окно с картой -->
    <div v-if="showMap" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
      <div class="w-full max-w-3xl rounded-2xl bg-gray-800 p-4 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-lg font-bold text-white">Выберите маршрут на карте</h2>
          <button
            @click="closeMapSelector"
            class="rounded-lg bg-gray-700 p-2 text-gray-400 hover:bg-gray-600 hover:text-white"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
        
        <!-- Панель выбора точек -->
        <div class="mb-3 space-y-2">
          <div class="flex items-center gap-2">
            <span class="text-2xl font-bold text-blue-500">🅰️</span>
            <input
              type="text"
              :value="fromCoords ? `${fromCoords[1].toFixed(4)}, ${fromCoords[0].toFixed(4)}` : ''"
              placeholder="Точка А (клик на карте)"
              readonly
              class="flex-1 rounded-lg border-0 bg-gray-700 px-3 py-2 text-white placeholder-gray-500"
            />
            <button
              @click="selectingPoint = 'from'"
              :class="[
                'px-4 py-2 rounded-lg font-semibold transition-all',
                selectingPoint === 'from' ? 'bg-orange-500 text-white animate-pulse' : 'bg-gray-600 text-gray-300 hover:bg-gray-500'
              ]"
            >
              {{ selectingPoint === 'from' ? 'Кликните...' : 'Выбрать' }}
            </button>
          </div>
          
          <div class="flex items-center gap-2">
            <span class="text-2xl font-bold text-red-500">🅱️</span>
            <input
              type="text"
              :value="toCoords ? `${toCoords[1].toFixed(4)}, ${toCoords[0].toFixed(4)}` : ''"
              placeholder="Точка Б (клик на карте)"
              readonly
              class="flex-1 rounded-lg border-0 bg-gray-700 px-3 py-2 text-white placeholder-gray-500"
            />
            <button
              @click="selectingPoint = 'to'"
              :class="[
                'px-4 py-2 rounded-lg font-semibold transition-all',
                selectingPoint === 'to' ? 'bg-orange-500 text-white animate-pulse' : 'bg-gray-600 text-gray-300 hover:bg-gray-500'
              ]"
            >
              {{ selectingPoint === 'to' ? 'Кликните...' : 'Выбрать' }}
            </button>
          </div>
          
          <!-- Кнопка построения маршрута -->
          <button
            @click="buildRoute"
            :disabled="!fromCoords || !toCoords"
            :class="[
              'w-full py-3 rounded-lg font-semibold transition-all',
              fromCoords && toCoords
                ? 'bg-blue-500 text-white hover:bg-blue-600'
                : 'bg-gray-600 text-gray-400 cursor-not-allowed'
            ]"
          >
            {{ fromCoords && toCoords ? '🚗 Построить маршрут' : 'Выберите обе точки' }}
          </button>
        </div>
        
        <!-- Информация о маршруте -->
        <div v-if="form.distance && form.duration" class="mb-3 rounded-lg bg-blue-500/20 p-3 flex items-center justify-between">
          <div class="text-sm text-blue-400">
            🚗 Маршрут построен
          </div>
          <div class="text-sm text-white font-semibold">
            {{ form.distance }} км (~{{ form.duration }} мин)
          </div>
        </div>
        
        <!-- Контейнер карты -->
        <div id="map-container" class="mb-4 h-96 w-full rounded-lg bg-gray-700"></div>
        
        <!-- Кнопки -->
        <div class="flex gap-3">
          <button
            @click="closeMapSelector"
            class="flex-1 rounded-lg bg-gray-700 py-3 font-semibold text-white transition-colors hover:bg-gray-600"
          >
            Отмена
          </button>
          <button
            @click="confirmMapSelection"
            :disabled="!fromCoords || !toCoords"
            :class="[
              'flex-1 rounded-lg py-3 font-semibold transition-colors',
              fromCoords && toCoords
                ? 'bg-green-500 text-white hover:bg-green-600'
                : 'bg-gray-600 text-gray-400 cursor-not-allowed'
            ]"
          >
            Готово
          </button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>