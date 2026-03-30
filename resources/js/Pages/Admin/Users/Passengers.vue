<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  passengers: {
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

// Закрыть dropdown при клике вне
const closeDropdowns = (event) => {
  if (!event.target.closest('.dropdown-container')) {
    showDropdown.value = null
  }
}

onMounted(() => {
  document.addEventListener('click', closeDropdowns)
})

onUnmounted(() => {
  document.removeEventListener('click', closeDropdowns)
})

// Debounce поиск
let searchTimeout = null
watch(searchQuery, (value) => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    router.get(route('admin.users.passengers'), { 
      search: value, 
      status: statusFilter.value 
    }, { preserveState: true })
  }, 300)
})

watch(statusFilter, (value) => {
  router.get(route('admin.users.passengers'), { 
    search: searchQuery.value, 
    status: value 
  }, { preserveState: true })
})

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ru-RU')
}

const toggleBlock = (passenger) => {
  router.post(route('admin.users.toggleBlock'), {
    user_id: passenger.user.id,
    type: 'passenger'
  }, { preserveState: true })
  showDropdown.value = null
}

const deleteUser = (passenger) => {
  if (confirm('Вы уверены, что хотите удалить этого пользователя? Это действие необратимо.')) {
    router.delete(route('admin.users.destroy'), {
      data: {
        user_id: passenger.user.id,
        type: 'passenger'
      },
      preserveState: true
    })
  }
  showDropdown.value = null
}
</script>

<template>
  <AdminLayout activeTab="users">
    <!-- Page Header -->
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Пассажиры</h1>
        <p class="text-gray-400">Управление пассажирами системы</p>
      </div>
    </div>

    <!-- Search & Filter -->
    <div class="mb-4 flex gap-4">
      <div class="flex-1">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Поиск по имени, телефону, email..."
          class="w-full rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white placeholder-gray-500 focus:border-red-500 focus:outline-none"
        />
      </div>
      <select
        v-model="statusFilter"
        class="rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-white focus:border-red-500 focus:outline-none"
      >
        <option value="all">Все статусы</option>
        <option value="active">Активен</option>
        <option value="blocked">Заблокирован</option>
      </select>
    </div>

    <!-- Table -->
    <div class="overflow-visible rounded-xl border border-gray-700 bg-gray-800">
      <table class="w-full">
        <thead class="border-b border-gray-700 bg-gray-900">
          <tr>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-400">Пассажир</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-400">Контакты</th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-400">Поездок</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-gray-400">Потрачено</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-gray-400">Баланс</th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-400">Рейтинг</th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-400">Статус</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-gray-400">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
          <tr
            v-for="passenger in passengers"
            :key="passenger.id"
            class="transition-colors hover:bg-gray-700/50"
          >
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-700">
                  <span class="text-sm font-bold text-gray-400">{{ (passenger.user?.first_name || passenger.user?.last_name)?.charAt(0) || '?' }}</span>
                </div>
                <div>
                  <div class="font-medium text-white">
                    {{ [passenger.user?.first_name, passenger.user?.last_name].filter(Boolean).join(' ') || '-' }}
                  </div>
                  <div class="text-xs text-gray-500">С {{ formatDate(passenger.user?.created_at) }}</div>
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="text-white">{{ passenger.user?.phone || '-' }}</div>
              <div class="text-xs text-gray-500">{{ passenger.user?.email || '-' }}</div>
            </td>
            <td class="px-4 py-3 text-center text-gray-400">{{ passenger.orders_count || 0 }}</td>
            <td class="px-4 py-3 text-right text-white">{{ (passenger.total_spent || 0).toLocaleString() }} ₽</td>
            <td class="px-4 py-3 text-right">
              <span :class="(passenger.balance || 0) > 0 ? 'text-green-500' : 'text-gray-400'">
                {{ (passenger.balance || 0).toLocaleString() }} ₽
              </span>
            </td>
            <td class="px-4 py-3 text-center">
              <div class="flex items-center justify-center gap-1">
                <svg class="h-4 w-4 text-yellow-500" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
                <span class="text-white">{{ passenger.rating || '-' }}</span>
              </div>
            </td>
            <td class="px-4 py-3 text-center">
              <span :class="[
                'rounded px-2 py-0.5 text-xs font-medium uppercase',
                passenger.user?.is_active ? 'bg-green-600' : 'bg-red-600'
              ]">
                {{ passenger.user?.is_active ? 'Активен' : 'Заблокирован' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="relative dropdown-container overflow-visible">
                <button 
                  @click.stop="showDropdown = showDropdown === passenger.id ? null : passenger.id" 
                  class="rounded p-1 text-gray-400 hover:bg-gray-700 hover:text-white"
                >
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                  </svg>
                </button>
                
                <!-- Dropdown Menu -->
                <div 
                  v-if="showDropdown === passenger.id"
                  class="absolute right-0 top-full z-50 mt-1 w-48 rounded-lg border border-gray-600 bg-gray-800 shadow-xl"
                >
                  <button
                    @click="toggleBlock(passenger)"
                    class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-white hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    {{ passenger.user.is_active ? 'Заблокировать' : 'Разблокировать' }}
                  </button>
                  <button
                    @click="deleteUser(passenger)"
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
          <tr v-if="passengers.length === 0">
            <td colspan="8" class="px-4 py-8 text-center text-gray-500">
              Пассажиры не найдены
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="mt-4 flex items-center justify-between">
      <div class="text-sm text-gray-400">
        Показано {{ passengers.length }} из {{ pagination.total }}
      </div>
      <div class="flex gap-2">
        <button 
          v-for="page in pagination.last_page" 
          :key="page"
          @click="router.get(route('admin.users.passengers'), { page, search: searchQuery, status: statusFilter }, { preserveState: true })"
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
  </AdminLayout>
</template>
