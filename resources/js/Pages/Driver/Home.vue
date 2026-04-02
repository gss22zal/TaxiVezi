<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { usePage } from '@inertiajs/vue3'
import MainLayout from '@/Layouts/MainLayout.vue'
import { useOrderNotifications } from '@/composables/useOrderNotifications'

// Настройки из админки
const page = usePage()
const settings = computed(() => page.props.settings?.api || {})
const ordersRepeatTime = computed(() => {
  const value = Number(settings.value.orders_repeat_time) || 5000
  console.log('ordersRepeatTime from settings:', value, 'raw:', settings.value.orders_repeat_time)
  return value
})
const driverRepeatTime = computed(() => {
  const value = Number(settings.value.driver_repeat_time) || 3000
  console.log('driverRepeatTime from settings:', value, 'raw:', settings.value.driver_repeat_time)
  return value
})

// Статус водителя
const driverStatus = ref('free') // free, busy, offline

const statusOptions = [
  { id: 'free', name: 'Свободен', color: 'green' },
  { id: 'busy', name: 'Занят', color: 'gray' },
  { id: 'offline', name: 'Офлайн', color: 'gray' }
]

const setStatus = async (status) => {
  // Отправляем статус на сервер
  try {
    const csrfToken = document.cookie
      .split('; ')
      .find(row => row.startsWith('XSRF-TOKEN='))
      ?.split('=')[1] || document.querySelector('meta[name="csrf-token"]')?.content

    const response = await fetch('/api/driver/status', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(csrfToken)
      },
      body: JSON.stringify({ status: status === 'free' ? 'online' : status })
    })

    if (response.ok) {
      driverStatus.value = status
    }
  } catch (e) {
    console.error('Ошибка изменения статуса:', e)
    // Всё равно меняем локально
    driverStatus.value = status
  }
}

// Данные водителя (будут загружены с сервера)
const driver = ref({
  name: '',
  car: '',
  avatar: null
})

// Загрузка профиля водителя
const loadDriverProfile = async () => {
  try {
    const response = await fetch('/api/driver/profile', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (response.ok) {
      const data = await response.json()
      if (data.driver) {
        driver.value.name = data.driver.name
        driver.value.car = data.driver.car
      }
    }
  } catch (e) {
    console.error('Ошибка загрузки профиля:', e)
  }
}

// Мониторинг новых заказов для водителя
const availableOrders = ref([])
const isLoadingActiveOrder = ref(true)
let pollTimer = null

// Уведомление об отмене заказа
const showCancellationModal = ref(false)
const cancellationReason = ref('')

// Звук отмены заказа
const playCancellationSound = () => {
  try {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)()

    // Противный гудок - низкая частота
    const oscillator = audioContext.createOscillator()
    const gainNode = audioContext.createGain()

    oscillator.connect(gainNode)
    gainNode.connect(audioContext.destination)

    oscillator.frequency.value = 200 // Низкая частота
    oscillator.type = 'sawtooth' // Противный звук

    gainNode.gain.setValueAtTime(0.5, audioContext.currentTime)
    gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.8)

    oscillator.start(audioContext.currentTime)
    oscillator.stop(audioContext.currentTime + 0.8)

    // Второй гудок
    setTimeout(() => {
      const osc2 = audioContext.createOscillator()
      const gain2 = audioContext.createGain()

      osc2.connect(gain2)
      gain2.connect(audioContext.destination)

      osc2.frequency.value = 200
      osc2.type = 'sawtooth'

      gain2.gain.setValueAtTime(0.5, audioContext.currentTime)
      gain2.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.8)

      osc2.start(audioContext.currentTime)
      osc2.stop(audioContext.currentTime + 0.8)
    }, 900)
  } catch (e) {
    console.warn('Audio not supported:', e)
  }
}

// Проверка статуса активного заказа
const checkActiveOrderStatus = async () => {
  if (!activeOrder.value || driverStatus.value !== 'busy') return

  try {
    const response = await fetch('/api/driver/active-order', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (response.ok) {
      const data = await response.json()

      console.log('Driver active order check:', data.order?.status)

      // Если заказ завершён - просто очищаем без уведомления
      if (data.order && data.order.status === 'completed') {
        console.log('Заказ завершён, сбрасываем статус')
        activeOrder.value = null
        driverStatus.value = 'free'
        return
      }

      // Если заказ был отменён пассажиром
      if (!data.order || data.order.status === 'cancelled') {
        // Пассажир отменил заказ!
        showCancellationModal.value = true

        // Формируем сообщение об отмене
        if (data.order?.cancelled_by === 'passenger') {
          cancellationReason.value = data.order.cancellation_reason || 'Пассажир отменил заказ'
        } else {
          cancellationReason.value = data.order?.cancellation_reason || 'Заказ был отменён'
        }

        // Проигрываем противный звук
        playCancellationSound()

        // Очищаем активный заказ
        activeOrder.value = null
        driverStatus.value = 'free'
      }
      // Если заказ завершён (completed) - просто очищаем без уведомления
      else if (data.order && data.order.status === 'completed') {
        activeOrder.value = null
        driverStatus.value = 'free'
      }
      // Если заказ в пути - обновляем локальный статус
      else if (data.order && data.order.status === 'in_transit') {
        if (activeOrder.value) {
          activeOrder.value.status = 'in_transit'
        }
      }
    }
  } catch (e) {
    console.error('Ошибка проверки статуса заказа:', e)
  }
}

// Закрыть модалку отмены и перезагрузить страницу
const closeCancellationModal = () => {
  showCancellationModal.value = false
  window.location.reload()
}

// Загрузка активного заказа при входе
const loadActiveOrder = async () => {
  try {
    const response = await fetch('/api/driver/active-order', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (response.ok) {
      const data = await response.json()

      if (data.order) {
        // Если заказ уже завершён - сбрасываем без уведомления
        if (data.order.status === 'completed') {
          console.log('Заказ уже завершён, сбрасываем')
          activeOrder.value = null
          driverStatus.value = 'free'
          return
        }

        // Если заказ уже отменён - не показываем его и не делаем водителя занятым
        if (data.order.status === 'cancelled') {
          console.log('Заказ был отменён, пропускаем')
          return
        }

        // У водителя есть активный заказ
        activeOrder.value = {
          id: data.order.order_number,
          orderId: data.order.id,
          pickup: data.order.pickup_address,
          dropoff: data.order.dropoff_address,
          price: data.order.final_price,
          distance: data.order.distance,
          timeLeft: Math.round(data.order.distance * 3),
          passenger: {
            name: data.order.passenger_name || 'Пассажир',
            phone: data.order.passenger_phone || '+7 900 000-00-00'
          }
        }
        driverStatus.value = 'busy'
      }
    }
  } catch (e) {
    console.error('Ошибка загрузки активного заказа:', e)
  } finally {
    isLoadingActiveOrder.value = false
  }
}

const checkAvailableOrders = async () => {
  if (driverStatus.value !== 'free') return

  try {
    const response = await fetch('/api/available-orders', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
      }
    })

    if (response.ok) {
      const orders = await response.json()

      // Если появились новые заказы и водитель свободен
      if (orders.length > availableOrders.value.length && driverStatus.value === 'free') {
        // Звуковое оповещение
        playNewOrderSound()
      }

      availableOrders.value = orders
    }
  } catch (e) {
    console.warn('Check orders error:', e)
  }
}

