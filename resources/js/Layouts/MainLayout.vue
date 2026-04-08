<script setup>
import { ref, computed } from 'vue'
import { Link, usePage, router } from '@inertiajs/vue3'

const props = defineProps({
  activeRole: {
    type: String,
    default: 'passenger'
  }
})

const emit = defineEmits(['role-change'])

const page = usePage()

// Данные профиля из page.props
const user = computed(() => page.props?.passenger?.user || page.props?.driver?.user || {})

// Состояние popup профиля
const showProfileModal = ref(false)
const isLoading = ref(false)
const isSaving = ref(false)
const error = ref('')
const success = ref('')

// Форма профиля
const profileForm = ref({
  first_name: '',
  last_name: '',
  phone: '',
  email: '',
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

// Открыть popup профиля
const openProfileModal = () => {
  profileForm.value = {
    first_name: user.value.first_name || '',
    last_name: user.value.last_name || '',
    phone: user.value.phone || '',
    email: user.value.email || '',
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
  }
  error.value = ''
  success.value = ''
  showProfileModal.value = true
}

// Закрыть popup
const closeProfileModal = () => {
  showProfileModal.value = false
}

// Получить CSRF токен
const getCsrfToken = () => {
  const cookieToken = document.cookie
    .split('; ')
    .find(row => row.startsWith('XSRF-TOKEN='))
    ?.split('=')[1]
  if (cookieToken) return decodeURIComponent(cookieToken)
  return document.querySelector('meta[name="csrf-token"]')?.content || ''
}

// Сохранить профиль
const saveProfile = async () => {
  error.value = ''
  success.value = ''
  isSaving.value = true

  try {
    const response = await fetch('/api/passenger/profile', {
      method: 'PUT',
      credentials: 'include',
      headers: {
        'X-XSRF-TOKEN': getCsrfToken(),
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        first_name: profileForm.value.first_name,
        last_name: profileForm.value.last_name,
        phone: profileForm.value.phone,
        email: profileForm.value.email,
        current_password: profileForm.value.current_password || null,
        new_password: profileForm.value.new_password || null,
        new_password_confirmation: profileForm.value.new_password_confirmation || null
      })
    })

    const data = await response.json()

    if (response.ok && data.success) {
      success.value = 'Данные сохранены!'
      setTimeout(() => {
        closeProfileModal()
        // Перезагрузить страницу для обновления данных
        router.reload({ only: ['passenger', 'driver'] })
      }, 1000)
    } else {
      error.value = data.error || data.message || 'Ошибка сохранения'
    }
  } catch (e) {
    error.value = 'Ошибка соединения'
  } finally {
    isSaving.value = false
  }
}

const currentDate = computed(() => {
  const now = new Date()
  const months = ['ЯНВАРЯ', 'ФЕВРАЛЯ', 'МАРТА', 'АПРЕЛЯ', 'МАЯ', 'ИЮНЯ', 'ИЮЛЯ', 'АВГУСТА', 'СЕНТЯБРЯ', 'ОКТЯБРЯ', 'НОЯБРЯ', 'ДЕКАБРЯ']
  return `${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`
})

const switchRole = (role) => {
  emit('role-change', role)
  if (role === 'passenger') {
    window.location.href = '/passenger'
  } else if (role === 'driver') {
    window.location.href = '/driver'
  } else if (role === 'dispatcher') {
    window.location.href = '/dispatcher'
  }
}

const logout = () => {
  router.post(route('logout'))
}
</script>

<template>
  <div class="min-h-screen bg-[#0F172A]">
    <!-- Header -->
    <header class="bg-[#1F2937] px-4 py-3">
      <div class="mx-auto flex max-w-md items-center justify-between">
        <!-- Logo -->
        <div class="flex items-center gap-2">
          <div class="flex h-10 w-10 items-center justify-center rounded-full bg-yellow-500">
            <svg class="h-6 w-6 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
              <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
            </svg>
          </div>
          <div>
            <div class="text-lg font-bold text-white">TaxiVezi</div>
            <div class="text-xs text-gray-400">Диспетчерская система</div>
          </div>
        </div>

        <!-- Right Section -->
        <div class="flex items-center gap-2">
          <div class="text-right">
            <div class="text-sm font-medium text-gray-300">{{ currentDate }}</div>
            <div class="flex items-center justify-end gap-1">
              <span class="h-2 w-2 rounded-full bg-green-500"></span>
              <span class="text-xs text-green-500">Онлайн</span>
            </div>
          </div>
          
          <!-- Кнопка профиля -->
          <button
            @click="openProfileModal"
            class="flex items-center gap-1 rounded-lg bg-gray-700 px-3 py-2 text-sm text-gray-300 hover:bg-yellow-500 hover:text-gray-900 transition"
            title="Профиль"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
          </button>
          
          <!-- Кнопка выхода -->
          <button
            @click="logout"
            class="flex items-center gap-1 rounded-lg bg-gray-700 px-3 py-2 text-sm text-gray-300 hover:bg-red-600 hover:text-white transition"
            title="Выйти"
          >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
            </svg>
          </button>
        </div>
      </div>

      <!-- Role Switcher -->
      <div class="mx-auto mt-3 flex max-w-md gap-2">
        <button
          @click="switchRole('passenger')"
          :class="[
            'flex flex-1 items-center justify-center gap-2 rounded-lg py-2 text-sm font-medium transition-all',
            activeRole === 'passenger'
              ? 'bg-yellow-500 text-gray-900'
              : 'bg-gray-700 text-gray-400 hover:bg-gray-600'
          ]"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
          </svg>
          Пассажир
        </button>
        <button
          @click="switchRole('driver')"
          :class="[
            'flex flex-1 items-center justify-center gap-2 rounded-lg py-2 text-sm font-medium transition-all',
            activeRole === 'driver'
              ? 'bg-yellow-500 text-gray-900'
              : 'bg-gray-700 text-gray-400 hover:bg-gray-600'
          ]"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
          </svg>
          Водитель
        </button>
        <button
          @click="switchRole('dispatcher')"
          :class="[
            'flex flex-1 items-center justify-center gap-2 rounded-lg py-2 text-sm font-medium transition-all',
            activeRole === 'dispatcher'
              ? 'bg-yellow-500 text-gray-900'
              : 'bg-gray-700 text-gray-400 hover:bg-gray-600'
          ]"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
          </svg>
          Диспетчер
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <main class="px-4 py-6">
      <slot />
    </main>

    <!-- Popup профиля -->
    <div v-if="showProfileModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4">
      <div class="w-full max-w-md rounded-2xl bg-gray-800 p-6 shadow-2xl">
        <div class="mb-4 flex items-center justify-between">
          <h2 class="text-xl font-bold text-white">Профиль</h2>
          <button @click="closeProfileModal" class="text-gray-400 hover:text-white">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Сообщения -->
        <div v-if="error" class="mb-4 rounded-lg bg-red-500/20 p-3 text-sm text-red-400">
          {{ error }}
        </div>
        <div v-if="success" class="mb-4 rounded-lg bg-green-500/20 p-3 text-sm text-green-400">
          {{ success }}
        </div>

        <form @submit.prevent="saveProfile" class="space-y-4">
          <div>
            <label class="mb-1 block text-sm text-gray-400">Имя</label>
            <input
              v-model="profileForm.first_name"
              type="text"
              required
              class="w-full rounded-lg border-0 bg-gray-700 p-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-yellow-500"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm text-gray-400">Фамилия</label>
            <input
              v-model="profileForm.last_name"
              type="text"
              class="w-full rounded-lg border-0 bg-gray-700 p-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-yellow-500"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm text-gray-400">Телефон</label>
            <input
              v-model="profileForm.phone"
              type="tel"
              class="w-full rounded-lg border-0 bg-gray-700 p-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-yellow-500"
            />
          </div>

          <div>
            <label class="mb-1 block text-sm text-gray-400">Email</label>
            <input
              v-model="profileForm.email"
              type="email"
              required
              class="w-full rounded-lg border-0 bg-gray-700 p-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-yellow-500"
            />
          </div>

          <div class="border-t border-gray-700 pt-4">
            <p class="mb-3 text-sm text-gray-400">Изменить пароль (необязательно)</p>
            
            <div class="space-y-3">
              <div>
                <label class="mb-1 block text-sm text-gray-400">Текущий пароль</label>
                <input
                  v-model="profileForm.current_password"
                  type="password"
                  class="w-full rounded-lg border-0 bg-gray-700 p-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-yellow-500"
                />
              </div>

              <div>
                <label class="mb-1 block text-sm text-gray-400">Новый пароль</label>
                <input
                  v-model="profileForm.new_password"
                  type="password"
                  class="w-full rounded-lg border-0 bg-gray-700 p-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-yellow-500"
                />
              </div>

              <div>
                <label class="mb-1 block text-sm text-gray-400">Подтвердите пароль</label>
                <input
                  v-model="profileForm.new_password_confirmation"
                  type="password"
                  class="w-full rounded-lg border-0 bg-gray-700 p-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-yellow-500"
                />
              </div>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button
              type="button"
              @click="closeProfileModal"
              class="flex-1 rounded-lg bg-gray-700 py-3 font-semibold text-white transition-colors hover:bg-gray-600"
            >
              Отмена
            </button>
            <button
              type="submit"
              :disabled="isSaving"
              class="flex-1 rounded-lg bg-yellow-500 py-3 font-semibold text-gray-900 transition-colors hover:bg-yellow-600 disabled:cursor-not-allowed disabled:opacity-50"
            >
              {{ isSaving ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
