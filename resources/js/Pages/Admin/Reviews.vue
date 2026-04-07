<script setup>
import { ref, computed, onMounted } from 'vue'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  reviews: Array,
  drivers: Array,
  passengers: Array,
  stats: Object,
})

// Фильтры
const filterType = ref('all') // all, driver, passenger
const searchQuery = ref('')
const ratingFilter = ref('all')

const filteredReviews = computed(() => {
  let result = props.reviews || []
  
  // Фильтр по типу
  if (filterType.value === 'passenger') {
    result = result.filter(r => r.passenger_rating !== null)
  } else if (filterType.value === 'driver') {
    result = result.filter(r => r.driver_rating !== null)
  }
  
  // Фильтр по рейтингу
  if (ratingFilter.value !== 'all') {
    const rating = parseInt(ratingFilter.value)
    result = result.filter(r => {
      // Взаимные отзывы - проверяем оба рейтинга
      if (r.passenger_rating && r.driver_rating) {
        const passengerMatch = r.passenger_rating === rating
        const driverMatch = r.driver_rating === rating
        
        if (filterType.value === 'passenger') {
          return passengerMatch
        } else if (filterType.value === 'driver') {
          return driverMatch
        } else {
          // Для "Все" - показываем если хотя бы один рейтинг совпадает
          return passengerMatch || driverMatch
        }
      }
      
      // Одиночные отзывы
      if (filterType.value === 'passenger') {
        return r.passenger_rating === rating
      } else if (filterType.value === 'driver') {
        return r.driver_rating === rating
      } else {
        return r.passenger_rating === rating || r.driver_rating === rating
      }
    })
  }
  
  // Поиск
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    result = result.filter(r => {
      const driverName = r.driver?.user?.first_name + ' ' + r.driver?.user?.last_name || ''
      const passengerName = r.passenger?.user?.first_name + ' ' + r.passenger?.user?.last_name || ''
      const comment = r.driver_comment || r.passenger_comment || ''
      return driverName.toLowerCase().includes(query) ||
             passengerName.toLowerCase().includes(query) ||
             comment.toLowerCase().includes(query)
    })
  }
  
  return result
})

const stats = computed(() => props.stats || {
  total: 0,
  avgRating: 0,
  fiveStars: 0,
  fourStars: 0,
  threeStars: 0,
  twoStars: 0,
  oneStar: 0,
})

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getRatingStars = (rating) => {
  if (!rating) return ''
  return '★'.repeat(rating) + '☆'.repeat(5 - rating)
}

const getRatingClass = (rating) => {
  if (rating >= 4) return 'text-green-400'
  if (rating >= 3) return 'text-yellow-400'
  if (rating >= 2) return 'text-orange-400'
  return 'text-red-400'
}
</script>