// Звук для нового заказа (отличается от административного)
const playNewOrderSound = () => {
  try {
    const audioContext = new (window.AudioContext || window.webkitAudioContext)()

    // Мелодия "Новый заказ" - более настойчивая
    const melody = [523, 659, 784, 1047] // C5, E5, G5, C6
    const duration = 0.2

    melody.forEach((freq, index) => {
      setTimeout(() => {
        const oscillator = audioContext.createOscillator()
        const gainNode = audioContext.createGain()

        oscillator.connect(gainNode)
        gainNode.connect(audioContext.destination)

        oscillator.frequency.value = freq
        oscillator.type = 'sine'

        gainNode.gain.setValueAtTime(0.4, audioContext.currentTime)
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration)

        oscillator.start(audioContext.currentTime)
        oscillator.stop(audioContext.currentTime + duration)
      }, index * 250)
    })
  } catch (e) {
    console.warn('Audio not supported:', e)
  }
}

// Воспроизведение звука оповещения (общая функция)
const playNotificationSound = () => {
  playNewOrderSound()
}

// Перезапуск мониторинга при изменении статуса
const restartPolling = () => {
  console.log('restartPolling called, ordersRepeatTime:', ordersRepeatTime.value, 'driverRepeatTime:', driverRepeatTime.value)

  if (pollTimer) {
    clearInterval(pollTimer)
  }

  if (driverStatus.value === 'free') {
    console.log('Starting polling with interval:', ordersRepeatTime.value, 'ms')
    pollTimer = setInterval(() => {
      checkAvailableOrders()
      checkActiveOrderStatus() // Также проверяем статус активного заказа
    }, ordersRepeatTime.value)
    checkAvailableOrders()
  } else {
    // Если занят - проверяем только статус активного заказа
    console.log('Starting status polling with interval:', driverRepeatTime.value, 'ms')
    pollTimer = setInterval(checkActiveOrderStatus, driverRepeatTime.value)
    checkActiveOrderStatus()
    availableOrders.value = []
  }
}

watch(driverStatus, () => {
  restartPolling()
})

// Перезапуск polling при изменении настроек
watch([ordersRepeatTime, driverRepeatTime], () => {
  console.log('Settings changed, restarting polling...')
  restartPolling()
})

onUnmounted(() => {
  if (pollTimer) {
    clearInterval(pollTimer)
  }
})

// Автомобиль
const car = ref(null)
const cars = ref([])
const showCarModal = ref(false)
const editingCar = ref(null)
const carSectionExpanded = ref(false) // Секция автомобиля свернута по умолчанию

const carForm = useForm({
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
  is_primary: true
})

const carClassOptions = [
  { id: 'econom', name: 'Эконом' },
  { id: 'comfort', name: 'Комфорт' },
  { id: 'business', name: 'Бизнес' },
  { id: 'minivan', name: 'Минивэн' },
  { id: 'premium', name: 'Премиум' },
]

// Загрузка автомобилей
const loadCars = async () => {
  try {
    const response = await fetch('/driver/cars', {
      headers: { 'Accept': 'application/json' }
    })
    cars.value = await response.json()
    // Основной автомобиль
    car.value = cars.value.find(c => c.is_primary) || cars.value[0] || null
    if (car.value) {
      driver.value.car = `${car.value.brand} ${car.value.model} • ${car.value.plate_number}`
    }
  } catch (e) {
    console.error('Ошибка загрузки автомобилей:', e)
  }
}

onMounted(async () => {
  console.log('Component mounted, settings:', page.props.settings)
  console.log('ordersRepeatTime:', ordersRepeatTime.value)
  console.log('driverRepeatTime:', driverRepeatTime.value)

  await loadDriverProfile()
  await loadCars()
  await loadActiveOrder()
  await loadTripHistory()

  // Запускаем мониторинг заказов с настройками из админки
  if (driverStatus.value === 'free') {
    console.log('Starting polling on mount with interval:', ordersRepeatTime.value, 'ms')
    pollTimer = setInterval(checkAvailableOrders, ordersRepeatTime.value)
    checkAvailableOrders()
  }
})

