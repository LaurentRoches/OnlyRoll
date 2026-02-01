<template>
  <div class="min-h-screen bg-secondary-900">
    <!-- Header -->
    <div class="bg-secondary-800 border-b border-secondary-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-secondary-50">Administration</h1>
            <p class="text-secondary-400 mt-1">Dashboard de gestion</p>
          </div>
          <div class="flex space-x-4">
            <RouterLink
              to="/admin/users"
              class="px-4 py-2 bg-secondary-700 hover:bg-secondary-600 text-secondary-50 rounded-lg transition-colors"
            >
              Utilisateurs
            </RouterLink>
            <RouterLink
              to="/admin/audit-logs"
              class="px-4 py-2 bg-secondary-700 hover:bg-secondary-600 text-secondary-50 rounded-lg transition-colors"
            >
              Audit Logs
            </RouterLink>
          </div>
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

      <!-- Stats Cards -->
      <div v-else-if="stats" class="space-y-8">
        <!-- Users Stats -->
        <div>
          <h2 class="text-lg font-semibold text-secondary-50 mb-4">Utilisateurs</h2>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700">
              <div class="text-3xl font-bold text-primary-500">{{ stats.users.total }}</div>
              <div class="text-secondary-400 text-sm">Total</div>
            </div>
            <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700">
              <div class="text-3xl font-bold text-success-500">{{ stats.users.active }}</div>
              <div class="text-secondary-400 text-sm">Actifs</div>
            </div>
            <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700">
              <div class="text-3xl font-bold text-warning-500">{{ stats.users.newThisWeek }}</div>
              <div class="text-secondary-400 text-sm">Cette semaine</div>
            </div>
            <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700">
              <div class="text-3xl font-bold text-info-500">{{ stats.users.admins }}</div>
              <div class="text-secondary-400 text-sm">Administrateurs</div>
            </div>
          </div>
        </div>

        <!-- Audit Stats -->
        <div>
          <h2 class="text-lg font-semibold text-secondary-50 mb-4">Audit</h2>
          <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700">
              <div class="text-3xl font-bold text-primary-500">{{ stats.audit.total }}</div>
              <div class="text-secondary-400 text-sm">Total</div>
            </div>
            <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700">
              <div class="text-3xl font-bold text-info-500">{{ stats.audit.today }}</div>
              <div class="text-secondary-400 text-sm">Aujourd'hui</div>
            </div>
            <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700">
              <div class="text-3xl font-bold text-warning-500">{{ stats.audit.warningsToday }}</div>
              <div class="text-secondary-400 text-sm">Warnings</div>
            </div>
            <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700">
              <div class="text-3xl font-bold text-danger-500">
                {{ stats.audit.failedLoginsToday }}
              </div>
              <div class="text-secondary-400 text-sm">Echecs login</div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div>
          <h2 class="text-lg font-semibold text-secondary-50 mb-4">Activite recente</h2>
          <div class="bg-secondary-800 rounded-lg border border-secondary-700 overflow-hidden">
            <div v-if="recentActivity.length === 0" class="p-8 text-center text-secondary-400">
              Aucune activite recente
            </div>
            <table v-else class="w-full">
              <thead class="bg-secondary-700">
                <tr>
                  <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                    Action
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                    Utilisateur
                  </th>
                  <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                    Date
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-secondary-700">
                <tr
                  v-for="log in recentActivity"
                  :key="log.id"
                  class="hover:bg-secondary-700/50 transition-colors"
                >
                  <td class="px-4 py-3 text-sm text-secondary-200">{{ log.action }}</td>
                  <td class="px-4 py-3 text-sm text-secondary-300">
                    {{ log.performer?.pseudo || 'Systeme' }}
                  </td>
                  <td class="px-4 py-3 text-sm text-secondary-400">
                    {{ formatDate(log.createdAt) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { adminApi, type DashboardStats, type AuditLogEntry } from '@/services/api/adminApi'

const isLoading = ref(true)
const error = ref<string | null>(null)
const stats = ref<DashboardStats | null>(null)
const recentActivity = ref<AuditLogEntry[]>([])

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
    error.value = err instanceof Error ? err.message : 'Erreur lors du chargement des donnees'
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>
