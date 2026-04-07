<script setup>
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  transactions: Object,
  stats: Object,
  filters: Object,
})

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatAmount = (amount) => {
  const value = parseFloat(amount)
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(value)
}

const getTypeLabel = (type) => {
  const labels = {
    'ride_payment': 'Оплата поездки',
    'topup': 'Пополнение',
    'payout': 'Выплата',
    'bonus': 'Бонус',
    'refund': 'Возврат'
  }
  return labels[type] || type
}

const getTypeClass = (type) => {
  const classes = {
    'ride_payment': 'bg-blue-500/20 text-blue-400',
    'topup': 'bg-green-500/20 text-green-400',
    'payout': 'bg-yellow-500/20 text-yellow-400',
    'bonus': 'bg-purple-500/20 text-purple-400',
    'refund': 'bg-red-500/20 text-red-400'
  }
  return classes[type] || 'bg-gray-500/20 text-gray-400'
}

const getStatusClass = (status) => {
  const classes = {
    'success': 'bg-green-500/20 text-green-400',
    'pending': 'bg-yellow-500/20 text-yellow-400',
    'failed': 'bg-red-500/20 text-red-400'
  }
  return classes[status] || 'bg-gray-500/20 text-gray-400'
}

const getPaymentMethodLabel = (method) => {
  const labels = {
    'card': 'Карта',
    'cash': 'Наличные',
    'bank_card': 'Банковская карта',
    'yookassa': 'ЮKassa'
  }
  return labels[method] || method
}

const currentType = ref(props.filters?.type || 'all')
const currentStatus = ref(props.filters?.status || 'all')
const searchQuery = ref(props.filters?.search || '')

const applyFilters = () => {
  router.get('/admin/finance/transactions', {
    type: currentType.value,
    status: currentStatus.value,
    search: searchQuery.value
  }, { preserveState: true })
}

const clearFilters = () => {
  currentType.value = 'all'
  currentStatus.value = 'all'
  searchQuery.value = ''
  router.get('/admin/finance/transactions', {}, { preserveState: true })
}
</script>