// Открыть модалку добавления/редактирования
const openCarModal = (carToEdit = null) => {
  editingCar.value = carToEdit
  if (carToEdit) {
    carForm.brand = carToEdit.brand
    carForm.model = carToEdit.model
    carForm.year = carToEdit.year || ''
    carForm.color = carToEdit.color || ''
    carForm.plate_number = carToEdit.plate_number
    carForm.region_code = carToEdit.region_code || ''
    carForm.car_class = carToEdit.car_class
    carForm.vin_number = carToEdit.vin_number || ''
    carForm.insurance_number = carToEdit.insurance_number || ''
    carForm.insurance_expiry = carToEdit.insurance_expiry || ''
    carForm.tech_inspection_expiry = carToEdit.tech_inspection_expiry || ''
    carForm.is_primary = carToEdit.is_primary
  } else {
    carForm.reset()
    carForm.is_primary = !cars.value.length
  }
  showCarModal.value = true
}

const closeCarModal = () => {
  showCarModal.value = false
  editingCar.value = null
  carForm.reset()
}

// Сохранить автомобиль
const saveCar = async () => {
  try {
    const url = editingCar.value ? `/driver/cars/${editingCar.value.id}` : '/driver/cars'
    const method = editingCar.value ? 'PUT' : 'POST'

    const response = await fetch(url, {
      method,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
      },
      body: JSON.stringify(carForm)
    })

    if (response.ok) {
      await loadCars()
      closeCarModal()
    } else {
      const error = await response.json()
      alert('Ошибка: ' + (error.message || 'Не удалось сохранить'))
    }
  } catch (e) {
    alert('Ошибка сохранения')
  }
}

// Удалить автомобиль
const deleteCar = async (carToDelete) => {
  if (!confirm(`Удалить автомобиль ${carToDelete.brand} ${carToDelete.model}?`)) return

  try {
    const response = await fetch(`/driver/cars/${carToDelete.id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.content
      }
    })

    if (response.ok) {
      await loadCars()
    }
  } catch (e) {
    alert('Ошибка удаления')
  }
}

// Активный заказ
const activeOrder = ref(null)

// Статистика за сегодня
const stats = ref({
  trips: 0,
  earnings: 0,
  distance: 0,
  rating: 0
})

// История поездок
const tripHistory = ref([])
const tripHistoryLoading = ref(true)
const tripPagination = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1
})
const tripStats = ref({
  total_trips: 0,
  total_earnings: 0,
  total_distance: 0,
  today_trips: 0,
  today_earnings: 0
})

// Загрузка истории поездок с пагинацией
const loadTripHistory = async (page = 1) => {
  tripHistoryLoading.value = true
  try {
    const response = await fetch(`/api/driver/orders/history?page=${page}`, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    if (response.ok) {
      const data = await response.json()
      console.log('Trip history loaded:', data.stats)
      tripHistory.value = data.orders || []
      tripStats.value = data.stats || tripStats.value
      tripPagination.value = data.pagination || {
        current_page: 1,
        per_page: 10,
        total: 0,
        last_page: 1
      }
      
      // Обновляем статистику за сегодня из API
      if (data.stats) {
        console.log('Updating stats:', {
          trips: data.stats.today_trips,
          earnings: data.stats.today_earnings,
          distance: data.stats.today_distance
        })
        stats.value.trips = data.stats.today_trips || 0
        stats.value.earnings = Number(data.stats.today_earnings) || 0
        stats.value.distance = Number(data.stats.today_distance) || 0
      }
    }
  } catch (e) {
    console.error('Ошибка загрузки истории поездок:', e)
  } finally {
    tripHistoryLoading.value = false
  }
}

// Переключение страницы истории
const changeTripPage = (page) => {
  if (page >= 1 && page <= tripPagination.value.last_page) {
    loadTripHistory(page)
  }
}

// Скрыть заказ из истории
const hideFromTripHistory = async (orderId) => {
  if (!confirm('Удалить этот заказ из истории?')) return
  
  try {
    const response = await fetch(`/api/driver/orders/${orderId}/hide`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })

    const data = await response.json()
    console.log('Hide order response:', data)
    
    if (response.ok && data.success) {
      // Удаляем заказ из локального списка
      tripHistory.value = tripHistory.value.filter(o => o.id !== orderId)
      // Обновляем пагинацию
      tripPagination.value.total--
      tripPagination.value.last_page = Math.ceil(tripPagination.value.total / tripPagination.value.per_page)
    } else {
      alert(data.message || 'Ошибка при удалении')
    }
  } catch (error) {
    console.error('Error hiding order:', error)
    alert('Не удалось удалить заказ')
  }
}

// Принять заказ
const acceptOrder = async (order) => {
  console.log('Принимаем заказ:', order)

  // Получаем CSRF токен из cookie
  const csrfToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1] || document.querySelector('meta[name="csrf-token"]')?.content

  try {
    const response = await fetch(`/api/orders/${order.id}/accept`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(csrfToken)
      }
    })

    const result = await response.json()
    console.log('Ответ принятия заказа:', { status: response.status, result })

    if (response.ok) {

      // Устанавливаем активный заказ из ответа сервера
      const orderData = result.order || order
      activeOrder.value = {
        id: orderData.order_number || order.order_number,
        orderId: orderData.id || order.id,
        pickup: orderData.pickup_address || order.pickup_address,
        dropoff: orderData.dropoff_address || order.dropoff_address,
        price: orderData.final_price || order.final_price,
        distance: orderData.distance || order.distance,
        timeLeft: Math.round((orderData.distance || order.distance) * 3),
        passenger: {
          name: orderData.passenger_name || 'Пассажир',
          phone: orderData.passenger_phone || '+7 900 000-00-00'
        }
      }

      // Удаляем из списка доступных
      availableOrders.value = availableOrders.value.filter(o => o.id !== order.id)

      // Меняем статус на занят
      driverStatus.value = 'busy'

      alert('Заказ принят!')
    } else {
      alert('Ошибка: ' + (result.message || 'Не удалось принять заказ'))
    }
  } catch (e) {
    console.error('Ошибка принятия заказа:', e)
    alert('Ошибка принятия заказа')
  }
}

const openNavigation = () => {
  const url = `https://yandex.ru/maps/?text=${encodeURIComponent(activeOrder.value.dropoff)}`
  window.open(url, '_blank')
}

const callPassenger = () => {
  window.location.href = `tel:${activeOrder.value.passenger.phone.replace(/\s/g, '')}`
}

// Начать поездку (пассажир сел в такси)
const startTrip = async () => {
  if (!activeOrder.value?.orderId) {
    alert('Нет активного заказа')
    return
  }

  const orderId = activeOrder.value.orderId
  console.log('=== START TRIP ===')
  console.log('orderId:', orderId)
  console.log('activeOrder:', activeOrder.value)

  // Получаем CSRF токен
  const csrfToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1] || document.querySelector('meta[name="csrf-token"]')?.content

  try {
    const response = await fetch(`/api/orders/${orderId}/start-trip`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(csrfToken)
      }
    })

    const result = await response.json()

    console.log('Start trip response:', { status: response.status, result })

    if (response.ok) {
      // Обновляем локальный статус
      if (activeOrder.value) {
        activeOrder.value.status = 'in_transit'
      }
      alert('Поездка началась! Пассажир в пути.')
    } else {
      alert('Ошибка: ' + (result.message || 'Не удалось начать поездку'))
    }
  } catch (e) {
    console.error('Ошибка начала поездки:', e)
    alert('Ошибка начала поездки')
  }
}

