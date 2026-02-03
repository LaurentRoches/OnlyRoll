<template>
  <div class="admin-dashboard">
    <!-- Page header -->
    <header class="mb-8">
      <h2 class="text-2xl font-bold text-secondary-50">Tableau de bord</h2>
      <p class="text-secondary-400 mt-1">Vue d'ensemble de l'administration</p>
    </header>

    <!-- Loading state -->
    <div
      v-if="isLoading"
      class="flex justify-center py-12"
      role="status"
      aria-label="Chargement en cours"
    >
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"
        aria-hidden="true"
      ></div>
      <span class="sr-only">Chargement des données...</span>
    </div>

    <!-- Error state -->
    <div
      v-else-if="error"
      role="alert"
      class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-400"
    >
      <strong class="font-semibold">Erreur :</strong> {{ error }}
    </div>

    <!-- Stats Cards -->
    <div v-else-if="stats" class="space-y-8">
      <!-- Users Stats Section -->
      <section aria-labelledby="users-stats-heading">
        <h3 id="users-stats-heading" class="text-lg font-semibold text-secondary-50 mb-4">
          Utilisateurs
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <article class="stats-card">
            <div class="text-3xl font-bold text-primary-500" aria-hidden="true">
              {{ stats.users.total }}
            </div>
            <div class="text-secondary-400 text-sm">Total</div>
            <span class="sr-only">{{ stats.users.total }} utilisateurs au total</span>
          </article>
          <article class="stats-card">
            <div class="text-3xl font-bold text-green-500" aria-hidden="true">
              {{ stats.users.active }}
            </div>
            <div class="text-secondary-400 text-sm">Actifs</div>
            <span class="sr-only">{{ stats.users.active }} utilisateurs actifs</span>
          </article>
          <article class="stats-card">
            <div class="text-3xl font-bold text-yellow-500" aria-hidden="true">
              {{ stats.users.newThisWeek }}
            </div>
            <div class="text-secondary-400 text-sm">Cette semaine</div>
            <span class="sr-only"
              >{{ stats.users.newThisWeek }} nouveaux utilisateurs cette semaine</span
            >
          </article>
          <article class="stats-card">
            <div class="text-3xl font-bold text-blue-500" aria-hidden="true">
              {{ stats.users.admins }}
            </div>
            <div class="text-secondary-400 text-sm">Administrateurs</div>
            <span class="sr-only">{{ stats.users.admins }} administrateurs</span>
          </article>
        </div>
      </section>

      <!-- Audit Stats Section -->
      <section aria-labelledby="audit-stats-heading">
        <h3 id="audit-stats-heading" class="text-lg font-semibold text-secondary-50 mb-4">Audit</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <article class="stats-card">
            <div class="text-3xl font-bold text-primary-500" aria-hidden="true">
              {{ stats.audit.total }}
            </div>
            <div class="text-secondary-400 text-sm">Total</div>
            <span class="sr-only">{{ stats.audit.total }} événements d'audit au total</span>
          </article>
          <article class="stats-card">
            <div class="text-3xl font-bold text-blue-500" aria-hidden="true">
              {{ stats.audit.today }}
            </div>
            <div class="text-secondary-400 text-sm">Aujourd'hui</div>
            <span class="sr-only">{{ stats.audit.today }} événements aujourd'hui</span>
          </article>
          <article class="stats-card">
            <div class="text-3xl font-bold text-yellow-500" aria-hidden="true">
              {{ stats.audit.warningsToday }}
            </div>
            <div class="text-secondary-400 text-sm">Warnings</div>
            <span class="sr-only">{{ stats.audit.warningsToday }} warnings aujourd'hui</span>
          </article>
          <article class="stats-card">
            <div class="text-3xl font-bold text-red-500" aria-hidden="true">
              {{ stats.audit.failedLoginsToday }}
            </div>
            <div class="text-secondary-400 text-sm">Échecs login</div>
            <span class="sr-only"
              >{{ stats.audit.failedLoginsToday }} échecs de connexion aujourd'hui</span
            >
          </article>
        </div>
      </section>

      <!-- Recent Activity Section -->
      <section aria-labelledby="recent-activity-heading">
        <h3 id="recent-activity-heading" class="text-lg font-semibold text-secondary-50 mb-4">
          Activité récente
        </h3>
        <AccessibleTable
          :columns="activityColumns"
          :data="recentActivity"
          caption="Dernières actions enregistrées dans le système"
          empty-message="Aucune activité récente"
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
            {{ (row.performer as { pseudo?: string })?.pseudo || 'Système' }}
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
import { ref, onMounted } from 'vue'
import { adminApi, type DashboardStats, type AuditLogEntry } from '@/services/api/adminApi'
import { AccessibleTable, type TableColumn } from '@/components/a11y'

const isLoading = ref(true)
const error = ref<string | null>(null)
const stats = ref<DashboardStats | null>(null)
const recentActivity = ref<AuditLogEntry[]>([])

const activityColumns: TableColumn[] = [
  { key: 'action', label: 'Action' },
  { key: 'performer', label: 'Utilisateur' },
  { key: 'createdAt', label: 'Date' },
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
    error.value = err instanceof Error ? err.message : 'Erreur lors du chargement des données'
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
