<template>
  <div class="admin-audit-logs">
    <!-- Page header -->
    <header class="mb-6">
      <h2 class="text-2xl font-bold text-secondary-50">Logs d'audit</h2>
      <p class="text-secondary-400 mt-1">Historique des actions du système</p>
    </header>

    <!-- Filters - RGAA 11.1, 11.2 -->
    <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700 mb-6">
      <form @submit.prevent class="flex flex-wrap gap-4" role="search" aria-label="Filtrer les logs d'audit">
        <!-- Action -->
        <div class="flex flex-col">
          <label for="filter-action" class="text-xs text-secondary-400 mb-1">Action</label>
          <select
            id="filter-action"
            v-model="filters.action"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @change="loadLogs"
          >
            <option value="">Toutes les actions</option>
            <option v-for="action in availableActions" :key="action.value" :value="action.value">
              {{ action.label }}
            </option>
          </select>
        </div>

        <!-- Sévérité -->
        <div class="flex flex-col">
          <label for="filter-severity" class="text-xs text-secondary-400 mb-1">Sévérité</label>
          <select
            id="filter-severity"
            v-model="filters.severity"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @change="loadLogs"
          >
            <option value="">Toutes les sévérités</option>
            <option value="info">Info</option>
            <option value="warning">Warning</option>
            <option value="high">High</option>
          </select>
        </div>

        <!-- Date de début -->
        <div class="flex flex-col">
          <label for="filter-date-from" class="text-xs text-secondary-400 mb-1">Date de début</label>
          <input
            id="filter-date-from"
            v-model="filters.dateFrom"
            type="date"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @change="loadLogs"
          />
        </div>

        <!-- Date de fin -->
        <div class="flex flex-col">
          <label for="filter-date-to" class="text-xs text-secondary-400 mb-1">Date de fin</label>
          <input
            id="filter-date-to"
            v-model="filters.dateTo"
            type="date"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @change="loadLogs"
          />
        </div>

        <!-- Bouton reset -->
        <div class="flex flex-col justify-end">
          <button
            type="button"
            class="px-4 py-2 bg-secondary-600 hover:bg-secondary-500 text-secondary-200 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-secondary-500"
            @click="resetFilters"
          >
            Réinitialiser
          </button>
        </div>
      </form>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="flex justify-center py-12" role="status" aria-label="Chargement en cours">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500" aria-hidden="true"></div>
      <span class="sr-only">Chargement des logs...</span>
    </div>

    <!-- Error state -->
    <div
      v-else-if="error"
      role="alert"
      class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-400"
    >
      <strong class="font-semibold">Erreur :</strong> {{ error }}
    </div>

    <!-- Logs Table -->
    <template v-else>
      <AccessibleTable
        :columns="logsColumns"
        :data="logs"
        caption="Historique des événements d'audit du système"
        empty-message="Aucun log trouvé pour ces critères"
        row-key="id"
      >
        <!-- Date -->
        <template #cell-createdAt="{ value }">
          <span class="text-secondary-400 text-sm">{{ formatDate(String(value)) }}</span>
        </template>

        <!-- Action -->
        <template #cell-action="{ value }">
          <span
            class="inline-block px-2 py-1 text-xs rounded font-medium"
            :class="getActionClass(String(value))"
          >
            {{ value }}
          </span>
        </template>

        <!-- Utilisateur -->
        <template #cell-performer="{ row }">
          <span class="text-secondary-200">
            {{ (row.performer as { pseudo?: string })?.pseudo || 'Système' }}
          </span>
        </template>

        <!-- Cible -->
        <template #cell-targetUser="{ row }">
          <span class="text-secondary-300">
            {{ (row.targetUser as { pseudo?: string })?.pseudo || '-' }}
          </span>
        </template>

        <!-- IP -->
        <template #cell-ipAddress="{ value }">
          <code class="text-secondary-400 font-mono text-xs bg-secondary-700/50 px-1 rounded">
            {{ value || '-' }}
          </code>
        </template>

        <!-- Détails -->
        <template #cell-details="{ row }">
          <button
            v-if="row.details && Object.keys(row.details as object).length > 0"
            type="button"
            class="text-primary-400 hover:text-primary-300 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 rounded px-1"
            :aria-label="`Voir les détails du log ${row.id}`"
            @click="showDetails(row)"
          >
            Voir détails
          </button>
          <span v-else class="text-secondary-500 text-sm">-</span>
        </template>
      </AccessibleTable>

      <!-- Pagination -->
      <div v-if="meta.totalPages > 1" class="mt-4">
        <AccessiblePagination
          :current-page="meta.page"
          :total-pages="meta.totalPages"
          :total="meta.total"
          :per-page="meta.limit"
          aria-label="Pagination des logs d'audit"
          @page-change="goToPage"
        />
      </div>
    </template>

    <!-- Details Modal -->
    <AccessibleModal
      :is-open="showDetailsModal"
      title="Détails du log"
      size="lg"
      @close="showDetailsModal = false"
    >
      <div v-if="selectedLog" class="space-y-4">
        <dl class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-secondary-400">ID</dt>
            <dd class="text-secondary-200 font-mono">{{ selectedLog.id }}</dd>
          </div>
          <div>
            <dt class="text-secondary-400">Action</dt>
            <dd>
              <span
                class="inline-block px-2 py-1 text-xs rounded"
                :class="getActionClass(selectedLog.action)"
              >
                {{ selectedLog.action }}
              </span>
            </dd>
          </div>
          <div>
            <dt class="text-secondary-400">Utilisateur</dt>
            <dd class="text-secondary-200">{{ selectedLog.performer?.pseudo || 'Système' }}</dd>
          </div>
          <div>
            <dt class="text-secondary-400">Cible</dt>
            <dd class="text-secondary-200">{{ selectedLog.targetUser?.pseudo || '-' }}</dd>
          </div>
          <div>
            <dt class="text-secondary-400">Date</dt>
            <dd class="text-secondary-200">{{ formatDate(selectedLog.createdAt) }}</dd>
          </div>
          <div>
            <dt class="text-secondary-400">Adresse IP</dt>
            <dd class="text-secondary-200 font-mono">{{ selectedLog.ipAddress || '-' }}</dd>
          </div>
        </dl>

        <div v-if="selectedLog.details && Object.keys(selectedLog.details).length > 0">
          <h4 class="text-secondary-400 text-sm mb-2">Détails</h4>
          <pre class="bg-secondary-900 rounded p-3 text-xs text-secondary-300 overflow-x-auto">{{ JSON.stringify(selectedLog.details, null, 2) }}</pre>
        </div>

        <div v-if="selectedLog.userAgent">
          <h4 class="text-secondary-400 text-sm mb-2">User Agent</h4>
          <p class="text-secondary-300 text-xs break-all">{{ selectedLog.userAgent }}</p>
        </div>
      </div>

      <template #footer="{ close }">
        <button
          type="button"
          class="px-4 py-2 bg-secondary-700 hover:bg-secondary-600 text-secondary-200 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-secondary-500"
          @click="close"
        >
          Fermer
        </button>
      </template>
    </AccessibleModal>
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
import { AccessibleTable, AccessibleModal, AccessiblePagination, type TableColumn } from '@/components/a11y'

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

const showDetailsModal = ref(false)
const selectedLog = ref<AuditLogEntry | null>(null)

const logsColumns: TableColumn[] = [
  { key: 'createdAt', label: 'Date' },
  { key: 'action', label: 'Action' },
  { key: 'performer', label: 'Utilisateur' },
  { key: 'targetUser', label: 'Cible' },
  { key: 'ipAddress', label: 'IP' },
  { key: 'details', label: 'Détails', align: 'right' },
]

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
    return 'bg-red-500/20 text-red-400'
  }
  if (action.includes('UPDATE') || action.includes('LOCK')) {
    return 'bg-yellow-500/20 text-yellow-400'
  }
  if (action.includes('CREATE') || action.includes('LOGIN')) {
    return 'bg-green-500/20 text-green-400'
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

const resetFilters = () => {
  filters.action = ''
  filters.severity = undefined
  filters.dateFrom = ''
  filters.dateTo = ''
  filters.page = 1
  loadLogs()
}

const goToPage = (page: number) => {
  filters.page = page
  loadLogs()
}

const showDetails = (log: Record<string, unknown>) => {
  selectedLog.value = log as AuditLogEntry
  showDetailsModal.value = true
}

onMounted(() => {
  loadActions()
  loadLogs()
})
</script>
