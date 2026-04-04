<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
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
  }
})

const form = ref({
  from: '',
  to: '',
  tariff_id: '',
  distance: 12,
  duration: 20,
  notes: ''
})

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
const lastCompletedOrderId = ref(null) // ✅ Исправлено: теперь это ref
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

// ✅ Проверка, оставлен ли отзыв на заказ
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

// ✅ Загрузка истории заказов
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

// ✅ Переключение страницы
const changePage = (page) => {
  if (page >= 1 && page <= pagination.value.last_page) {
    loadOrderHistory(page)
  }
}

// ✅ Скрыть заказ из истории
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
    console.log('Hide order response:', data)

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

// ✅ Повторить заказ из истории
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

// ✅ Форматирование даты
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

// ✅ AudioContext создаётся один раз при монтировании
let audioContext = null

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
      console.log('=== ORDER CREATED SUCCESS ===')
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
      console.log('Polling started!')
    },
    onError: (errors) => {
      alert('Ошибка: ' + Object.values(errors).join(', '))
      isSubmitting.value = false
    }
  })
}

// ✅ Получение CSRF-токена (сначала из cookie, потом из meta)
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

// ✅ Отправка отзыва с правильным заголовком
const submitReview = async () => {
  if (!lastCompletedOrderId.value) {
    alert('ID заказа не найден')
    return
  }
  
  isSubmittingReview.value = true
  
  console.log('Отправляем отзыв для заказа:', lastCompletedOrderId.value)
  console.log('CSRF Token:', getCsrfToken())
  
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
    console.log('Ответ отзыва:', { status: response.status, data })
    
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

// ✅ Исправленная функция воспроизведения звука
const playArrivedSound = () => {
  console.log('Playing arrived sound...')
  
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

    console.log('=== ORDER STATUS CHECK ===')
    console.log('has_active_order:', data.has_active_order)
    console.log('order:', data.order)
    console.log('previousStatus:', previousStatus.value)
    console.log('=========================')

    if (data.has_active_order) {
      // ✅ Проверяем изменение статуса на "arrived"
      if (previousStatus.value && previousStatus.value !== 'arrived' && data.order.status === 'arrived') {
        console.log('Driver arrived! Playing sound...')
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

// ✅ Закрытие модального окна отзыва
const closeReviewModal = () => {
  showReviewModal.value = false
  reviewRating.value = 5
  reviewComment.value = ''
  reviewTags.value = []
}

// ✅ Инициализация при монтировании
onMounted(() => {
  console.log('=== PASSENGER HOME MOUNTED ===')
  
  // ✅ Создаём AudioContext при первом взаимодействии пользователя
  const initAudio = () => {
    const AudioContext = window.AudioContext || window.webkitAudioContext
    if (AudioContext && !audioContext) {
      audioContext = new AudioContext()
      console.log('AudioContext initialized')
    }
    // Убираем обработчики после первого клика
    document.removeEventListener('click', initAudio)
    document.removeEventListener('touchstart', initAudio)
  }
  
  document.addEventListener('click', initAudio, { once: true })
  document.addEventListener('touchstart', initAudio, { once: true })
  
  fetchOrderStatus()
  startPolling()
  loadOrderHistory() // Загружаем историю заказов
  console.log('Polling interval:', pollingInterval)
})

onUnmounted(() => {
  stopPolling()
  // ✅ Очищаем AudioContext при размонтировании
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
                v-model="form.from"
                type="text"
                placeholder="Откуда"
                class="w-full rounded-lg border-0 bg-gray-800 py-3 pl-12 pr-4 text-white placeholder-gray-500 focus:ring-2 focus:ring-yellow-500"
              />
            </div>
          </div>

          <div class="mb-4">
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <div class="h-3 w-3 rounded-full bg-orange-500"></div>
              </div>
              <input
                v-model="form.to"
                type="text"
                placeholder="Куда"
                class="w-full rounded-lg border-0 bg-gray-800 py-3 pl-12 pr-4 text-white placeholder-gray-500 focus:ring-2 focus:ring-yellow-500"
              />
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
                      <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
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
  </MainLayout>
</template>