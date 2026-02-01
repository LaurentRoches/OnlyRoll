<template>
  <div class="min-h-screen bg-secondary-900">
    <!-- Header -->
    <div class="bg-secondary-800 border-b border-secondary-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-secondary-50">Logs d'audit</h1>
            <p class="text-secondary-400 mt-1">Historique des actions</p>
          </div>
          <RouterLink
            to="/admin"
            class="px-4 py-2 bg-secondary-700 hover:bg-secondary-600 text-secondary-50 rounded-lg transition-colors"
          >
            Retour au dashboard
          </RouterLink>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700">
        <div class="flex flex-wrap gap-4">
          <select
            v-model="filters.action"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:border-primary-500"
            @change="loadLogs"
          >
            <option value="">Toutes les actions</option>
            <option v-for="action in availableActions" :key="action.value" :value="action.value">
              {{ action.label }}
            </option>
          </select>
          <select
            v-model="filters.severity"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:border-primary-500"
            @change="loadLogs"
          >
            <option value="">Toutes les severites</option>
            <option value="info">Info</option>
            <option value="warning">Warning</option>
            <option value="high">High</option>
          </select>
          <input
            v-model="filters.dateFrom"
            type="date"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:border-primary-500"
            @change="loadLogs"
          />
          <input
            v-model="filters.dateTo"
            type="date"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:border-primary-500"
            @change="loadLogs"
          />
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
      <!-- Loading state -->
      <div v-if="isLoading" class="flex justify-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
      </div>

      <!-- Error state -->
      <div
        v-else-if="error"
        class="bg-danger-500/10 border border-danger-500/50 rounded-lg p-4 text-danger-400"
      >
        {{ error }}
      </div>

      <!-- Logs Table -->
      <div v-else class="bg-secondary-800 rounded-lg border border-secondary-700 overflow-hidden">
        <table class="w-full">
          <thead class="bg-secondary-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                Date
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                Action
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                Utilisateur
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                Cible
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                IP
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-700">
            <tr
              v-for="log in logs"
              :key="log.id"
              class="hover:bg-secondary-700/50 transition-colors"
            >
              <td class="px-4 py-3 text-sm text-secondary-400">
                {{ formatDate(log.createdAt) }}
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-block px-2 py-1 text-xs rounded"
                  :class="getActionClass(log.action)"
                >
                  {{ log.action }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-secondary-200">
                {{ log.performer?.pseudo || 'Systeme' }}
              </td>
              <td class="px-4 py-3 text-sm text-secondary-300">
                {{ log.targetUser?.pseudo || '-' }}
              </td>
              <td class="px-4 py-3 text-sm text-secondary-400 font-mono">
                {{ log.ipAddress || '-' }}
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Pagination -->
        <div
          v-if="meta.totalPages > 1"
          class="bg-secondary-700 px-4 py-3 flex items-center justify-between"
        >
          <div class="text-sm text-secondary-400">
            Page {{ meta.page }} sur {{ meta.totalPages }}
          </div>
          <div class="flex space-x-2">
            <button
              :disabled="meta.page <= 1"
              class="px-3 py-1 bg-secondary-600 rounded text-secondary-200 disabled:opacity-50"
              @click="goToPage(meta.page - 1)"
            >
              Precedent
            </button>
            <button
              :disabled="meta.page >= meta.totalPages"
              class="px-3 py-1 bg-secondary-600 rounded text-secondary-200 disabled:opacity-50"
              @click="goToPage(meta.page + 1)"
            >
              Suivant
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import {
  adminApi,
  type AuditLogEntry,
  type AuditLogFilterParams,
  type AuditActionInfo,
} from '@/services/api/adminApi'

const isLoading = ref(true)
const error = ref<string | null>(null)
const logs = ref<AuditLogEntry[]>([])
const availableActions = ref<AuditActionInfo[]>([])
const meta = reactive({
  total: 0,
  page: 1,
  limit: 20,
  totalPages: 0,
})

const filters = reactive<AuditLogFilterParams>({
  action: '',
  severity: undefined,
  dateFrom: '',
  dateTo: '',
  page: 1,
  limit: 20,
})

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getActionClass = (action: string): string => {
  if (action.includes('FAILED') || action.includes('DELETE')) {
    return 'bg-danger-500/20 text-danger-400'
  }
  if (action.includes('UPDATE') || action.includes('LOCK')) {
    return 'bg-warning-500/20 text-warning-400'
  }
  if (action.includes('CREATE') || action.includes('LOGIN')) {
    return 'bg-success-500/20 text-success-400'
  }
  return 'bg-secondary-600 text-secondary-300'
}

const loadLogs = async () => {
  isLoading.value = true
  error.value = null

  try {
    const response = await adminApi.auditLogs.list({
      ...filters,
      action: filters.action || undefined,
      severity: filters.severity || undefined,
      dateFrom: filters.dateFrom || undefined,
      dateTo: filters.dateTo || undefined,
    })
    logs.value = response.data
    Object.assign(meta, response.meta)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Erreur lors du chargement'
  } finally {
    isLoading.value = false
  }
}

const loadActions = async () => {
  try {
    availableActions.value = await adminApi.auditLogs.getAvailableActions()
  } catch (err) {
    console.error('Failed to load actions:', err)
  }
}

const goToPage = (page: number) => {
  filters.page = page
  loadLogs()
}

onMounted(() => {
  loadActions()
  loadLogs()
})
</script>
