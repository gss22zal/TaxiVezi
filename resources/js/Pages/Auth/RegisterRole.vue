<script setup>
import { Link } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'
</script>

<template>
  <GuestLayout>
    <div class="mb-6 text-center">
      <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-yellow-500">
        <svg class="h-8 w-8 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
          <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white">Регистрация</h1>
      <p class="mt-1 text-gray-400">Выберите тип аккаунта</p>
    </div>

    <div class="space-y-3">
      <!-- Пассажир -->
      <Link
        :href="route('register.passenger')"
        class="flex items-center gap-4 rounded-xl border border-gray-700 bg-gray-800 p-4 transition-all hover:border-yellow-500 hover:bg-gray-700"
      >
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-600">
          <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 3c1.66 0 3 1.34 3 3s-1.34 3-3 3-3-1.34-3-3 1.34-3 3-3zm0 14.2c-2.5 0-4.71-1.28-6-3.22.03-1.99 4-3.08 6-3.08 1.99 0 5.97 1.09 6 3.08-1.29 1.94-3.5 3.22-6 3.22z"/>
          </svg>
        </div>
        <div class="flex-1">
          <div class="font-bold text-white">Пассажир</div>
          <div class="text-sm text-gray-400">Заказывайте такси и отслеживайте поездки</div>
        </div>
        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </Link>

      <!-- Водитель -->
      <Link
        :href="route('register.driver')"
        class="flex items-center gap-4 rounded-xl border border-gray-700 bg-gray-800 p-4 transition-all hover:border-yellow-500 hover:bg-gray-700"
      >
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-600">
          <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
          </svg>
        </div>
        <div class="flex-1">
          <div class="font-bold text-white">Водитель</div>
          <div class="text-sm text-gray-400">Принимайте заказы и зарабатывайте</div>
        </div>
        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </Link>

      <!-- Диспетчер -->
      <Link
        :href="route('register.dispatcher')"
        class="flex items-center gap-4 rounded-xl border border-gray-700 bg-gray-800 p-4 transition-all hover:border-yellow-500 hover:bg-gray-700"
      >
        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-purple-600">
          <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
          </svg>
        </div>
        <div class="flex-1">
          <div class="font-bold text-white">Диспетчер</div>
          <div class="text-sm text-gray-400">Управляйте заказами и водителями</div>
        </div>
        <svg class="h-5 w-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
      </Link>
    </div>

    <div class="mt-6 text-center text-sm text-gray-400">
      Уже есть аккаунт?
      <Link :href="route('login')" class="text-yellow-500 hover:underline">Войти</Link>
    </div>
  </GuestLayout>
</template>