// Отметить "у клиента" (водитель прибыл)
const arrivedAtCustomer = async () => {
  if (!activeOrder.value?.orderId) {
    alert('Нет активного заказа')
    return
  }

  const orderId = activeOrder.value.orderId

  console.log('=== ARRIVED AT CUSTOMER ===')
  console.log('orderId:', orderId)

  // Получаем CSRF токен
  const csrfToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1] || document.querySelector('meta[name="csrf-token"]')?.content

  try {
    const response = await fetch(`/api/orders/${orderId}/arrived`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(csrfToken)
      }
    })

    const result = await response.json()

    console.log('Arrived response:', { status: response.status, result })

    if (response.ok) {
      // Обновляем локальный статус
      if (activeOrder.value) {
        activeOrder.value.status = 'arrived'
      }
      alert('Клиент уведомлён! Ожидайте посадки.')
    } else {
      alert('Ошибка: ' + (result.message || 'Не удалось отметить прибытие'))
    }
  } catch (e) {
    console.error('Ошибка прибытия:', e)
    alert('Ошибка отправки уведомления')
  }
}

// Отменить заказ (водитель)
const cancelOrderByDriver = async () => {
  if (!activeOrder.value?.orderId) {
    alert('Нет активного заказа')
    return
  }

  const reason = prompt('Причина отмены заказа:')
  if (reason === null) return // Отмена

  const orderId = activeOrder.value.orderId

  // Получаем CSRF токен
  const csrfToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1] || document.querySelector('meta[name="csrf-token"]')?.content

  try {
    const response = await fetch(`/api/orders/${orderId}/driver-cancel`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(csrfToken)
      },
      body: JSON.stringify({ reason: reason || '' })
    })

    const result = await response.json()

    if (response.ok) {
      alert('Заказ отменён')
      activeOrder.value = null
      driverStatus.value = 'free'
    } else {
      alert('Ошибка: ' + (result.error || 'Не удалось отменить заказ'))
    }
  } catch (e) {
    console.error('Ошибка отмены заказа:', e)
    alert('Ошибка отмены заказа')
  }
}

// Завершить заказ
const completeOrder = async () => {
  if (!activeOrder.value?.orderId) {
    alert('Нет активного заказа')
    return
  }

  if (!confirm('Завершить заказ?')) return

  const orderId = activeOrder.value.orderId
  const orderPrice = activeOrder.value?.price || 0
  const orderDistance = activeOrder.value?.distance || 0

  console.log('Завершаем заказ:', { orderId, activeOrder: activeOrder.value })

  // Получаем CSRF токен
  const csrfToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1] || document.querySelector('meta[name="csrf-token"]')?.content

  try {
    const response = await fetch(`/api/orders/${orderId}/complete`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(csrfToken)
      }
    })

    // Сохраняем результат один раз
    const result = await response.json()

    console.log('Ответ сервера:', { status: response.status, result })

    if (response.ok) {
      console.log('Заказ завершен:', result)

      // Сохраняем ID завершённого заказа для отзыва
      completedOrderId.value = orderId

      // Очищаем активный заказ
      activeOrder.value = null

      // Обновляем статистику правильными значениями с сервера
      const earnings = result.order?.driver_earnings || result.order?.final_price || orderPrice
      const distance = result.order?.distance || orderDistance
      stats.value.trips++
      stats.value.earnings += Number(earnings)
      stats.value.distance += Number(distance)

      // Возвращаем водителя в свободные
      driverStatus.value = 'free'

      // Показываем модалку отзыва
      showReviewModal.value = true
    } else {
      alert('Ошибка: ' + (result.message || 'Не удалось завершить заказ') + (result.debug ? '\n' + JSON.stringify(result.debug, null, 2) : ''))
    }
  } catch (e) {
    console.error('Ошибка завершения заказа:', e)
    alert('Ошибка завершения заказа: ' + e.message)
  }
}

// === ОТЗЫВ ВОДИТЕЛЯ О ПАССАЖИРЕ ===
const showReviewModal = ref(false)
const completedOrderId = ref(null)
const reviewRating = ref(5)
const reviewComment = ref('')
const reviewTags = ref([])
const isSubmittingReview = ref(false)