<template>
  <AdminLayout activeTab="finance">
    <!-- Заголовок -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-white">Транзакции</h1>
      <p class="text-gray-400">Финансовые операции системы</p>
    </div>

    <!-- Статистика -->
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">
      <div class="rounded-lg bg-gray-800 p-4">
        <div class="text-2xl font-bold text-white">{{ stats.total }}</div>
        <div class="text-sm text-gray-400">Всего транзакций</div>
      </div>
      <div class="rounded-lg bg-gray-800 p-4">
        <div class="text-2xl font-bold text-green-400">{{ formatAmount(stats.totalAmount) }}</div>
        <div class="text-sm text-gray-400">Общая сумма</div>
      </div>
      <div class="rounded-lg bg-gray-800 p-4">
        <div class="text-2xl font-bold text-blue-400">{{ formatAmount(stats.byType.ride_payment) }}</div>
        <div class="text-sm text-gray-400">Оплата поездок</div>
      </div>
      <div class="rounded-lg bg-gray-800 p-4">
        <div class="text-2xl font-bold text-yellow-400">{{ formatAmount(stats.byType.payout) }}</div>
        <div class="text-sm text-gray-400">Выплаты</div>
      </div>
      <div class="rounded-lg bg-green-500/20 p-4">
        <div class="text-2xl font-bold text-green-400">{{ stats.byStatus.success }}</div>
        <div class="text-sm text-gray-400">Успешных</div>
      </div>
      <div class="rounded-lg bg-yellow-500/20 p-4">
        <div class="text-2xl font-bold text-yellow-400">{{ stats.byStatus.pending + stats.byStatus.failed }}</div>
        <div class="text-sm text-gray-400">Ожидают/Ошибки</div>
      </div>
    </div>

    <!-- Фильтры -->
    <div class="mb-4 flex flex-wrap gap-4">
      <select
        v-model="currentType"
        @change="applyFilters"
        class="rounded-lg bg-gray-700 px-4 py-2 text-sm text-white"
      >
        <option value="all">Все типы</option>
        <option value="ride_payment">Оплата поездки</option>
        <option value="topup">Пополнение</option>
        <option value="payout">Выплата</option>
        <option value="bonus">Бонус</option>
        <option value="refund">Возврат</option>
      </select>

      <select
        v-model="currentStatus"
        @change="applyFilters"
        class="rounded-lg bg-gray-700 px-4 py-2 text-sm text-white"
      >
        <option value="all">Все статусы</option>
        <option value="success">Успешно</option>
        <option value="pending">Ожидает</option>
        <option value="failed">Ошибка</option>
      </select>

      <input
        v-model="searchQuery"
        @keyup.enter="applyFilters"
        type="text"
        placeholder="Поиск по ID или имени..."
        class="flex-1 rounded-lg bg-gray-700 px-4 py-2 text-sm text-white placeholder-gray-400"
      />

      <button
        @click="applyFilters"
        class="rounded-lg bg-yellow-500 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-yellow-400"
      >
        Применить
      </button>

      <button
        @click="clearFilters"
        class="rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-gray-300 hover:bg-gray-600"
      >
        Сбросить
      </button>
    </div>

    <!-- Таблица транзакций -->
    <div class="overflow-x-auto rounded-lg bg-gray-800">
      <table class="w-full text-left text-sm">
        <thead class="bg-gray-700 text-gray-400">
          <tr>
            <th class="px-4 py-3">ID транзакции</th>
            <th class="px-4 py-3">Пользователь</th>
            <th class="px-4 py-3">Тип</th>
            <th class="px-4 py-3">Сумма</th>
            <th class="px-4 py-3">Способ оплаты</th>
            <th class="px-4 py-3">Статус</th>
            <th class="px-4 py-3">Дата</th>
            <th class="px-4 py-3">Описание</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
          <tr
            v-for="transaction in transactions.data"
            :key="transaction.id"
            class="hover:bg-gray-700/50"
          >
            <td class="px-4 py-3 text-white font-mono text-xs">
              {{ transaction.transaction_id || '#' + transaction.id }}
            </td>
            <td class="px-4 py-3">
              <div class="text-white">
                {{ transaction.user?.first_name }} {{ transaction.user?.last_name }}
              </div>
              <div class="text-xs text-gray-400">
                {{ transaction.user?.role === 'passenger' ? 'Пассажир' : (transaction.user?.role === 'driver' ? 'Водитель' : transaction.user?.role) }}
              </div>
            </td>
            <td class="px-4 py-3">
              <span :class="['rounded-full px-3 py-1 text-xs font-medium', getTypeClass(transaction.type)]">
                {{ getTypeLabel(transaction.type) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span :class="[
                'font-bold',
                parseFloat(transaction.amount) >= 0 ? 'text-green-400' : 'text-red-400'
              ]">
                {{ formatAmount(transaction.amount) }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-300">
              {{ transaction.payment_method ? getPaymentMethodLabel(transaction.payment_method) : '-' }}
            </td>
            <td class="px-4 py-3">
              <span :class="['rounded-full px-3 py-1 text-xs font-medium', getStatusClass(transaction.status)]">
                {{ transaction.status === 'success' ? 'Успешно' : (transaction.status === 'pending' ? 'Ожидает' : 'Ошибка') }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-400">
              {{ formatDate(transaction.created_at) }}
            </td>
            <td class="px-4 py-3 text-gray-300 max-w-xs truncate">
              {{ transaction.description || '-' }}
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="transactions.data.length === 0" class="p-8 text-center text-gray-400">
        Транзакции не найдены
      </div>
    </div>

    <!-- Пагинация -->
    <div v-if="transactions.last_page > 1" class="mt-4 flex justify-center gap-2">
      <button
        v-for="page in transactions.links"
        :key="page.label"
        @click="page.url && router.visit(page.url)"
        :class="[
          'rounded-lg px-4 py-2 text-sm',
          page.active ? 'bg-yellow-500 text-gray-900' : 'bg-gray-700 text-gray-300 hover:bg-gray-600',
          !page.url && 'opacity-50 cursor-not-allowed'
        ]"
        v-html="page.label"
        :disabled="!page.url"
      />
    </div>
  </AdminLayout>
</template>
