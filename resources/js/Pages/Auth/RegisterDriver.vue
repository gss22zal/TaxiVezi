<script setup>
import { ref } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import GuestLayout from '@/Layouts/GuestLayout.vue'

const showPassword = ref(false)
const step = ref(1)

const form = useForm({
  // Данные водителя
  first_name: '',
  last_name: '',
  phone: '',
  email: '',
  password: '',
  password_confirmation: '',
  // Водительское удостоверение
  license_number: '',
  license_expiry: '',
  // Автомобиль
  car_brand: '',
  car_model: '',
  car_year: '',
  car_color: '',
  car_plate: '',
  car_region: '',
  car_class: 'econom',
})

const carClassOptions = [
  { id: 'econom', name: 'Эконом', desc: 'KIA Rio, Hyundai Solaris, VW Polo' },
  { id: 'comfort', name: 'Комфорт', desc: 'KIA K5, Hyundai Sonata, Toyota Camry' },
  { id: 'business', name: 'Бизнес', desc: 'BMW 3, Mercedes C-Class, Audi A4' },
  { id: 'minivan', name: 'Минивэн', desc: 'KIA Carnival, Toyota Sienna' },
  { id: 'premium', name: 'Премиум', desc: 'Mercedes S-Class, BMW 7, Porsche Panamera' },
]

const nextStep = () => {
  if (step.value === 1) {
    if (!form.first_name || !form.last_name || !form.phone || !form.email || !form.password) {
      alert('Заполните все обязательные поля')
      return
    }
  }
  if (step.value === 2) {
    if (!form.license_number || !form.license_expiry) {
      alert('Заполните данные водительского удостоверения')
      return
    }
  }
  step.value++
}

const prevStep = () => {
  step.value--
}

const submit = () => {
  if (!form.car_brand || !form.car_model || !form.car_plate || !form.car_class) {
    alert('Заполните данные автомобиля')
    return
  }
  form.post(route('register.driver'), {
    onFinish: () => form.reset('password', 'password_confirmation'),
  })
}
</script>