const availableTags = [
  'Вежливый',
  'Точный адрес',
  'Без опозданий',
  'Хорошее настроение',
  'Не навязчивый',
  'Разговорчивый',
  'Молчаливый',
  'Курил в салоне',
  'Опоздал',
  'Неверный адрес'
]

const submitDriverReview = async () => {
  if (!completedOrderId.value) {
    alert('Нет заказа для отзыва')
    return
  }

  isSubmittingReview.value = true

  const csrfToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1] || document.querySelector('meta[name="csrf-token"]')?.content

  try {
    const response = await fetch('/api/review/driver', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-XSRF-TOKEN': decodeURIComponent(csrfToken)
      },
      body: JSON.stringify({
        order_id: completedOrderId.value,
        rating: reviewRating.value,
        comment: reviewComment.value || null,
        tags: reviewTags.value.length > 0 ? reviewTags.value : null
      })
    })

    const result = await response.json()

    if (response.ok) {
      alert('Спасибо за отзыв!')
      closeReviewModal()
    } else {
      alert('Ошибка: ' + (result.message || 'Не удалось отправить отзыв'))
    }
  } catch (e) {
    console.error('Ошибка отправки отзыва:', e)
    alert('Ошибка отправки отзыва')
  } finally {
    isSubmittingReview.value = false
  }
}

const closeReviewModal = () => {
  showReviewModal.value = false
  completedOrderId.value = null
  reviewRating.value = 5
  reviewComment.value = ''
  reviewTags.value = []
}

const toggleTag = (tag) => {
  const index = reviewTags.value.indexOf(tag)
  if (index === -1) {
    reviewTags.value.push(tag)
  } else {
    reviewTags.value.splice(index, 1)
  }
}
</script>

