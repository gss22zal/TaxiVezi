import { ref, onMounted, onUnmounted, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function useOrderNotifications(options = {}) {
  const page = usePage()
  
  // Получаем настройку интервала из админки
  const settings = computed(() => page.props.settings?.api || {})
  const defaultInterval = computed(() => settings.value.order_stats_repeat_time || 30000)
  const driverInterval = computed(() => settings.value.driver_repeat_time || 3000)
  
  const {
    pollInterval = defaultInterval.value,
    onNewOrder = null
  } = options

  const previousStats = ref(null)
  const previousDriverStats = ref(null)
  const isEnabled = ref(true)
  let pollTimer = null
  let driverPollTimer = null

  // Звук оповещения
  const playNotificationSound = () => {
    try {
      // Создаем AudioContext для воспроизведения
      const audioContext = new (window.AudioContext || window.webkitAudioContext)()
      
      // Частота и длительность для приятного звука
      const frequencies = [800, 1000, 1200]
      const duration = 0.15
      
      frequencies.forEach((freq, index) => {
        setTimeout(() => {
          const oscillator = audioContext.createOscillator()
          const gainNode = audioContext.createGain()
          
          oscillator.connect(gainNode)
          gainNode.connect(audioContext.destination)
          
          oscillator.frequency.value = freq
          oscillator.type = 'sine'
          
          gainNode.gain.setValueAtTime(0.3, audioContext.currentTime)
          gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + duration)
          
          oscillator.start(audioContext.currentTime)
          oscillator.stop(audioContext.currentTime + duration)
        }, index * 200)
      })
    } catch (e) {
      console.warn('Audio not supported:', e)
    }
  }

  // Проверка изменений статистики
  const checkStats = async () => {
    if (!isEnabled.value) return
    
    try {
      const response = await fetch('/api/order-stats', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        }
      })
      
      if (!response.ok) return
      
      const newStats = await response.json()
      
      // Проверяем - есть ли новые заказы
      if (previousStats.value) {
        const newCount = newStats.new || 0
        const prevCount = previousStats.value.new || 0
        
        if (newCount > prevCount) {
          // Новый заказ!
          playNotificationSound()
          
          if (onNewOrder) {
            onNewOrder(newStats)
          }
        }
      }
      
      // Обновляем предыдущее значение
      previousStats.value = newStats
      
      // Обновляем глобально через Inertia
      page.props.orderStats = newStats
      
    } catch (e) {
      console.warn('Poll error:', e)
    }
  }

  // Проверка статусов водителей
  const checkDriverStats = async () => {
    if (!isEnabled.value) return

    try {
      const response = await fetch('/api/driver-statuses', {
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
        }
      })

      if (!response.ok) return

      const data = await response.json()
      const drivers = data.drivers || []

      // Подсчитываем статистику
      const stats = {
        total: drivers.length,
        online: drivers.filter(d => d.is_online).length,
        busy: drivers.filter(d => d.is_busy).length,
        free: drivers.filter(d => d.is_online && !d.is_busy).length,
      }

      // Обновляем глобально через Inertia
      page.props.driverStats = stats

    } catch (e) {
      console.warn('Driver poll error:', e)
    }
  }

  const startPolling = () => {
    isEnabled.value = true
    // Инициализируем предыдущие значения
    previousStats.value = { ...page.props.orderStats }
    // Запускаем polling для заказов (только для отслеживания НОВЫХ заказов)
    // Не делаем немедленный запрос - данные уже переданы через SSR
    pollTimer = setInterval(checkStats, pollInterval)
    // Запускаем polling для водителей (более частый)
    driverPollTimer = setInterval(checkDriverStats, driverInterval.value)
    // Не получаем данные сразу - используем серверные данные
  }

  const stopPolling = () => {
    isEnabled.value = false
    if (pollTimer) {
      clearInterval(pollTimer)
      pollTimer = null
    }
    if (driverPollTimer) {
      clearInterval(driverPollTimer)
      driverPollTimer = null
    }
  }

  onMounted(() => {
    startPolling()
  })

  onUnmounted(() => {
    stopPolling()
  })

  return {
    playNotificationSound,
    startPolling,
    stopPolling
  }
}