<template>
  <AdminLayout activeTab="reviews">
    <!-- Заголовок -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-white">Отзывы</h1>
      <p class="text-gray-400">Управление отзывами водителей и пассажиров</p>
    </div>

    <!-- Статистика -->
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">
      <div class="rounded-lg bg-gray-800 p-4">
        <div class="text-2xl font-bold text-white">{{ stats.total }}</div>
        <div class="text-sm text-gray-400">Всего отзывов</div>
      </div>
      <div class="rounded-lg bg-gray-800 p-4">
        <div class="text-2xl font-bold text-yellow-400">{{ stats.avgRating }}</div>
        <div class="text-sm text-gray-400">Средний рейтинг</div>
      </div>
      <div class="rounded-lg bg-green-500/20 p-4">
        <div class="text-2xl font-bold text-green-400">{{ stats.fiveStars }}</div>
        <div class="text-sm text-gray-400">5 звёзд</div>
      </div>
      <div class="rounded-lg bg-blue-500/20 p-4">
        <div class="text-2xl font-bold text-blue-400">{{ stats.fourStars }}</div>
        <div class="text-sm text-gray-400">4 звезды</div>
      </div>
      <div class="rounded-lg bg-yellow-500/20 p-4">
        <div class="text-2xl font-bold text-yellow-400">{{ stats.threeStars }}</div>
        <div class="text-sm text-gray-400">3 звезды</div>
      </div>
      <div class="rounded-lg bg-red-500/20 p-4">
        <div class="text-2xl font-bold text-red-400">{{ stats.oneStar + stats.twoStars }}</div>
        <div class="text-sm text-gray-400">1-2 звезды</div>
      </div>
    </div>

    <!-- Фильтры -->
    <div class="mb-4 flex flex-wrap gap-4">
      <div class="flex gap-2">
        <button
          @click="filterType = 'all'"
          :class="[
            'rounded-lg px-4 py-2 text-sm font-medium transition',
            filterType === 'all' ? 'bg-yellow-500 text-gray-900' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
          ]"
        >
          Все
        </button>
        <button
          @click="filterType = 'passenger'"
          :class="[
            'rounded-lg px-4 py-2 text-sm font-medium transition',
            filterType === 'passenger' ? 'bg-yellow-500 text-gray-900' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
          ]"
        >
          От пассажиров
        </button>
        <button
          @click="filterType = 'driver'"
          :class="[
            'rounded-lg px-4 py-2 text-sm font-medium transition',
            filterType === 'driver' ? 'bg-yellow-500 text-gray-900' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'
          ]"
        >
          От водителей
        </button>
      </div>
      
      <select
        v-model="ratingFilter"
        class="rounded-lg bg-gray-700 px-4 py-2 text-sm text-white"
      >
        <option value="all">Все рейтинги</option>
        <option value="5">5 звёзд</option>
        <option value="4">4 звезды</option>
        <option value="3">3 звезды</option>
        <option value="2">2 звезды</option>
        <option value="1">1 звезда</option>
      </select>

      <input
        v-model="searchQuery"
        type="text"
        placeholder="Поиск по имени или комментарию..."
        class="flex-1 rounded-lg bg-gray-700 px-4 py-2 text-sm text-white placeholder-gray-400"
      />
    </div>

    <!-- Список отзывов -->
    <div class="space-y-4">
      <div
        v-for="review in filteredReviews"
        :key="review.id"
        class="rounded-lg bg-gray-800 p-4"
      >
        <!-- Заказ и дата -->
        <div class="mb-3 flex items-center gap-3">
          <span class="text-sm text-gray-400">Заказ #{{ review.order_id }}</span>
          <span class="text-sm text-gray-500">{{ formatDate(review.created_at) }}</span>
        </div>

        <!-- Взаимные отзывы - разделены на две колонки -->
        <template v-if="review.passenger_rating && review.driver_rating">
          <!-- При фильтре "Все" - две колонки -->
          <div v-if="filterType === 'all'" class="grid grid-cols-2 gap-4">
            <!-- Левая колонка - отзыв пассажира -->
            <div class="rounded-lg bg-blue-500/10 p-4 border-l-4 border-l-blue-500">
              <div class="flex items-center gap-2 mb-2">
                <span class="text-sm text-gray-400">От пассажира:</span>
                <span class="font-medium text-white">
                  {{ review.passenger?.user?.first_name }} {{ review.passenger?.user?.last_name || 'Пассажир' }}
                </span>
              </div>
              <div class="mb-2">
                <span :class="['text-lg font-bold', getRatingClass(review.passenger_rating)]">
                  {{ getRatingStars(review.passenger_rating) }}
                  <span class="ml-2 text-sm text-gray-400">({{ review.passenger_rating }}/5)</span>
                </span>
              </div>
              <div v-if="review.passenger_comment" class="mt-2 rounded bg-gray-700/50 p-3">
                <div class="text-sm text-gray-400">Комментарий:</div>
                <div class="text-white">{{ review.passenger_comment }}</div>
              </div>
              <div v-if="review.passenger_tags" class="mt-2 flex flex-wrap gap-2">
                <span
                  v-for="tag in (typeof review.passenger_tags === 'string' ? JSON.parse(review.passenger_tags) : review.passenger_tags)"
                  :key="'p-' + tag"
                  class="rounded-full bg-blue-500/20 px-3 py-1 text-xs text-blue-400"
                >
                  {{ tag }}
                </span>
              </div>
            </div>

            <!-- Правая колонка - отзыв водителя -->
            <div class="rounded-lg bg-yellow-500/10 p-4 border-r-4 border-r-yellow-500">
              <div class="flex items-center gap-2 mb-2 justify-end">
                <span class="text-sm text-gray-400">От водителя:</span>
                <span class="font-medium text-white">
                  {{ review.driver?.user?.first_name }} {{ review.driver?.user?.last_name || 'Водитель' }}
                </span>
              </div>
              <div class="mb-2 flex justify-end">
                <span :class="['text-lg font-bold', getRatingClass(review.driver_rating)]">
                  {{ getRatingStars(review.driver_rating) }}
                  <span class="ml-2 text-sm text-gray-400">({{ review.driver_rating }}/5)</span>
                </span>
              </div>
              <div v-if="review.driver_comment" class="mt-2 rounded bg-gray-700/50 p-3">
                <div class="text-sm text-gray-400 text-right">Комментарий:</div>
                <div class="text-white text-right">{{ review.driver_comment }}</div>
              </div>
              <div v-if="review.driver_tags" class="mt-2 flex flex-wrap gap-2 justify-end">
                <span
                  v-for="tag in (typeof review.driver_tags === 'string' ? JSON.parse(review.driver_tags) : review.driver_tags)"
                  :key="'d-' + tag"
                  class="rounded-full bg-yellow-500/20 px-3 py-1 text-xs text-yellow-400"
                >
                  {{ tag }}
                </span>
              </div>
            </div>
          </div>

          <!-- При фильтре "Пассажир" - только отзыв пассажира на всю ширину слева -->
          <div v-else-if="filterType === 'passenger'" class="flex">
            <div class="rounded-lg bg-blue-500/10 p-4 border-l-4 border-l-blue-500 w-full md:w-1/2">
              <div class="flex items-center gap-2 mb-2">
                <span class="text-sm text-gray-400">От пассажира:</span>
                <span class="font-medium text-white">
                  {{ review.passenger?.user?.first_name }} {{ review.passenger?.user?.last_name || 'Пассажир' }}
                </span>
              </div>
              <div class="mb-2">
                <span :class="['text-lg font-bold', getRatingClass(review.passenger_rating)]">
                  {{ getRatingStars(review.passenger_rating) }}
                  <span class="ml-2 text-sm text-gray-400">({{ review.passenger_rating }}/5)</span>
                </span>
              </div>
              <div v-if="review.passenger_comment" class="mt-2 rounded bg-gray-700/50 p-3">
                <div class="text-sm text-gray-400">Комментарий:</div>
                <div class="text-white">{{ review.passenger_comment }}</div>
              </div>
              <div v-if="review.passenger_tags" class="mt-2 flex flex-wrap gap-2">
                <span
                  v-for="tag in (typeof review.passenger_tags === 'string' ? JSON.parse(review.passenger_tags) : review.passenger_tags)"
                  :key="'p-' + tag"
                  class="rounded-full bg-blue-500/20 px-3 py-1 text-xs text-blue-400"
                >
                  {{ tag }}
                </span>
              </div>
            </div>
          </div>

          <!-- При фильтре "Водитель" - только отзыв водителя на всю ширину справа -->
          <div v-else class="flex justify-end">
            <div class="rounded-lg bg-yellow-500/10 p-4 border-r-4 border-r-yellow-500 w-full md:w-1/2">
              <div class="flex items-center gap-2 mb-2 justify-end">
                <span class="text-sm text-gray-400">От водителя:</span>
                <span class="font-medium text-white">
                  {{ review.driver?.user?.first_name }} {{ review.driver?.user?.last_name || 'Водитель' }}
                </span>
              </div>
              <div class="mb-2 flex justify-end">
                <span :class="['text-lg font-bold', getRatingClass(review.driver_rating)]">
                  {{ getRatingStars(review.driver_rating) }}
                  <span class="ml-2 text-sm text-gray-400">({{ review.driver_rating }}/5)</span>
                </span>
              </div>
              <div v-if="review.driver_comment" class="mt-2 rounded bg-gray-700/50 p-3">
                <div class="text-sm text-gray-400 text-right">Комментарий:</div>
                <div class="text-white text-right">{{ review.driver_comment }}</div>
              </div>
              <div v-if="review.driver_tags" class="mt-2 flex flex-wrap gap-2 justify-end">
                <span
                  v-for="tag in (typeof review.driver_tags === 'string' ? JSON.parse(review.driver_tags) : review.driver_tags)"
                  :key="'d-' + tag"
                  class="rounded-full bg-yellow-500/20 px-3 py-1 text-xs text-yellow-400"
                >
                  {{ tag }}
                </span>
              </div>
            </div>
          </div>
        </template>

        <!-- Только отзыв пассажира -->
        <template v-else-if="review.passenger_rating && (filterType === 'all' || filterType === 'passenger')">
          <div class="rounded-lg bg-blue-500/10 p-4 border-l-4 border-l-blue-500" :class="filterType === 'passenger' ? 'mr-auto' : ''">
            <div class="flex items-center gap-4 mb-2">
              <div class="flex items-center gap-2">
                <span class="text-sm text-gray-400">От пассажира:</span>
                <span class="font-medium text-white">
                  {{ review.passenger?.user?.first_name }} {{ review.passenger?.user?.last_name || 'Пассажир' }}
                </span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-sm text-gray-400">Водителю:</span>
                <span class="font-medium text-white">
                  {{ review.driver?.user?.first_name }} {{ review.driver?.user?.last_name || 'Водитель' }}
                </span>
              </div>
            </div>
            <div class="mb-2">
              <span :class="['text-lg font-bold', getRatingClass(review.passenger_rating)]">
                {{ getRatingStars(review.passenger_rating) }}
                <span class="ml-2 text-sm text-gray-400">(оценка пассажира: {{ review.passenger_rating }}/5)</span>
              </span>
            </div>
            <div v-if="review.passenger_comment" class="mt-2 rounded bg-gray-700 p-3">
              <div class="text-sm text-gray-400">Комментарий пассажира:</div>
              <div class="text-white">{{ review.passenger_comment }}</div>
            </div>
            <div v-if="review.passenger_tags" class="mt-2 flex flex-wrap gap-2">
              <span
                v-for="tag in (typeof review.passenger_tags === 'string' ? JSON.parse(review.passenger_tags) : review.passenger_tags)"
                :key="'p-' + tag"
                class="rounded-full bg-blue-500/20 px-3 py-1 text-xs text-blue-400"
              >
                {{ tag }}
              </span>
            </div>
          </div>
        </template>

        <!-- Только отзыв водителя -->
        <template v-else-if="review.driver_rating && (filterType === 'all' || filterType === 'driver')">
          <div class="rounded-lg bg-yellow-500/10 p-4 border-r-4 border-r-yellow-500" :class="filterType === 'driver' ? 'ml-auto' : ''">
            <div class="flex items-center gap-4 mb-2 justify-end">
              <div class="flex items-center gap-2">
                <span class="text-sm text-gray-400">От водителя:</span>
                <span class="font-medium text-white">
                  {{ review.driver?.user?.first_name }} {{ review.driver?.user?.last_name || 'Водитель' }}
                </span>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-sm text-gray-400">Пассажиру:</span>
                <span class="font-medium text-white">
                  {{ review.passenger?.user?.first_name }} {{ review.passenger?.user?.last_name || 'Пассажир' }}
                </span>
              </div>
            </div>
            <div class="mb-2 flex justify-end">
              <span :class="['text-lg font-bold', getRatingClass(review.driver_rating)]">
                {{ getRatingStars(review.driver_rating) }}
                <span class="ml-2 text-sm text-gray-400">(оценка водителя: {{ review.driver_rating }}/5)</span>
              </span>
            </div>
            <div v-if="review.driver_comment" class="mt-2 rounded bg-gray-700 p-3">
              <div class="text-sm text-gray-400 text-right">Комментарий водителя:</div>
              <div class="text-white text-right">{{ review.driver_comment }}</div>
            </div>
            <div v-if="review.driver_tags" class="mt-2 flex flex-wrap gap-2 justify-end">
              <span
                v-for="tag in (typeof review.driver_tags === 'string' ? JSON.parse(review.driver_tags) : review.driver_tags)"
                :key="'d-' + tag"
                class="rounded-full bg-yellow-500/20 px-3 py-1 text-xs text-yellow-400"
              >
                {{ tag }}
              </span>
            </div>
          </div>
        </template>
      </div>

      <div v-if="filteredReviews.length === 0" class="py-8 text-center text-gray-400">
        Отзывы не найдены
      </div>
    </div>
  </AdminLayout>
</template>
