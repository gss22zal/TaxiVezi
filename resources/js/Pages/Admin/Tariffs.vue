<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  tariffs: Array,
  pagination: Object,
  filters: Object
})

const showModal = ref(false)
const editingTariff = ref(null)

const form = useForm({
  name: '',
  code: '',
  description: '',
  base_price: 0,
  price_per_km: 0,
  price_per_min: 0,
  min_price: 0,
  min_distance: 0,
  min_duration: 0,
  commission_rate: 20,
  is_active: true
})

const openCreateModal = () => {
  editingTariff.value = null
  form.reset()
  form.is_active = true
  form.commission_rate = 20
  showModal.value = true
}

const openEditModal = (tariff) => {
  editingTariff.value = tariff
  form.name = tariff.name
  form.code = tariff.code
  form.description = tariff.description || ''
  form.base_price = tariff.base_price
  form.price_per_km = tariff.price_per_km
  form.price_per_min = tariff.price_per_min || tariff.price_per_km
  form.min_price = tariff.min_price
  form.min_distance = tariff.min_distance || 0
  form.min_duration = tariff.min_duration || 0
  form.commission_rate = tariff.commission_rate || 20
  form.is_active = tariff.is_active
  showModal.value = true
}

const closeModal = () => {
  showModal.value = false
  editingTariff.value = null
  form.reset()
}

const submitForm = () => {
  if (editingTariff.value) {
    form.put(route('admin.tariffs.update', editingTariff.value.id), {
      onSuccess: closeModal
    })
  } else {
    form.post(route('admin.tariffs.store'), {
      onSuccess: closeModal
    })
  }
}

const deleteTariff = (tariff) => {
  if (confirm(`Удалить тариф "${tariff.name}"?`)) {
    form.delete(route('admin.tariffs.destroy', tariff.id))
  }
}

const toggleActive = (tariff) => {
  form.post(route('admin.tariffs.toggle', tariff.id))
}

const formatPrice = (value) => {
  return new Intl.NumberFormat('ru-RU').format(value) + ' ₽'
}
</script>

