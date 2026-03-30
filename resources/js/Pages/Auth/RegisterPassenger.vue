<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

const showPassword = ref(false)

const form = useForm({
  first_name: '',
  last_name: '',
  phone: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('register.passenger'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <GuestLayout>
    <div class="mb-6 text-center">
      <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-yellow-500">
        <svg class="h-8 w-8 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white">Регистрация пассажира</h1>
      <p class="mt-1 text-gray-400">Создайте аккаунт для заказа такси</p>
    </div>

    <form @submit.prevent="submit" class="space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="mb-1 block text-sm text-gray-400">Имя</label>
          <input
            v-model="form.first_name"
            type="text"
            required
            class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
            placeholder="Иван"
          />
          <span v-if="form.errors.first_name" class="text-sm text-red-500">{{ form.errors.first_name }}</span>
        </div>
        <div>
          <label class="mb-1 block text-sm text-gray-400">Фамилия</label>
          <input
            v-model="form.last_name"
            type="text"
            required
            class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
            placeholder="Петров"
          />
          <span v-if="form.errors.last_name" class="text-sm text-red-500">{{ form.errors.last_name }}</span>
        </div>
      </div>

      <div>
        <label class="mb-1 block text-sm text-gray-400">Телефон</label>
        <input
          v-model="form.phone"
          type="tel"
          required
          class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
          placeholder="+7 (999) 123-45-67"
        />
        <span v-if="form.errors.phone" class="text-sm text-red-500">{{ form.errors.phone }}</span>
      </div>

      <div>
        <label class="mb-1 block text-sm text-gray-400">Email</label>
        <input
          v-model="form.email"
          type="email"
          required
          class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
          placeholder="ivan@example.com"
        />
        <span v-if="form.errors.email" class="text-sm text-red-500">{{ form.errors.email }}</span>
      </div>

      <div>
        <label class="mb-1 block text-sm text-gray-400">Пароль</label>
        <div class="relative">
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            required
            class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
            placeholder="••••••••"
          />
          <button
            type="button"
            @click="showPassword = !showPassword"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500"
          >
            <svg v-if="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
            </svg>
            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
            </svg>
          </button>
        </div>
        <span v-if="form.errors.password" class="text-sm text-red-500">{{ form.errors.password }}</span>
      </div>

      <div>
        <label class="mb-1 block text-sm text-gray-400">Подтверждение пароля</label>
        <input
          v-model="form.password_confirmation"
          type="password"
          required
          class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
          placeholder="••••••••"
        />
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="w-full rounded-lg bg-yellow-500 py-3 font-bold text-gray-900 transition-all hover:bg-yellow-600 disabled:opacity-50"
      >
        {{ form.processing ? 'Регистрация...' : 'Зарегистрироваться' }}
      </button>

      <div class="text-center text-sm text-gray-400">
        Уже есть аккаунт?
        <Link :href="route('login')" class="text-yellow-500 hover:underline">Войти</Link>
      </div>
    </form>
  </GuestLayout>
</template>
