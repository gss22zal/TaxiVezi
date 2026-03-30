<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  dispatchers: {
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
    router.get(route('admin.users.dispatchers'), { 
      search: value, 
      status: statusFilter.value 
    }, { preserveState: true })
  }, 300)
})

watch(statusFilter, (value) => {
  router.get(route('admin.users.dispatchers'), { 
    search: searchQuery.value, 
    status: value 
  }, { preserveState: true })
})

const getRoleName = (roleCode) => {
  const roles = {
    'super_admin': 'Супер-админ',
    'admin': 'Администратор',
    'dispatcher': 'Диспетчер'
  }
  return roles[roleCode] || roleCode || '-'
}

const formatDate = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleDateString('ru-RU')
}

const formatDateTime = (dateString) => {
  if (!dateString) return '-'
  return new Date(dateString).toLocaleString('ru-RU')
}

const toggleBlock = (dispatcher) => {
  router.post(route('admin.users.toggleBlock'), {
    user_id: dispatcher.id,
    type: 'dispatcher'
  }, { preserveState: true })
  showDropdown.value = null
}

const deleteUser = (dispatcher) => {
  if (confirm('Вы уверены, что хотите удалить этого пользователя? Это действие необратимо.')) {
    router.delete(route('admin.users.destroy'), {
      data: {
        user_id: dispatcher.id,
        type: 'dispatcher'
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
        <h1 class="text-2xl font-bold text-white">Диспетчеры</h1>
        <p class="text-gray-400">Управление диспетчерами системы</p>
      </div>
      <button class="rounded-lg bg-red-500 px-4 py-2 font-medium text-white transition-all hover:bg-red-600">
        + Добавить диспетчера
      </button>
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
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-400">Диспетчер</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-400">Контакты</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-400">Роль</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-400">Верификация</th>
            <th class="px-4 py-3 text-left text-sm font-medium text-gray-400">Создан</th>
            <th class="px-4 py-3 text-center text-sm font-medium text-gray-400">Статус</th>
            <th class="px-4 py-3 text-right text-sm font-medium text-gray-400">Действия</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
          <tr
            v-for="dispatcher in dispatchers"
            :key="dispatcher.id"
            class="transition-colors hover:bg-gray-700/50"
          >
            <td class="px-4 py-3">
              <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-500/20">
                  <span class="text-sm font-bold text-red-500">{{ (dispatcher.first_name || dispatcher.last_name)?.charAt(0) || '?' }}</span>
                </div>
                <div class="font-medium text-white">
                  {{ [dispatcher.first_name, dispatcher.last_name].filter(Boolean).join(' ') || '-' }}
                </div>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="text-white">{{ dispatcher.phone }}</div>
              <div class="text-xs text-gray-500">{{ dispatcher.email }}</div>
            </td>
            <td class="px-4 py-3">
              <span class="rounded bg-gray-700 px-2 py-1 text-sm text-gray-300">
                {{ getRoleName(dispatcher.role?.code || dispatcher.role) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <div class="flex gap-2">
                <span v-if="dispatcher.email_verified_at" class="rounded bg-green-600/20 px-2 py-0.5 text-xs text-green-500">✓ Email</span>
                <span v-else class="rounded bg-red-600/20 px-2 py-0.5 text-xs text-red-500">✗ Email</span>
                <span v-if="dispatcher.phone_verified_at" class="rounded bg-green-600/20 px-2 py-0.5 text-xs text-green-500">✓ Phone</span>
                <span v-else class="rounded bg-red-600/20 px-2 py-0.5 text-xs text-red-500">✗ Phone</span>
              </div>
            </td>
            <td class="px-4 py-3 text-gray-400">{{ formatDate(dispatcher.created_at) }}</td>
            <td class="px-4 py-3 text-center">
              <span :class="[
                'rounded px-2 py-0.5 text-xs font-medium uppercase',
                dispatcher.is_active ? 'bg-green-600' : 'bg-gray-600'
              ]">
                {{ dispatcher.is_active ? 'Активен' : 'Неактивен' }}
              </span>
            </td>
            <td class="px-4 py-3 text-right">
              <div class="relative dropdown-container overflow-visible">
                <button 
                  @click.stop="showDropdown = showDropdown === dispatcher.id ? null : dispatcher.id" 
                  class="rounded p-1 text-gray-400 hover:bg-gray-700 hover:text-white"
                >
                  <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 8c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/>
                  </svg>
                </button>
                
                <!-- Dropdown Menu -->
                <div 
                  v-if="showDropdown === dispatcher.id"
                  class="absolute right-0 top-full z-50 mt-1 w-48 rounded-lg border border-gray-600 bg-gray-800 shadow-xl"
                >
                  <button
                    @click="toggleBlock(dispatcher)"
                    class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-white hover:bg-gray-700"
                  >
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    {{ dispatcher.is_active ? 'Заблокировать' : 'Разблокировать' }}
                  </button>
                  <button
                    @click="deleteUser(dispatcher)"
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
          <tr v-if="dispatchers.length === 0">
            <td colspan="7" class="px-4 py-8 text-center text-gray-500">
              Диспетчеры не найдены
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="mt-4 flex items-center justify-between">
      <div class="text-sm text-gray-400">
        Показано {{ dispatchers.length }} из {{ pagination.total }}
      </div>
      <div class="flex gap-2">
        <button 
          v-for="page in pagination.last_page" 
          :key="page"
          @click="router.get(route('admin.users.dispatchers'), { page, search: searchQuery, status: statusFilter }, { preserveState: true })"
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
