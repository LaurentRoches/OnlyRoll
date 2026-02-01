<template>
  <div class="min-h-screen bg-secondary-900">
    <!-- Header -->
    <div class="bg-secondary-800 border-b border-secondary-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-secondary-50">Gestion des utilisateurs</h1>
            <p class="text-secondary-400 mt-1">{{ meta.total }} utilisateurs au total</p>
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
          <input
            v-model="filters.search"
            type="text"
            placeholder="Rechercher..."
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 placeholder-secondary-400 focus:outline-none focus:border-primary-500"
            @input="debouncedSearch"
          />
          <select
            v-model="filters.status"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:border-primary-500"
            @change="loadUsers"
          >
            <option value="all">Tous les statuts</option>
            <option value="active">Actifs</option>
            <option value="inactive">Inactifs</option>
            <option value="deleted">Supprimes</option>
            <option value="locked">Verrouilles</option>
          </select>
          <select
            v-model="filters.role"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:border-primary-500"
            @change="loadUsers"
          >
            <option value="">Tous les roles</option>
            <option value="ROLE_USER">Utilisateurs</option>
            <option value="ROLE_ADMIN">Administrateurs</option>
          </select>
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

      <!-- Users Table -->
      <div v-else class="bg-secondary-800 rounded-lg border border-secondary-700 overflow-hidden">
        <table class="w-full">
          <thead class="bg-secondary-700">
            <tr>
              <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                ID
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                Pseudo
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                Email
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                Roles
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                Statut
              </th>
              <th class="px-4 py-3 text-left text-xs font-medium text-secondary-300 uppercase">
                Derniere connexion
              </th>
              <th class="px-4 py-3 text-right text-xs font-medium text-secondary-300 uppercase">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-secondary-700">
            <tr
              v-for="user in users"
              :key="user.id"
              class="hover:bg-secondary-700/50 transition-colors"
            >
              <td class="px-4 py-3 text-sm text-secondary-400">{{ user.id }}</td>
              <td class="px-4 py-3 text-sm text-secondary-200 font-medium">{{ user.pseudo }}</td>
              <td class="px-4 py-3 text-sm text-secondary-300">{{ user.email }}</td>
              <td class="px-4 py-3">
                <span
                  v-for="role in user.roles"
                  :key="role"
                  class="inline-block px-2 py-1 text-xs rounded mr-1"
                  :class="{
                    'bg-primary-500/20 text-primary-400': role === 'ROLE_ADMIN',
                    'bg-secondary-600 text-secondary-300': role === 'ROLE_USER',
                  }"
                >
                  {{ role.replace('ROLE_', '') }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-block px-2 py-1 text-xs rounded"
                  :class="{
                    'bg-success-500/20 text-success-400': user.isActive && !user.deletedAt,
                    'bg-warning-500/20 text-warning-400': !user.isActive && !user.deletedAt,
                    'bg-danger-500/20 text-danger-400': user.deletedAt,
                  }"
                >
                  {{ user.deletedAt ? 'Supprime' : user.isActive ? 'Actif' : 'Inactif' }}
                </span>
              </td>
              <td class="px-4 py-3 text-sm text-secondary-400">
                {{ user.lastLogin ? formatDate(user.lastLogin) : 'Jamais' }}
              </td>
              <td class="px-4 py-3 text-right">
                <button
                  v-if="user.deletedAt"
                  class="text-success-400 hover:text-success-300 text-sm mr-2"
                  @click="restoreUser(user.id)"
                >
                  Restaurer
                </button>
                <button
                  v-else
                  class="text-danger-400 hover:text-danger-300 text-sm"
                  @click="deleteUser(user.id)"
                >
                  Supprimer
                </button>
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
import { adminApi, type AdminUser, type UserFilterParams } from '@/services/api/adminApi'

const isLoading = ref(true)
const error = ref<string | null>(null)
const users = ref<AdminUser[]>([])
const meta = reactive({
  total: 0,
  page: 1,
  limit: 20,
  totalPages: 0,
})

const filters = reactive<UserFilterParams>({
  search: '',
  status: 'all',
  role: undefined,
  page: 1,
  limit: 20,
})

let searchTimeout: ReturnType<typeof setTimeout> | null = null

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const loadUsers = async () => {
  isLoading.value = true
  error.value = null

  try {
    const response = await adminApi.users.list({
      ...filters,
      role: filters.role || undefined,
      status: filters.status as UserFilterParams['status'],
    })
    users.value = response.data
    Object.assign(meta, response.meta)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Erreur lors du chargement'
  } finally {
    isLoading.value = false
  }
}

const debouncedSearch = () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    filters.page = 1
    loadUsers()
  }, 300)
}

const goToPage = (page: number) => {
  filters.page = page
  loadUsers()
}

const deleteUser = async (id: number) => {
  if (!confirm('Etes-vous sur de vouloir supprimer cet utilisateur ?')) return

  try {
    await adminApi.users.delete(id)
    loadUsers()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Erreur lors de la suppression'
  }
}

const restoreUser = async (id: number) => {
  try {
    await adminApi.users.restore(id)
    loadUsers()
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Erreur lors de la restauration'
  }
}

onMounted(() => {
  loadUsers()
})
</script>
