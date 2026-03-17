<template>
  <div class="admin-dashboard">
    <header class="mb-8">
      <h2 class="text-2xl font-bold text-secondary-50">{{ t('admin.dashboard.title') }}</h2>
      <p class="text-secondary-400 mt-1">{{ t('admin.dashboard.subtitle') }}</p>
    </header>

    <div
      v-if="isLoading"
      class="flex justify-center py-12"
      role="status"
      :aria-label="t('admin.dashboard.loading')"
    >
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"
        aria-hidden="true"
      ></div>
      <span class="sr-only">{{ t('admin.dashboard.loadingData') }}</span>
    </div>

    <div
      v-else-if="error"
      role="alert"
      class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-400"
    >
      <strong class="font-semibold">{{ t('admin.dashboard.errorLabel') }}</strong> {{ error }}
    </div>

    <div v-else-if="stats" class="space-y-8">
      <section aria-labelledby="users-stats-heading">
        <h3 id="users-stats-heading" class="text-lg font-semibold text-secondary-50 mb-4">
          {{ t('admin.dashboard.users.heading') }}
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <article class="stats-card">
            <div class="text-3xl font-bold text-primary-500" aria-hidden="true">
              {{ stats.users.total }}
            </div>
            <div class="text-secondary-400 text-sm">{{ t('admin.dashboard.users.total') }}</div>
            <span class="sr-only">{{
              t('admin.dashboard.users.totalSrOnly', { count: stats.users.total })
            }}</span>
          </article>
          <article class="stats-card">
            <div class="text-3xl font-bold text-green-500" aria-hidden="true">
              {{ stats.users.active }}
            </div>
            <div class="text-secondary-400 text-sm">{{ t('admin.dashboard.users.active') }}</div>
            <span class="sr-only">{{
              t('admin.dashboard.users.activeSrOnly', { count: stats.users.active })
            }}</span>
          </article>
          <article class="stats-card">
            <div class="text-3xl font-bold text-yellow-500" aria-hidden="true">
              {{ stats.users.newThisWeek }}
            </div>
            <div class="text-secondary-400 text-sm">{{ t('admin.dashboard.users.thisWeek') }}</div>
            <span class="sr-only">{{
              t('admin.dashboard.users.thisWeekSrOnly', { count: stats.users.newThisWeek })
            }}</span>
          </article>
          <article class="stats-card">
            <div class="text-3xl font-bold text-blue-500" aria-hidden="true">
              {{ stats.users.admins }}
            </div>
            <div class="text-secondary-400 text-sm">{{ t('admin.dashboard.users.admins') }}</div>
            <span class="sr-only">{{
              t('admin.dashboard.users.adminsSrOnly', { count: stats.users.admins })
            }}</span>
          </article>
        </div>
      </section>

      <section aria-labelledby="audit-stats-heading">
        <h3 id="audit-stats-heading" class="text-lg font-semibold text-secondary-50 mb-4">
          {{ t('admin.dashboard.audit.heading') }}
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <article class="stats-card">
            <div class="text-3xl font-bold text-primary-500" aria-hidden="true">
              {{ stats.audit.total }}
            </div>
            <div class="text-secondary-400 text-sm">{{ t('admin.dashboard.audit.total') }}</div>
            <span class="sr-only">{{
              t('admin.dashboard.audit.totalSrOnly', { count: stats.audit.total })
            }}</span>
          </article>
          <article class="stats-card">
            <div class="text-3xl font-bold text-blue-500" aria-hidden="true">
              {{ stats.audit.today }}
            </div>
            <div class="text-secondary-400 text-sm">{{ t('admin.dashboard.audit.today') }}</div>
            <span class="sr-only">{{
              t('admin.dashboard.audit.todaySrOnly', { count: stats.audit.today })
            }}</span>
          </article>
          <article class="stats-card">
            <div class="text-3xl font-bold text-yellow-500" aria-hidden="true">
              {{ stats.audit.warningsToday }}
            </div>
            <div class="text-secondary-400 text-sm">{{ t('admin.dashboard.audit.warnings') }}</div>
            <span class="sr-only">{{
              t('admin.dashboard.audit.warningsSrOnly', { count: stats.audit.warningsToday })
            }}</span>
          </article>
          <article class="stats-card">
            <div class="text-3xl font-bold text-red-500" aria-hidden="true">
              {{ stats.audit.failedLoginsToday }}
            </div>
            <div class="text-secondary-400 text-sm">
              {{ t('admin.dashboard.audit.failedLogins') }}
            </div>
            <span class="sr-only">{{
              t('admin.dashboard.audit.failedLoginsSrOnly', {
                count: stats.audit.failedLoginsToday,
              })
            }}</span>
          </article>
        </div>
      </section>

      <section aria-labelledby="recent-activity-heading">
        <h3 id="recent-activity-heading" class="text-lg font-semibold text-secondary-50 mb-4">
          {{ t('admin.dashboard.recentActivity.heading') }}
        </h3>
        <AccessibleTable
          :columns="activityColumns"
          :data="recentActivity"
          :caption="t('admin.dashboard.recentActivity.caption')"
          :empty-message="t('admin.dashboard.recentActivity.emptyMessage')"
          row-key="id"
        >
          <template #cell-action="{ value }">
            <span
              class="inline-block px-2 py-1 text-xs rounded"
              :class="getActionClass(String(value))"
            >
              {{ value }}
            </span>
          </template>
          <template #cell-performer="{ row }">
            {{
              (row.performer as { pseudo?: string })?.pseudo ||
              t('admin.dashboard.recentActivity.systemUser')
            }}
          </template>
          <template #cell-createdAt="{ value }">
            {{ formatDate(String(value)) }}
          </template>
        </AccessibleTable>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi, type DashboardStats, type AuditLogEntry } from '@/services/api/adminApi'
import { AccessibleTable, type TableColumn } from '@/components/a11y'

const { t, locale } = useI18n()

const isLoading = ref(true)
const error = ref<string | null>(null)
const stats = ref<DashboardStats | null>(null)
const recentActivity = ref<AuditLogEntry[]>([])

const activityColumns = computed<TableColumn[]>(() => [
  { key: 'action', label: t('admin.dashboard.columns.action') },
  { key: 'performer', label: t('admin.dashboard.columns.user') },
  { key: 'createdAt', label: t('admin.dashboard.columns.date') },
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

const loadData = async () => {
  isLoading.value = true
  error.value = null

  try {
    const [statsData, activityData] = await Promise.all([
      adminApi.dashboard.getStats(),
      adminApi.dashboard.getRecentActivity(),
    ])
    stats.value = statsData
    recentActivity.value = activityData
  } catch (err) {
    error.value = err instanceof Error ? err.message : t('admin.dashboard.errorLoading')
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<style scoped>
.stats-card {
  @apply bg-secondary-800 rounded-lg p-4 border border-secondary-700;
}
</style>
