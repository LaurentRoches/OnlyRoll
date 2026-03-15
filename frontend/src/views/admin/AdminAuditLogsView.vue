<template>
  <div class="admin-audit-logs">
    <header class="mb-6">
      <h2 class="text-2xl font-bold text-secondary-50">{{ t('admin.auditLogs.title') }}</h2>
      <p class="text-secondary-400 mt-1">{{ t('admin.auditLogs.subtitle') }}</p>
    </header>

    <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700 mb-6">
      <form
        @submit.prevent
        class="flex flex-wrap gap-4"
        role="search"
        :aria-label="t('admin.auditLogs.searchAriaLabel')"
      >
        <div class="flex flex-col">
          <label for="filter-action" class="text-xs text-secondary-400 mb-1">{{ t('admin.auditLogs.filters.actionLabel') }}</label>
          <select
            id="filter-action"
            v-model="filters.action"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @change="loadLogs"
          >
            <option value="">{{ t('admin.auditLogs.filters.allActions') }}</option>
            <option v-for="action in availableActions" :key="action.value" :value="action.value">
              {{ action.label }}
            </option>
          </select>
        </div>

        <div class="flex flex-col">
          <label for="filter-severity" class="text-xs text-secondary-400 mb-1">{{ t('admin.auditLogs.filters.severityLabel') }}</label>
          <select
            id="filter-severity"
            v-model="filters.severity"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @change="loadLogs"
          >
            <option value="">{{ t('admin.auditLogs.filters.allSeverities') }}</option>
            <option value="info">{{ t('admin.auditLogs.filters.severityInfo') }}</option>
            <option value="warning">{{ t('admin.auditLogs.filters.severityWarning') }}</option>
            <option value="high">{{ t('admin.auditLogs.filters.severityHigh') }}</option>
          </select>
        </div>

        <div class="flex flex-col">
          <label for="filter-date-from" class="text-xs text-secondary-400 mb-1">{{ t('admin.auditLogs.filters.dateFrom') }}</label>
          <input
            id="filter-date-from"
            v-model="filters.dateFrom"
            type="date"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @change="loadLogs"
          />
        </div>

        <div class="flex flex-col">
          <label for="filter-date-to" class="text-xs text-secondary-400 mb-1">{{ t('admin.auditLogs.filters.dateTo') }}</label>
          <input
            id="filter-date-to"
            v-model="filters.dateTo"
            type="date"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @change="loadLogs"
          />
        </div>

        <div class="flex flex-col justify-end">
          <button
            type="button"
            class="px-4 py-2 bg-secondary-600 hover:bg-secondary-500 text-secondary-200 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-secondary-500"
            @click="resetFilters"
          >
            {{ t('admin.auditLogs.filters.reset') }}
          </button>
        </div>
      </form>
    </div>

    <div
      v-if="isLoading"
      class="flex justify-center py-12"
      role="status"
      :aria-label="t('admin.auditLogs.loading')"
    >
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"
        aria-hidden="true"
      ></div>
      <span class="sr-only">{{ t('admin.auditLogs.loadingLogs') }}</span>
    </div>

    <div
      v-else-if="error"
      role="alert"
      class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-400"
    >
      <strong class="font-semibold">{{ t('admin.auditLogs.errorLabel') }}</strong> {{ error }}
    </div>

    <template v-else>
      <AccessibleTable
        :columns="logsColumns"
        :data="logs"
        :caption="t('admin.auditLogs.table.caption')"
        :empty-message="t('admin.auditLogs.table.emptyMessage')"
        row-key="id"
      >
        <template #cell-createdAt="{ value }">
          <span class="text-secondary-400 text-sm">{{ formatDate(String(value)) }}</span>
        </template>

        <template #cell-action="{ value }">
          <span
            class="inline-block px-2 py-1 text-xs rounded font-medium"
            :class="getActionClass(String(value))"
          >
            {{ value }}
          </span>
        </template>

        <template #cell-performer="{ row }">
          <span class="text-secondary-200">
            {{ (row.performer as { pseudo?: string })?.pseudo || t('admin.auditLogs.systemUser') }}
          </span>
        </template>

        <template #cell-targetUser="{ row }">
          <span class="text-secondary-300">
            {{ (row.targetUser as { pseudo?: string })?.pseudo || t('admin.auditLogs.noTarget') }}
          </span>
        </template>

        <template #cell-ipAddress="{ value }">
          <code class="text-secondary-400 font-mono text-xs bg-secondary-700/50 px-1 rounded">
            {{ value || t('admin.auditLogs.noIp') }}
          </code>
        </template>

        <template #cell-details="{ row }">
          <button
            v-if="row.details && Object.keys(row.details as object).length > 0"
            type="button"
            class="text-primary-400 hover:text-primary-300 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 rounded px-1"
            :aria-label="t('admin.auditLogs.viewDetailsAriaLabel', { id: row.id as number })"
            @click="showDetails(row)"
          >
            {{ t('admin.auditLogs.viewDetails') }}
          </button>
          <span v-else class="text-secondary-500 text-sm">-</span>
        </template>
      </AccessibleTable>

      <div v-if="meta.totalPages > 1" class="mt-4">
        <AccessiblePagination
          :current-page="meta.page"
          :total-pages="meta.totalPages"
          :total="meta.total"
          :per-page="meta.limit"
          :aria-label="t('admin.auditLogs.pagination.ariaLabel')"
          @page-change="goToPage"
        />
      </div>
    </template>

    <AccessibleModal
      :is-open="showDetailsModal"
      :title="t('admin.auditLogs.detailModal.title')"
      size="lg"
      @close="showDetailsModal = false"
    >
      <div v-if="selectedLog" class="space-y-4">
        <dl class="grid grid-cols-2 gap-4 text-sm">
          <div>
            <dt class="text-secondary-400">{{ t('admin.auditLogs.detailModal.id') }}</dt>
            <dd class="text-secondary-200 font-mono">{{ selectedLog.id }}</dd>
          </div>
          <div>
            <dt class="text-secondary-400">{{ t('admin.auditLogs.detailModal.action') }}</dt>
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
            <dt class="text-secondary-400">{{ t('admin.auditLogs.detailModal.user') }}</dt>
            <dd class="text-secondary-200">{{ selectedLog.performer?.pseudo || t('admin.auditLogs.systemUser') }}</dd>
          </div>
          <div>
            <dt class="text-secondary-400">{{ t('admin.auditLogs.detailModal.target') }}</dt>
            <dd class="text-secondary-200">{{ selectedLog.targetUser?.pseudo || t('admin.auditLogs.noTarget') }}</dd>
          </div>
          <div>
            <dt class="text-secondary-400">{{ t('admin.auditLogs.detailModal.date') }}</dt>
            <dd class="text-secondary-200">{{ formatDate(selectedLog.createdAt) }}</dd>
          </div>
          <div>
            <dt class="text-secondary-400">{{ t('admin.auditLogs.detailModal.ipAddress') }}</dt>
            <dd class="text-secondary-200 font-mono">{{ selectedLog.ipAddress || t('admin.auditLogs.noIp') }}</dd>
          </div>
        </dl>

        <div v-if="selectedLog.details && Object.keys(selectedLog.details).length > 0">
          <h4 class="text-secondary-400 text-sm mb-2">{{ t('admin.auditLogs.detailModal.details') }}</h4>
          <pre class="bg-secondary-900 rounded p-3 text-xs text-secondary-300 overflow-x-auto">{{
            JSON.stringify(selectedLog.details, null, 2)
          }}</pre>
        </div>

        <div v-if="selectedLog.userAgent">
          <h4 class="text-secondary-400 text-sm mb-2">{{ t('admin.auditLogs.detailModal.userAgent') }}</h4>
          <p class="text-secondary-300 text-xs break-all">{{ selectedLog.userAgent }}</p>
        </div>
      </div>

      <template #footer="{ close }">
        <button
          type="button"
          class="px-4 py-2 bg-secondary-700 hover:bg-secondary-600 text-secondary-200 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-secondary-500"
          @click="close"
        >
          {{ t('admin.auditLogs.detailModal.close') }}
        </button>
      </template>
    </AccessibleModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  adminApi,
  type AuditLogEntry,
  type AuditLogFilterParams,
  type AuditActionInfo,
} from '@/services/api/adminApi'
import {
  AccessibleTable,
  AccessibleModal,
  AccessiblePagination,
  type TableColumn,
} from '@/components/a11y'

const { t, locale } = useI18n()

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

const logsColumns = computed<TableColumn[]>(() => [
  { key: 'createdAt', label: t('admin.auditLogs.columns.date') },
  { key: 'action', label: t('admin.auditLogs.columns.action') },
  { key: 'performer', label: t('admin.auditLogs.columns.user') },
  { key: 'targetUser', label: t('admin.auditLogs.columns.target') },
  { key: 'ipAddress', label: t('admin.auditLogs.columns.ip') },
  { key: 'details', label: t('admin.auditLogs.columns.details'), align: 'right' },
])

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString(locale.value === 'fr' ? 'fr-FR' : 'en-US', {
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
    error.value = err instanceof Error ? err.message : t('admin.auditLogs.errorLoading')
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
  selectedLog.value = log as unknown as AuditLogEntry
  showDetailsModal.value = true
}

onMounted(() => {
  loadActions()
  loadLogs()
})
</script>
