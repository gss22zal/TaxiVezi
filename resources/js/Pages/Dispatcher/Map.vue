<script setup>
import { ref } from 'vue'
import DispatcherLayout from '@/Layouts/DispatcherLayout.vue'

const showPlaceholder = ref(true)

const drivers = ref([
  { id: 1, name: 'Ахмедов Р.', lat: 55.7558, lng: 37.6173, status: 'free' },
  { id: 2, name: 'Козлов В.', lat: 55.7580, lng: 37.6210, status: 'busy' },
  { id: 3, name: 'Смирнов К.', lat: 55.7520, lng: 37.6150, status: 'free' },
])

const orders = ref([
  { id: 'ТК-4821', lat: 55.7600, lng: 37.6200, status: 'new' },
  { id: 'ТК-4820', lat: 55.7500, lng: 37.6100, status: 'accepted' },
])
</script>

<template>
  <DispatcherLayout activeTab="map">
    <!-- Page Header -->
    <div class="mb-4 flex items-center justify-between">
      <h1 class="text-2xl font-bold text-white">Карта</h1>
      <div class="flex gap-2">
        <button class="rounded-lg bg-gray-700 px-3 py-2 text-sm text-gray-400 hover:bg-gray-600 hover:text-white">
          <svg class="inline h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
          </svg>
          Фильтры
        </button>
      </div>
    </div>

    <!-- Map Container -->
    <div class="relative h-[calc(100vh-180px)] overflow-hidden rounded-xl border border-gray-700 bg-gray-800">
      <!-- Placeholder (shown when no map service connected) -->
      <div
        v-if="showPlaceholder"
        class="absolute inset-0 flex flex-col items-center justify-center bg-gray-800"
      >
        <svg class="mb-4 h-24 w-24 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
        </svg>
        <h2 class="mb-2 text-xl font-bold text-gray-400">Карта с отслеживанием водителей</h2>
        <p class="max-w-md text-center text-gray-500">
          Подключается при интеграции с картографическим сервисом (Яндекс.Карты, Google Maps, Mapbox)
        </p>
        <button
          @click="showPlaceholder = false"
          class="mt-4 rounded-lg bg-yellow-500 px-4 py-2 font-medium text-gray-900 hover:bg-yellow-600"
        >
          Показать демо
        </button>
      </div>

      <!-- Demo Map (simple representation) -->
      <div
        v-else
        class="relative h-full w-full bg-[#1a1a2e]"
      >
        <!-- Grid pattern to simulate map -->
        <div class="absolute inset-0 opacity-20" style="background-image: linear-gradient(#333 1px, transparent 1px), linear-gradient(90deg, #333 1px, transparent 1px); background-size: 40px 40px;"></div>

        <!-- Driver markers -->
        <div
          v-for="driver in drivers"
          :key="'d-' + driver.id"
          class="absolute cursor-pointer transition-transform hover:scale-110"
          :style="{ left: '50%', top: '50%' }"
        >
          <div :class="[
            'flex h-8 w-8 items-center justify-center rounded-full border-2 border-gray-900',
            driver.status === 'free' ? 'bg-green-500' : 'bg-yellow-500'
          ]">
            <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99z"/>
            </svg>
          </div>
          <div class="absolute -bottom-6 left-1/2 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-0.5 text-xs text-white">
            {{ driver.name }}
          </div>
        </div>

        <!-- Order markers -->
        <div
          v-for="order in orders"
          :key="'o-' + order.id"
          class="absolute cursor-pointer"
          :style="{ left: '45%', top: '40%' }"
        >
          <div :class="[
            'flex h-6 w-6 items-center justify-center rounded-full border-2 border-gray-900',
            order.status === 'new' ? 'bg-red-500' : 'bg-blue-500'
          ]">
            <svg class="h-3 w-3 text-white" fill="currentColor" viewBox="0 0 24 24">
              <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
            </svg>
          </div>
        </div>

        <!-- Map controls -->
        <div class="absolute bottom-4 right-4 flex flex-col gap-2">
          <button class="rounded-lg bg-gray-700 p-2 text-white hover:bg-gray-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
          </button>
          <button class="rounded-lg bg-gray-700 p-2 text-white hover:bg-gray-600">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/>
            </svg>
          </button>
        </div>

        <!-- Legend -->
        <div class="absolute left-4 top-4 rounded-lg bg-gray-900/90 p-3">
          <div class="space-y-2 text-xs">
            <div class="flex items-center gap-2">
              <span class="h-3 w-3 rounded-full bg-green-500"></span>
              <span class="text-gray-400">Свободен</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="h-3 w-3 rounded-full bg-yellow-500"></span>
              <span class="text-gray-400">Занят</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="h-3 w-3 rounded-full bg-red-500"></span>
              <span class="text-gray-400">Новый заказ</span>
            </div>
            <div class="flex items-center gap-2">
              <span class="h-3 w-3 rounded-full bg-blue-500"></span>
              <span class="text-gray-400">Принят</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </DispatcherLayout>
</template>
