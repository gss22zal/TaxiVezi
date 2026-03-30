<script setup>
import { ref, reactive, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  settingsData: {
    type: Object,
    required: true
  }
})

const activeGroup = ref('general')
const saving = ref(false)
const successMessage = ref('')

// Локальная копия настроек для редактирования
const localSettings = reactive({})

// Инициализация локальных настроек
const initLocalSettings = () => {
  Object.keys(props.settingsData).forEach(group => {
    localSettings[group] = {}
    Object.keys(props.settingsData[group].settings || {}).forEach(key => {
      localSettings[group][key] = props.settingsData[group].settings[key].value
    })
  })
}

initLocalSettings()

// Следим за изменением props (на случай перезагрузки)
watch(() => props.settingsData, initLocalSettings, { deep: true })

const saveSettings = async () => {
  saving.value = true
  successMessage.value = ''

  try {
    await router.post('/admin/settings', {
      settings: localSettings
    }, {
      onSuccess: () => {
        successMessage.value = 'Настройки сохранены!'
        setTimeout(() => successMessage.value = '', 3000)
      },
      onError: (errors) => {
        console.error('Ошибки сохранения:', errors)
        alert('Ошибка при сохранении настроек')
      }
    })
  } finally {
    saving.value = false
  }
}

// Получить конфигурацию настройки
const getConfig = (group, key) => {
  return props.settingsData[group]?.settings?.[key] || {}
}
</script>

<template>
  <AdminLayout activeTab="settings">
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-white">Системные настройки</h1>
      <p class="text-gray-400">Настройка параметров системы</p>
    </div>

    <!-- Сообщение об успехе -->
    <div v-if="successMessage" class="mb-4 p-4 bg-green-500/20 border border-green-500 rounded-lg text-green-400">
      ✅ {{ successMessage }}
    </div>

    <form @submit.prevent="saveSettings">
      <div class="flex gap-6">
        <!-- Боковое меню групп настроек -->
        <div class="w-64 flex-shrink-0">
          <div class="rounded-xl border border-gray-700 bg-gray-800 p-2">
            <button
              v-for="(data, group) in settingsData"
              :key="group"
              type="button"
              @click="activeGroup = group"
              :class="[
                'w-full text-left px-4 py-3 rounded-lg transition-all',
                activeGroup === group
                  ? 'bg-red-500/20 text-red-400'
                  : 'text-gray-400 hover:bg-gray-700 hover:text-white'
              ]"
            >
              {{ data.title }}
            </button>
          </div>
        </div>

        <!-- Содержимое группы -->
        <div class="flex-1">
          <div class="rounded-xl border border-gray-700 bg-gray-800 p-6">
            <h2 class="text-xl font-semibold text-white mb-6">
              {{ settingsData[activeGroup]?.title }}
            </h2>

            <div v-if="settingsData[activeGroup]?.settings" class="space-y-4">
              <template v-for="(config, key) in settingsData[activeGroup].settings" :key="key">
                <!-- Boolean (checkbox) -->
                <div v-if="config.type === 'boolean'" class="flex items-center justify-between py-3 px-4 bg-gray-700/50 rounded-lg">
                  <div>
                    <label :for="key" class="text-white font-medium block">
                      {{ key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                    </label>
                    <p v-if="config.description" class="text-gray-400 text-sm mt-1">
                      {{ config.description }}
                    </p>
                  </div>
                  <label class="relative inline-flex items-center cursor-pointer">
                    <input
                      type="checkbox"
                      :id="key"
                      v-model="localSettings[activeGroup][key]"
                      class="sr-only peer"
                    />
                    <div class="w-11 h-6 bg-gray-600 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-green-500"></div>
                  </label>
                </div>

                <!-- Number -->
                <div v-else-if="config.type === 'number'" class="py-3 px-4 bg-gray-700/50 rounded-lg">
                  <label :for="key" class="text-white font-medium block mb-2">
                    {{ key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                  </label>
                  <p v-if="config.description" class="text-gray-400 text-sm mb-2">
                    {{ config.description }}
                  </p>
                  <input
                    type="number"
                    :id="key"
                    v-model.number="localSettings[activeGroup][key]"
                    class="w-full bg-gray-900 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-red-500 focus:outline-none"
                  />
                </div>

                <!-- String / Text -->
                <div v-else class="py-3 px-4 bg-gray-700/50 rounded-lg">
                  <label :for="key" class="text-white font-medium block mb-2">
                    {{ key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                  </label>
                  <p v-if="config.description" class="text-gray-400 text-sm mb-2">
                    {{ config.description }}
                  </p>
                  <input
                    type="text"
                    :id="key"
                    v-model="localSettings[activeGroup][key]"
                    class="w-full bg-gray-900 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-red-500 focus:outline-none"
                  />
                </div>
              </template>
            </div>

            <div v-else class="text-gray-400 text-center py-8">
              Нет настроек для отображения
            </div>
          </div>

          <!-- Кнопка сохранения -->
          <div class="mt-6 flex justify-end">
            <button
              type="submit"
              :disabled="saving"
              class="px-6 py-3 bg-red-500 hover:bg-red-600 text-white font-medium rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ saving ? 'Сохранение...' : '💾 Сохранить настройки' }}
            </button>
          </div>
        </div>
      </div>
    </form>
  </AdminLayout>
</template>