<template>
  <AdminLayout activeTab="tariffs">
    <div class="mb-6 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white">Тарифы</h1>
        <p class="text-gray-400">Управление тарифами и ценообразованием</p>
      </div>
      <button
        @click="openCreateModal"
        class="rounded-lg bg-yellow-500 px-4 py-2 font-semibold text-gray-900 hover:bg-yellow-600"
      >
        + Добавить тариф
      </button>
    </div>

    <!-- Список тарифов -->
    <div class="grid gap-4">
      <div
        v-for="tariff in tariffs"
        :key="tariff.id"
        :class="[
          'rounded-xl border p-4 transition-all',
          tariff.is_active ? 'border-gray-700 bg-gray-800' : 'border-red-900/50 bg-gray-900/50 opacity-60'
        ]"
      >
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-700 text-2xl">
              🚕
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h3 class="text-lg font-semibold text-white">{{ tariff.name }}</h3>
                <span
                  :class="[
                    'rounded px-2 py-0.5 text-xs',
                    tariff.is_active ? 'bg-green-900/50 text-green-400' : 'bg-red-900/50 text-red-400'
                  ]"
                >
                  {{ tariff.is_active ? 'Активен' : 'Отключён' }}
                </span>
              </div>
              <p class="text-sm text-gray-400">{{ tariff.description || 'Без описания' }}</p>
            </div>
          </div>

          <div class="flex items-center gap-6">
            <div class="text-right">
              <div class="text-2xl font-bold text-yellow-500">{{ formatPrice(tariff.base_price) }}</div>
              <div class="text-xs text-gray-400">Базовая</div>
            </div>
            <div class="text-right">
              <div class="text-lg font-semibold text-white">{{ formatPrice(tariff.min_price) }}</div>
              <div class="text-xs text-gray-400">Мин. цена</div>
            </div>
            <div class="text-right">
              <div class="text-lg font-semibold text-white">{{ formatPrice(tariff.price_per_km) }}/км</div>
              <div class="text-xs text-gray-400">За км</div>
            </div>
            <div class="text-right">
              <div class="text-lg font-semibold text-white">{{ tariff.price_per_min || tariff.price_per_km }} ₽/мин</div>
              <div class="text-xs text-gray-400">За минуту</div>
            </div>
            <div class="text-right">
              <div class="text-lg font-semibold text-white">{{ tariff.commission_rate }}%</div>
              <div class="text-xs text-gray-400">Комиссия</div>
            </div>

            <div class="flex gap-2">
              <button
                @click="toggleActive(tariff)"
                :class="[
                  'rounded-lg px-3 py-2 text-sm',
                  tariff.is_active ? 'bg-red-900/50 text-red-400 hover:bg-red-900' : 'bg-green-900/50 text-green-400 hover:bg-green-900'
                ]"
              >
                {{ tariff.is_active ? 'Отключить' : 'Включить' }}
              </button>
              <button
                @click="openEditModal(tariff)"
                class="rounded-lg bg-gray-700 px-3 py-2 text-sm text-white hover:bg-gray-600"
              >
                Изменить
              </button>
              <button
                @click="deleteTariff(tariff)"
                class="rounded-lg bg-red-900/50 px-3 py-2 text-sm text-red-400 hover:bg-red-900"
              >
                Удалить
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="!tariffs.length" class="rounded-xl border border-gray-700 bg-gray-800 p-8 text-center text-gray-400">
        Тарифы не найдены. Создайте первый тариф.
      </div>
    </div>

    <!-- Модальное окно -->
    <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div class="w-full max-w-2xl rounded-xl border border-gray-700 bg-gray-800 p-6">
        <h2 class="mb-4 text-xl font-bold text-white">
          {{ editingTariff ? 'Редактирование тарифа' : 'Новый тариф' }}
        </h2>

        <form @submit.prevent="submitForm" class="space-y-4">
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="mb-1 block text-sm text-gray-400">Название</label>
              <input
                v-model="form.name"
                type="text"
                required
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white focus:border-yellow-500 focus:outline-none"
                placeholder="Эконом"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm text-gray-400">Код</label>
              <input
                v-model="form.code"
                type="text"
                required
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white focus:border-yellow-500 focus:outline-none"
                placeholder="economy"
              />
            </div>
          </div>

          <div>
            <label class="mb-1 block text-sm text-gray-400">Описание</label>
            <input
              v-model="form.description"
              type="text"
              class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white focus:border-yellow-500 focus:outline-none"
              placeholder="Доступные автомобили эконом-класса"
            />
          </div>

          <div class="grid grid-cols-4 gap-4">
            <div>
              <label class="mb-1 block text-sm text-gray-400">Базовая цена (₽)</label>
              <input
                v-model.number="form.base_price"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white focus:border-yellow-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm text-gray-400">Мин. цена (₽)</label>
              <input
                v-model.number="form.min_price"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white focus:border-yellow-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm text-gray-400">За км (₽)</label>
              <input
                v-model.number="form.price_per_km"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white focus:border-yellow-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm text-gray-400">За мин (₽)</label>
              <input
                v-model.number="form.price_per_min"
                type="number"
                step="0.01"
                min="0"
                required
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white focus:border-yellow-500 focus:outline-none"
              />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-4">
            <div>
              <label class="mb-1 block text-sm text-gray-400">Мин. расстояние (км)</label>
              <input
                v-model.number="form.min_distance"
                type="number"
                step="0.1"
                min="0"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white focus:border-yellow-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm text-gray-400">Мин. время (мин)</label>
              <input
                v-model.number="form.min_duration"
                type="number"
                min="0"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white focus:border-yellow-500 focus:outline-none"
              />
            </div>
            <div>
              <label class="mb-1 block text-sm text-gray-400">Комиссия (%)</label>
              <input
                v-model.number="form.commission_rate"
                type="number"
                step="0.1"
                min="0"
                max="100"
                class="w-full rounded-lg border border-gray-600 bg-gray-700 px-3 py-2 text-white focus:border-yellow-500 focus:outline-none"
              />
            </div>
          </div>

          <div class="flex items-center gap-2">
            <input
              v-model="form.is_active"
              type="checkbox"
              id="is_active"
              class="h-4 w-4 rounded border-gray-600 bg-gray-700 text-yellow-500 focus:ring-yellow-500"
            />
            <label for="is_active" class="text-sm text-gray-300">Активен</label>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button
              type="button"
              @click="closeModal"
              class="rounded-lg border border-gray-600 px-4 py-2 text-gray-300 hover:bg-gray-700"
            >
              Отмена
            </button>
            <button
              type="submit"
              :disabled="form.processing"
              class="rounded-lg bg-yellow-500 px-4 py-2 font-semibold text-gray-900 hover:bg-yellow-600 disabled:opacity-50"
            >
              {{ editingTariff ? 'Сохранить' : 'Создать' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
