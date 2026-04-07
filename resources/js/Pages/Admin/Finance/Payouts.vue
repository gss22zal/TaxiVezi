<script setup>
import { ref } from 'vue'
import { router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
  payouts: Object,
  stats: Object,
  filters: Object,
})

const formatDate = (date) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('ru-RU', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric'
  })
}

const formatAmount = (amount) => {
  const value = parseFloat(amount)
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB'
  }).format(value)
}

const getStatusClass = (status) => {
  const classes = {
    'paid': 'bg-green-500/20 text-green-400',
    'pending': 'bg-yellow-500/20 text-yellow-400',
    'cancelled': 'bg-red-500/20 text-red-400'
  }
  return classes[status] || 'bg-gray-500/20 text-gray-400'
}

const getStatusLabel = (status) => {
  const labels = {
    'paid': 'Выплачено',
    'pending': 'Ожидает',
    'cancelled': 'Отменено'
  }
  return labels[status] || status
}

const currentStatus = ref(props.filters?.status || 'all')
const searchQuery = ref(props.filters?.search || '')

const applyFilters = () => {
  router.get('/admin/finance/payouts', {
    status: currentStatus.value,
    search: searchQuery.value
  }, { preserveState: true })
}

const clearFilters = () => {
  currentStatus.value = 'all'
  searchQuery.value = ''
  router.get('/admin/finance/payouts', {}, { preserveState: true })
}
</script>

<template>
  <AdminLayout activeTab="finance">
    <!-- Заголовок -->
    <div class="mb-6">
      <h1 class="text-2xl font-bold text-white">Выплаты</h1>
      <p class="text-gray-400">Выплаты водителям</p>
    </div>

    <!-- Статистика -->
    <div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-5">
      <div class="rounded-lg bg-gray-800 p-4">
        <div class="text-2xl font-bold text-white">{{ stats.total }}</div>
        <div class="text-sm text-gray-400">Всего выплат</div>
      </div>
      <div class="rounded-lg bg-green-500/20 p-4">
        <div class="text-2xl font-bold text-green-400">{{ formatAmount(stats.totalAmount) }}</div>
        <div class="text-sm text-gray-400">Выплачено</div>
      </div>
      <div class="rounded-lg bg-yellow-500/20 p-4">
        <div class="text-2xl font-bold text-yellow-400">{{ formatAmount(stats.pendingAmount) }}</div>
        <div class="text-sm text-gray-400">Ожидает</div>
      </div>
      <div class="rounded-lg bg-blue-500/20 p-4">
        <div class="text-2xl font-bold text-blue-400">{{ stats.totalRides }}</div>
        <div class="text-sm text-gray-400">Всего поездок</div>
      </div>
      <div class="rounded-lg bg-gray-800 p-4">
        <div class="text-2xl font-bold text-white">{{ stats.byStatus.pending }}</div>
        <div class="text-sm text-gray-400">В ожидании</div>
      </div>
    </div>

    <!-- Фильтры -->
    <div class="mb-4 flex flex-wrap gap-4">
      <select
        v-model="currentStatus"
        @change="applyFilters"
        class="rounded-lg bg-gray-700 px-4 py-2 text-sm text-white"
      >
        <option value="all">Все статусы</option>
        <option value="paid">Выплачено</option>
        <option value="pending">Ожидает</option>
        <option value="cancelled">Отменено</option>
      </select>

      <input
        v-model="searchQuery"
        @keyup.enter="applyFilters"
        type="text"
        placeholder="Поиск по номеру или водителю..."
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

    <!-- Таблица выплат -->
    <div class="overflow-x-auto rounded-lg bg-gray-800">
      <table class="w-full text-left text-sm">
        <thead class="bg-gray-700 text-gray-400">
          <tr>
            <th class="px-4 py-3">Номер</th>
            <th class="px-4 py-3">Водитель</th>
            <th class="px-4 py-3">Период</th>
            <th class="px-4 py-3">Поездок</th>
            <th class="px-4 py-3">Заработано</th>
            <th class="px-4 py-3">Комиссия</th>
            <th class="px-4 py-3">Сумма</th>
            <th class="px-4 py-3">Статус</th>
            <th class="px-4 py-3">Банк</th>
            <th class="px-4 py-3">Дата</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
          <tr
            v-for="payout in payouts.data"
            :key="payout.id"
            class="hover:bg-gray-700/50"
          >
            <td class="px-4 py-3 text-white font-mono text-xs">
              {{ payout.payout_number }}
            </td>
            <td class="px-4 py-3">
              <div class="text-white">
                {{ payout.driver?.user?.first_name }} {{ payout.driver?.user?.last_name }}
              </div>
              <div class="text-xs text-gray-400">
                {{ payout.driver?.user?.phone || '-' }}
              </div>
            </td>
            <td class="px-4 py-3 text-gray-300">
              {{ formatDate(payout.period_start) }} - {{ formatDate(payout.period_end) }}
            </td>
            <td class="px-4 py-3 text-gray-300">
              {{ payout.total_rides || '-' }}
            </td>
            <td class="px-4 py-3 text-gray-300">
              {{ payout.total_earnings ? formatAmount(payout.total_earnings) : '-' }}
            </td>
            <td class="px-4 py-3 text-red-400">
              {{ payout.commission_deducted ? formatAmount(payout.commission_deducted) : '-' }}
            </td>
            <td class="px-4 py-3">
              <span class="font-bold text-green-400">
                {{ formatAmount(payout.amount) }}
              </span>
            </td>
            <td class="px-4 py-3">
              <span :class="['rounded-full px-3 py-1 text-xs font-medium', getStatusClass(payout.status)]">
                {{ getStatusLabel(payout.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-gray-300">
              <div v-if="payout.bank_name">{{ payout.bank_name }}</div>
              <div class="text-xs text-gray-500">{{ payout.bank_account }}</div>
            </td>
            <td class="px-4 py-3 text-gray-400">
              {{ formatDate(payout.created_at) }}
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="payouts.data.length === 0" class="p-8 text-center text-gray-400">
        Выплаты не найдены
      </div>
    </div>

    <!-- Пагинация -->
    <div v-if="payouts.last_page > 1" class="mt-4 flex justify-center gap-2">
      <button
        v-for="page in payouts.links"
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
