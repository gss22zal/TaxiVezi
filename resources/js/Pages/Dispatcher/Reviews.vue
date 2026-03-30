<script setup>
import { ref, computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import DispatcherLayout from '@/Layouts/DispatcherLayout.vue'

const page = usePage()

const props = defineProps({
  reviews: Array,
  stats: Object,
})

// Фильтры
const filterType = ref('all')
const searchQuery = ref('')
const ratingFilter = ref('all')

const filteredReviews = computed(() => {
  let result = props.reviews || []
  
  if (filterType.value === 'passenger') {
    result = result.filter(r => r.passenger_rating !== null)
  } else if (filterType.value === 'driver') {
    result = result.filter(r => r.driver_rating !== null)
  }
  
  if (ratingFilter.value !== 'all') {
    const rating = parseInt(ratingFilter.value)
    result = result.filter(r => {
      const ratingValue = r.driver_rating || r.passenger_rating
      return ratingValue === rating
    })
  }
  
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
})

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
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
  <DispatcherLayout activeTab="reviews">
    <!-- Заголовок -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-white">Отзывы</h1>
      <p class="text-gray-400">Просмотр отзывов о водителях</p>
    </div>

    <!-- Статистика -->
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
      <div class="rounded-lg bg-gray-800 p-4">
        <div class="text-2xl font-bold text-white">{{ stats.total }}</div>
        <div class="text-sm text-gray-400">Всего отзывов</div>
      </div>
      <div class="rounded-lg bg-gray-800 p-4">
        <div class="text-2xl font-bold text-yellow-400">{{ stats.avgRating }}</div>
        <div class="text-sm text-gray-400">Средний рейтинг</div>
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
        placeholder="Поиск..."
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
        <div class="flex items-start justify-between">
          <div class="flex-1">
            <div class="mb-2 flex items-center gap-3">
              <span class="text-sm text-gray-400">Заказ #{{ review.order_id }}</span>
              <span class="text-sm text-gray-500">{{ formatDate(review.created_at) }}</span>
            </div>

            <div class="mb-2 flex items-center gap-4">
              <div>
                <span class="text-sm text-gray-400">Пассажир:</span>
                <span class="ml-2 font-medium text-white">
                  {{ review.passenger?.user?.first_name }} {{ review.passenger?.user?.last_name || 'Пассажир' }}
                </span>
              </div>
              <div>
                <span class="text-sm text-gray-400">Водитель:</span>
                <span class="ml-2 font-medium text-white">
                  {{ review.driver?.user?.first_name }} {{ review.driver?.user?.last_name || 'Водитель' }}
                </span>
              </div>
            </div>

            <div v-if="review.passenger_rating" :class="['text-lg font-bold', getRatingClass(review.passenger_rating)]">
              {{ getRatingStars(review.passenger_rating) }}
              <span class="ml-2 text-sm text-gray-400">({{ review.passenger_rating }}/5)</span>
            </div>

            <div v-if="review.passenger_comment" class="mt-2 rounded bg-gray-700 p-3 text-white">
              {{ review.passenger_comment }}
            </div>

            <div v-if="review.passenger_tags" class="mt-2 flex flex-wrap gap-2">
              <span
                v-for="tag in (typeof review.passenger_tags === 'string' ? JSON.parse(review.passenger_tags) : review.passenger_tags)"
                :key="tag"
                class="rounded-full bg-yellow-500/20 px-3 py-1 text-xs text-yellow-400"
              >
                {{ tag }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="filteredReviews.length === 0" class="py-8 text-center text-gray-400">
        Отзывы не найдены
      </div>
    </div>
  </DispatcherLayout>
</template>