<template>
  <GuestLayout>
    <div class="mb-6 text-center">
      <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-yellow-500">
        <svg class="h-8 w-8 text-gray-900" fill="currentColor" viewBox="0 0 24 24">
          <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
        </svg>
      </div>
      <h1 class="text-2xl font-bold text-white">Регистрация водителя</h1>
      <p class="mt-1 text-gray-400">Присоединяйтесь к нашей команде</p>
    </div>

    <!-- Progress Steps -->
    <div class="mb-6 flex items-center justify-center gap-2">
      <div :class="['flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold', step >= 1 ? 'bg-yellow-500 text-gray-900' : 'bg-gray-700 text-gray-400']">1</div>
      <div class="h-0.5 w-8" :class="step >= 2 ? 'bg-yellow-500' : 'bg-gray-700'"></div>
      <div :class="['flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold', step >= 2 ? 'bg-yellow-500 text-gray-900' : 'bg-gray-700 text-gray-400']">2</div>
      <div class="h-0.5 w-8" :class="step >= 3 ? 'bg-yellow-500' : 'bg-gray-700'"></div>
      <div :class="['flex h-8 w-8 items-center justify-center rounded-full text-sm font-bold', step >= 3 ? 'bg-yellow-500 text-gray-900' : 'bg-gray-700 text-gray-400']">3</div>
    </div>

    <form @submit.prevent="submit">
      <!-- Шаг 1: Личные данные -->
      <div v-if="step === 1" class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="mb-1 block text-sm text-gray-400">Имя *</label>
            <input
              v-model="form.first_name"
              type="text"
              required
              class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
              placeholder="Иван"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm text-gray-400">Фамилия *</label>
            <input
              v-model="form.last_name"
              type="text"
              required
              class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
              placeholder="Петров"
            />
          </div>
        </div>

        <div>
          <label class="mb-1 block text-sm text-gray-400">Телефон *</label>
          <input
            v-model="form.phone"
            type="tel"
            required
            class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
            placeholder="+7 (999) 123-45-67"
          />
        </div>

        <div>
          <label class="mb-1 block text-sm text-gray-400">Email *</label>
          <input
            v-model="form.email"
            type="email"
            required
            class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
            placeholder="ivan@example.com"
          />
        </div>

        <div>
          <label class="mb-1 block text-sm text-gray-400">Пароль *</label>
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            required
            class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
            placeholder="••••••••"
          />
        </div>

        <div>
          <label class="mb-1 block text-sm text-gray-400">Подтверждение пароля *</label>
          <input
            v-model="form.password_confirmation"
            type="password"
            required
            class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
            placeholder="••••••••"
          />
        </div>

        <button
          type="button"
          @click="nextStep"
          class="w-full rounded-lg bg-yellow-500 py-3 font-bold text-gray-900 transition-all hover:bg-yellow-600"
        >
          Далее
        </button>
      </div>

      <!-- Шаг 2: Водительское удостоверение -->
      <div v-if="step === 2" class="space-y-4">
        <div>
          <label class="mb-1 block text-sm text-gray-400">Номер водительского удостоверения *</label>
          <input
            v-model="form.license_number"
            type="text"
            required
            class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
            placeholder="77АА123456"
          />
        </div>

        <div>
          <label class="mb-1 block text-sm text-gray-400">Срок действия *</label>
          <input
            v-model="form.license_expiry"
            type="date"
            required
            class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
          />
        </div>

        <div class="flex gap-3">
          <button
            type="button"
            @click="prevStep"
            class="flex-1 rounded-lg border border-gray-600 py-3 font-medium text-gray-300 transition-all hover:bg-gray-700"
          >
            Назад
          </button>
          <button
            type="button"
            @click="nextStep"
            class="flex-1 rounded-lg bg-yellow-500 py-3 font-bold text-gray-900 transition-all hover:bg-yellow-600"
          >
            Далее
          </button>
        </div>
      </div>

      <!-- Шаг 3: Автомобиль -->
      <div v-if="step === 3" class="space-y-4">
        <div>
          <label class="mb-2 block text-sm text-gray-400">Класс автомобиля *</label>
          <div class="grid grid-cols-1 gap-2">
            <button
              v-for="opt in carClassOptions"
              :key="opt.id"
              type="button"
              @click="form.car_class = opt.id"
              :class="[
                'rounded-lg border p-3 text-left transition-all',
                form.car_class === opt.id 
                  ? 'border-yellow-500 bg-yellow-500/10' 
                  : 'border-gray-600 bg-gray-800 hover:border-gray-500'
              ]"
            >
              <div class="font-medium text-white">{{ opt.name }}</div>
              <div class="text-xs text-gray-400">{{ opt.desc }}</div>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="mb-1 block text-sm text-gray-400">Марка авто *</label>
            <input
              v-model="form.car_brand"
              type="text"
              required
              class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
              placeholder="KIA"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm text-gray-400">Модель *</label>
            <input
              v-model="form.car_model"
              type="text"
              required
              class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
              placeholder="Rio"
            />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-4">
          <div>
            <label class="mb-1 block text-sm text-gray-400">Год</label>
            <input
              v-model="form.car_year"
              type="number"
              class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
              placeholder="2023"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm text-gray-400">Цвет</label>
            <input
              v-model="form.car_color"
              type="text"
              class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
              placeholder="Белый"
            />
          </div>
          <div>
            <label class="mb-1 block text-sm text-gray-400">Госномер *</label>
            <input
              v-model="form.car_plate"
              type="text"
              required
              class="w-full rounded-lg border border-gray-600 bg-gray-800 px-4 py-3 text-white placeholder-gray-500 focus:border-yellow-500 focus:outline-none"
              placeholder="А123ВГ77"
            />
          </div>
        </div>

        <div class="flex gap-3">
          <button
            type="button"
            @click="prevStep"
            class="flex-1 rounded-lg border border-gray-600 py-3 font-medium text-gray-300 transition-all hover:bg-gray-700"
          >
            Назад
          </button>
          <button
            type="submit"
            :disabled="form.processing"
            class="flex-1 rounded-lg bg-yellow-500 py-3 font-bold text-gray-900 transition-all hover:bg-yellow-600 disabled:opacity-50"
          >
            {{ form.processing ? 'Регистрация...' : 'Зарегистрироваться' }}
          </button>
        </div>
      </div>
    </form>

    <div class="mt-4 text-center text-sm text-gray-400">
      Уже есть аккаунт?
      <Link :href="route('login')" class="text-yellow-500 hover:underline">Войти</Link>
    </div>
  </GuestLayout>
</template>