<template>
  <MainLayout activeRole="driver">
    <div class="mx-auto max-w-md space-y-4">
      <!-- Профиль водителя -->
      <div class="rounded-xl bg-[#1F2937] p-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-700">
              <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
            </div>
            <div>
              <div class="font-bold text-white">{{ driver.name }}</div>
              <div class="text-sm text-gray-400">{{ driver.car }}</div>
            </div>
          </div>
          <div class="text-right">
            <!-- Индикатор статуса - меняется в зависимости от наличия активного заказа -->
            <div v-if="driverStatus === 'free'" class="flex items-center gap-1 text-green-500">
              <span class="h-2 w-2 rounded-full bg-green-500"></span>
              <span class="text-sm font-medium">Свободен</span>
            </div>
            <div v-else class="flex items-center gap-1 text-yellow-500">
              <span class="h-2 w-2 rounded-full bg-yellow-500 animate-pulse"></span>
              <span class="text-sm font-medium">Занят</span>
            </div>
          </div>
        </div>

        <!-- Кнопки статуса -->
        <div class="mt-4 flex gap-2">
          <button v-for="status in statusOptions" :key="status.id" @click="setStatus(status.id)" :class="[
            'flex-1 rounded-lg py-2 text-sm font-medium transition-all',
            driverStatus === status.id
              ? status.color === 'green' ? 'bg-green-600 text-white' : 'bg-yellow-500 text-gray-900'
              : 'bg-gray-700 text-gray-400 hover:bg-gray-600'
          ]">
            {{ status.name }}
          </button>
        </div>
      </div>

      <!-- Автомобиль (свёрнут по умолчанию) -->
      <div class="rounded-xl bg-[#1F2937] p-3">
        <!-- Заголовок с кнопкой разворачивания -->
        <div class="mb-2 flex items-center justify-between">
          <button @click="carSectionExpanded = !carSectionExpanded"
            class="flex items-center gap-1.5 text-xs font-medium uppercase text-gray-400 hover:text-white transition-colors">
            <svg class="h-3 w-3 transition-transform" :class="carSectionExpanded ? 'rotate-90' : ''" fill="none"
              stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
            Авто
          </button>
          <button v-if="carSectionExpanded" @click="openCarModal()"
            class="text-xs text-yellow-500 hover:text-yellow-400">
            + Добавить
          </button>
        </div>

        <!-- ✅ БЛОК 1: Развёрнутое состояние -->
        <div v-if="carSectionExpanded">
          <div v-if="car" class="rounded-lg bg-gray-800 p-4">
            <!-- Детали автомобиля -->
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-700">
                  <svg class="h-6 w-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                  </svg>
                </div>
                <div>
                  <div class="font-bold text-white">{{ car.brand }} {{ car.model }}</div>
                  <div class="text-sm text-gray-400">{{ car.plate_number }} • {{ car.color || 'Без цвета' }}</div>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <span v-if="car.is_primary"
                  class="rounded bg-yellow-500/20 px-2 py-1 text-xs font-medium text-yellow-500">
                  Основной
                </span>
                <button @click="openCarModal(car)" class="rounded-lg bg-gray-700 p-2 text-gray-400 hover:text-white">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                  </svg>
                </button>
              </div>
            </div>
            <div class="mt-3 flex items-center justify-between text-sm">
              <span class="text-gray-400">Класс: <span class="text-white">{{carClassOptions.find(c => c.id ===
                car.car_class)?.name || car.car_class }}</span></span>
              <span v-if="car.year" class="text-gray-400">Год: <span class="text-white">{{ car.year }}</span></span>
            </div>
          </div>

          <div v-else class="rounded-lg bg-gray-800/50 p-6 text-center">
            <svg class="mx-auto h-8 w-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
            </svg>
            <div class="mt-2 text-gray-400">Нет автомобиля</div>
            <button @click="openCarModal()"
              class="mt-2 rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-yellow-600">
              Добавить автомобиль
            </button>
          </div>
        </div>

        <!-- ✅ БЛОК 2: Свёрнуто + есть авто (v-else-if сразу после v-if!) -->

        <div v-else-if="car" @click="carSectionExpanded = true"
          class="flex cursor-pointer items-center gap-2 rounded-lg bg-gray-800/50 px-3 py-2 text-xs hover:bg-gray-800 transition-colors w-full overflow-hidden">
          <!-- Иконка слева (фиксированная) -->
          <svg class="h-4 w-4 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
          </svg>

          <!-- Основной текст (гибкий и обрезаемый) -->
          <div class="flex-1 min-w-0 truncate text-gray-300">
            <span>{{ car.brand }} {{ car.model }} • {{ car.plate_number }}</span>
            <span v-if="car.car_class" class="text-gray-500 ml-1">
              • {{carClassOptions.find(c => c.id === car.car_class)?.name || car.car_class}}
            </span>
          </div>

          <!-- Стрелка справа (фиксированная) -->
          <svg class="h-3 w-3 text-gray-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
          </svg>
        </div>


        <!-- ✅ БЛОК 3: Свёрнуто + нет авто (v-else) -->
        <div v-else @click="carSectionExpanded = true"
          class="flex cursor-pointer items-center gap-2 rounded-lg bg-gray-800/30 px-3 py-2 text-xs hover:bg-gray-800/50 transition-colors">
          <svg class="h-4 w-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          <span class="text-gray-500">Нет автомобиля</span>
          <span class="ml-auto text-xs text-yellow-500">+ Добавить</span>
        </div>
      </div>

      <!-- Доступные заказы (показываем только когда водитель свободен) -->
      <div v-if="driverStatus === 'free' && availableOrders.length > 0" class="rounded-xl bg-[#1F2937] p-4">
        <div class="mb-3 flex items-center justify-between">
          <div class="text-sm font-medium uppercase text-gray-400">
            Доступные заказы
            <span class="ml-2 rounded-full bg-blue-500 px-2 py-0.5 text-xs text-white">{{ availableOrders.length
              }}</span>
          </div>
          <span class="text-xs text-gray-500">Обновляется...</span>
        </div>

        <div class="space-y-3">
          <div v-for="order in availableOrders" :key="order.id"
            class="rounded-lg border border-gray-700 bg-gray-800/50 p-3 transition-all hover:border-yellow-500/50">
            <div class="flex items-start justify-between mb-2">
              <div>
                <span class="text-sm font-bold text-white">{{ order.order_number }}</span>
                <span class="ml-2 text-xs text-gray-400">{{ order.status === 'new' ? 'Новый' : 'Принят' }}</span>
              </div>
              <span class="text-lg font-bold text-yellow-500">{{ order.final_price }} ₽</span>
            </div>

            <div class="space-y-2 mb-3">
              <div class="flex items-start gap-2">
                <div class="mt-1 h-2 w-2 rounded-full bg-green-500 flex-shrink-0"></div>
                <div class="text-sm text-gray-300 truncate">{{ order.pickup_address }}</div>
              </div>
              <div class="flex items-start gap-2">
                <div class="mt-1 h-2 w-2 rounded-full bg-orange-500 flex-shrink-0"></div>
                <div class="text-sm text-gray-300 truncate">{{ order.dropoff_address }}</div>
              </div>
            </div>

            <div class="flex items-center justify-between">
              <div class="flex items-center gap-3 text-xs text-gray-400">
                <span class="flex items-center gap-1">
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                  </svg>
                  {{ order.distance }} км
                </span>
              </div>
              <button @click="acceptOrder(order)"
                class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-bold text-gray-900 hover:bg-yellow-600 transition-all">
                Принять
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Сообщение когда нет заказов и водитель свободен -->
      <div v-else-if="driverStatus === 'free'" class="rounded-xl bg-[#1F2937] p-4">
        <div class="flex items-center justify-between">
          <div class="text-sm font-medium uppercase text-gray-400">Доступные заказы</div>
          <span class="flex items-center gap-1 text-xs text-gray-500">
            <span class="h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
            Ожидание...
          </span>
        </div>
        <div class="mt-4 rounded-lg bg-gray-800/50 p-6 text-center">
          <svg class="mx-auto h-10 w-10 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
          </svg>
          <div class="mt-2 text-gray-400">Нет доступных заказов</div>
          <div class="text-xs text-gray-500 mt-1">Новые заказы появятся автоматически</div>
        </div>
      </div>

      <!-- Активный заказ -->
      <div v-if="activeOrder" class="rounded-xl border-2 border-yellow-500 bg-[#1F2937] p-4">
        <div class="mb-4 flex items-center justify-between">
          <span class="text-sm font-bold uppercase text-yellow-500">Активный заказ</span>
          <span class="text-sm text-gray-400">{{ activeOrder.id }}</span>
        </div>

        <!-- Маршрут -->
        <div class="space-y-3 mb-4">
          <div class="flex items-start gap-3">
            <div class="mt-1 h-3 w-3 rounded-full bg-green-500"></div>
            <div>
              <div class="text-xs text-gray-400">Забрать</div>
              <div class="font-bold text-white">{{ activeOrder.pickup }}</div>
            </div>
          </div>
          <div class="flex items-start gap-3">
            <div class="mt-1 h-3 w-3 rounded-full bg-orange-500"></div>
            <div>
              <div class="text-xs text-gray-400">Доставить</div>
              <div class="font-bold text-white">{{ activeOrder.dropoff }}</div>
            </div>
          </div>
        </div>

        <!-- Навигация -->
        <div class="mb-4 flex gap-2">
          <button @click="openNavigation"
            class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-yellow-500 py-3 font-bold text-gray-900 transition-all hover:bg-yellow-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            Навигация
          </button>
          <button @click="callPassenger"
            class="flex items-center justify-center rounded-lg bg-gray-700 px-4 py-3 text-gray-400 transition-all hover:bg-gray-600 hover:text-white">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
              <path
                d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z" />
            </svg>
          </button>
        </div>

        <!-- Инфо-блоки -->
        <div class="mb-4 grid grid-cols-3 gap-3">
          <div class="rounded-lg bg-gray-700/50 p-3 text-center">
            <div class="text-lg font-bold text-yellow-500">{{ activeOrder.price }} ₽</div>
            <div class="text-xs text-gray-400">оплата</div>
          </div>
          <div class="rounded-lg bg-gray-700/50 p-3 text-center">
            <div class="text-lg font-bold text-white">{{ activeOrder.distance }} км</div>
            <div class="text-xs text-gray-400">дистанция</div>
          </div>
          <div class="rounded-lg bg-gray-700/50 p-3 text-center">
            <div class="text-lg font-bold text-white">{{ activeOrder.timeLeft }} мин</div>
            <div class="text-xs text-gray-400">осталось</div>
          </div>
        </div>

        <!-- Кнопки "У клиента" и "В пути" -->
        <div class="mb-4 flex gap-2">
          <button @click="arrivedAtCustomer"
            class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-green-600 py-3 font-bold text-white transition-all hover:bg-green-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            У КЛИЕНТА
          </button>
          <button @click="startTrip"
            class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 py-3 font-bold text-white transition-all hover:bg-blue-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            В ПУТИ
          </button>
        </div>

        <!-- Кнопки действий -->
        <div class="flex gap-2">
          <button @click="cancelOrderByDriver"
            class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-red-600 py-3 font-bold text-white transition-all hover:bg-red-700">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Отменить
          </button>
          <button @click="completeOrder"
            class="flex flex-1 items-center justify-center gap-2 rounded-lg bg-[#065F46] py-3 font-bold text-green-400 transition-all hover:bg-green-700">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
              <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z" />
            </svg>
            Завершить
          </button>
        </div>
      </div>

      <!-- Статистика за сегодня -->
      <div class="rounded-xl bg-[#1F2937] p-4">
        <div class="mb-3 text-sm font-medium uppercase text-gray-400">Смена сегодня</div>
        <div class="grid grid-cols-2 gap-3">
          <div class="rounded-lg bg-gray-800 p-4">
            <div class="flex items-center gap-2">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
              </svg>
              <span class="text-2xl font-bold text-white">{{ stats.trips }}</span>
            </div>
            <div class="text-sm text-gray-400">Поездок</div>
          </div>
          <div class="rounded-lg bg-gray-800 p-4">
            <div class="flex items-center gap-2">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span class="text-2xl font-bold text-white">{{ stats.earnings }} ₽</span>
            </div>
            <div class="text-sm text-gray-400">Заработано</div>
          </div>
          <div class="rounded-lg bg-gray-800 p-4">
            <div class="flex items-center gap-2">
              <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
              </svg>
              <span class="text-2xl font-bold text-white">{{ stats.distance }} км</span>
            </div>
            <div class="text-sm text-gray-400">Километраж</div>
          </div>
          <div class="rounded-lg bg-gray-800 p-4">
            <div class="flex items-center gap-2">
              <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
              </svg>
              <span class="text-2xl font-bold text-white">{{ stats.rating }} ★</span>
            </div>
            <div class="text-sm text-gray-400">Рейтинг</div>
          </div>
        </div>
      </div>

      <!-- История поездок -->
      <div class="rounded-xl bg-[#1F2937] p-4">
        <div class="mb-3 flex items-center justify-between">
          <div class="text-sm font-medium uppercase text-gray-400">История поездок</div>
          <div class="text-xs text-gray-500">
            {{ tripPagination.total }} поездок
          </div>
        </div>

        <!-- Список поездок -->
        <div v-if="tripHistoryLoading" class="py-4 text-center text-gray-500">
          Загрузка...
        </div>
        <div v-else-if="tripHistory.length === 0" class="py-4 text-center text-gray-500">
          Нет завершённых поездок
        </div>
        <div v-else class="space-y-3">
          <div v-for="trip in tripHistory" :key="trip.id"
            class="flex items-center justify-between border-b border-gray-700 pb-3 last:border-0">
            <div class="flex-1 min-w-0">
              <div class="text-xs text-gray-500">{{ trip.order_number }}</div>
              <div class="truncate text-sm text-white">{{ trip.dropoff_address || trip.pickup_address }}</div>
              <div class="text-xs text-gray-400">
                {{ trip.distance ? trip.distance + ' км' : '' }}
                {{ trip.passenger_name ? '• ' + trip.passenger_name : '' }}
              </div>
            </div>
            <div class="flex flex-col items-end gap-1 ml-3">
              <div class="font-bold text-white">{{ trip.driver_earnings || trip.final_price }} ₽</div>
              <div class="text-xs text-gray-400">
                {{ trip.completed_at ? new Date(trip.completed_at).toLocaleDateString('ru-RU') : '' }}
              </div>
            </div>
          </div>
        </div>

        <!-- Пагинация -->
        <div v-if="tripPagination.last_page > 1" class="mt-4 flex items-center justify-center gap-2">
          <button
            @click="changeTripPage(tripPagination.current_page - 1)"
            :disabled="tripPagination.current_page === 1"
            class="rounded-lg bg-gray-700 px-3 py-1.5 text-sm text-gray-300 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            ← Назад
          </button>
          <span class="text-sm text-gray-400">
            {{ tripPagination.current_page }} / {{ tripPagination.last_page }}
          </span>
          <button
            @click="changeTripPage(tripPagination.current_page + 1)"
            :disabled="tripPagination.current_page === tripPagination.last_page"
            class="rounded-lg bg-gray-700 px-3 py-1.5 text-sm text-gray-300 hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Вперёд →
          </button>
        </div>
      </div>
    </div>

    <!-- Модальное окно автомобиля -->
    <div v-if="showCarModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
      <div class="w-full max-w-md rounded-xl bg-[#1F2937] p-4">
        <h3 class="mb-4 text-lg font-bold text-white">
          {{ editingCar ? 'Редактировать автомобиль' : 'Добавить автомобиль' }}
        </h3>

        <form @submit.prevent="saveCar" class="space-y-3">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-xs text-gray-400">Марка *</label>
              <input v-model="carForm.brand" type="text" required
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white text-sm"
                placeholder="KIA" />
            </div>
            <div>
              <label class="mb-1 block text-xs text-gray-400">Модель *</label>
              <input v-model="carForm.model" type="text" required
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white text-sm"
                placeholder="Rio" />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="mb-1 block text-xs text-gray-400">Год</label>
              <input v-model="carForm.year" type="number"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white text-sm"
                placeholder="2023" />
            </div>
            <div>
              <label class="mb-1 block text-xs text-gray-400">Цвет</label>
              <input v-model="carForm.color" type="text"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white text-sm"
                placeholder="Серебристый" />
            </div>
            <div>
              <label class="mb-1 block text-xs text-gray-400">Класс *</label>
              <select v-model="carForm.car_class"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white text-sm">
                <option v-for="opt in carClassOptions" :key="opt.id" :value="opt.id">
                  {{ opt.name }}
                </option>
              </select>
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-xs text-gray-400">Госномер *</label>
              <input v-model="carForm.plate_number" type="text" required
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white text-sm"
                placeholder="А123ВГ" />
            </div>
            <div>
              <label class="mb-1 block text-xs text-gray-400">Регион</label>
              <input v-model="carForm.region_code" type="text"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white text-sm"
                placeholder="77" />
            </div>
          </div>

          <div>
            <label class="mb-1 block text-xs text-gray-400">VIN</label>
            <input v-model="carForm.vin_number" type="text"
              class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white text-sm"
              placeholder="XW8ABCD..." />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="mb-1 block text-xs text-gray-400">Страховка</label>
              <input v-model="carForm.insurance_number" type="text"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white text-sm"
                placeholder="№ полиса" />
            </div>
            <div>
              <label class="mb-1 block text-xs text-gray-400">Страховка до</label>
              <input v-model="carForm.insurance_expiry" type="date"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white text-sm" />
            </div>
          </div>

          <div class="flex items-center gap-2">
            <input v-model="carForm.is_primary" type="checkbox" id="is_primary"
              class="h-4 w-4 rounded border-gray-600 bg-gray-700 text-yellow-500" />
            <label for="is_primary" class="text-sm text-gray-300">Основной автомобиль</label>
          </div>

          <div class="flex gap-2 pt-2">
            <button v-if="editingCar" type="button" @click="deleteCar(editingCar)"
              class="rounded-lg bg-red-900/50 px-3 py-2 text-sm text-red-400 hover:bg-red-900">
              Удалить
            </button>
            <div class="flex-1"></div>
            <button type="button" @click="closeCarModal"
              class="rounded-lg border border-gray-600 px-3 py-2 text-sm text-gray-300 hover:bg-gray-700">
              Отмена
            </button>
            <button type="submit" class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-gray-900">
              Сохранить
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модальное окно отмены заказа пассажиром -->
    <div v-if="showCancellationModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70">
      <div class="mx-4 w-full max-w-sm rounded-xl bg-red-900 p-6 text-center">
        <div class="mb-4 flex justify-center">
          <div class="flex h-20 w-20 items-center justify-center rounded-full bg-red-800">
            <svg class="h-10 w-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
          </div>
        </div>

        <h2 class="mb-2 text-xl font-bold text-white">Заказ отменён!</h2>
        <p class="mb-4 text-red-200">{{ cancellationReason }}</p>

        <button @click="closeCancellationModal"
          class="w-full rounded-lg bg-white py-3 font-bold text-red-900 transition-all hover:bg-gray-100">
          ОК
        </button>
      </div>
    </div>

    <!-- Модальное окно отзыва водителя о пассажире -->
    <div v-if="showReviewModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 p-4">
      <div class="w-full max-w-md rounded-xl bg-[#1F2937] p-6">
        <h2 class="mb-4 text-xl font-bold text-white text-center">Оцените пассажира</h2>

        <!-- Звёзды -->
        <div class="mb-6 flex justify-center gap-2">
          <button v-for="star in 5" :key="star" @click="reviewRating = star"
            class="transition-transform hover:scale-110">
            <svg class="h-10 w-10" :class="star <= reviewRating ? 'text-yellow-500' : 'text-gray-600'"
              fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z" />
            </svg>
          </button>
        </div>

        <!-- Теги -->
        <div class="mb-4">
          <p class="mb-2 text-sm text-gray-400">Что понравилось / не понравилось:</p>
          <div class="flex flex-wrap gap-2">
            <button v-for="tag in availableTags" :key="tag" @click="toggleTag(tag)" :class="[
              'rounded-full px-3 py-1 text-xs font-medium transition-all',
              reviewTags.includes(tag)
                ? 'bg-yellow-500 text-gray-900'
                : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
            ]">
              {{ tag }}
            </button>
          </div>
        </div>

        <!-- Комментарий -->
        <div class="mb-6">
          <label class="mb-2 block text-sm text-gray-400">Комментарий (необязательно)</label>
          <textarea v-model="reviewComment" rows="3"
            class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white text-sm placeholder-gray-500"
            placeholder="Напишите ваш комментарий..."></textarea>
        </div>

        <!-- Кнопки -->
        <div class="flex gap-3">
          <button @click="closeReviewModal"
            class="flex-1 rounded-lg border border-gray-600 py-3 text-gray-300 transition-all hover:bg-gray-700">
            Пропустить
          </button>
          <button @click="submitDriverReview" :disabled="isSubmittingReview"
            class="flex-1 rounded-lg bg-yellow-500 py-3 font-bold text-gray-900 transition-all hover:bg-yellow-600 disabled:opacity-50">
            {{ isSubmittingReview ? 'Отправка...' : 'Отправить' }}
          </button>
        </div>
      </div>
    </div>
  </MainLayout>
</template>
