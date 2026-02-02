<template>
  <div class="admin-users">
    <!-- Page header -->
    <header class="mb-6">
      <h2 class="text-2xl font-bold text-secondary-50">Gestion des utilisateurs</h2>
      <p class="text-secondary-400 mt-1">{{ meta.total }} utilisateurs au total</p>
    </header>

    <!-- Filters - RGAA 11.1, 11.2 -->
    <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700 mb-6">
      <form @submit.prevent class="flex flex-wrap gap-4" role="search" aria-label="Filtrer les utilisateurs">
        <!-- Recherche -->
        <div class="flex flex-col">
          <label for="search-users" class="sr-only">Rechercher un utilisateur</label>
          <input
            id="search-users"
            v-model="filters.search"
            type="search"
            placeholder="Rechercher..."
            autocomplete="off"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @input="debouncedSearch"
          />
        </div>

        <!-- Filtre statut -->
        <div class="flex flex-col">
          <label for="filter-status" class="sr-only">Filtrer par statut</label>
          <select
            id="filter-status"
            v-model="filters.status"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @change="loadUsers"
          >
            <option value="all">Tous les statuts</option>
            <option value="active">Actifs</option>
            <option value="inactive">Inactifs</option>
            <option value="deleted">Supprimés</option>
            <option value="locked">Verrouillés</option>
          </select>
        </div>

        <!-- Filtre rôle -->
        <div class="flex flex-col">
          <label for="filter-role" class="sr-only">Filtrer par rôle</label>
          <select
            id="filter-role"
            v-model="filters.role"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @change="loadUsers"
          >
            <option value="">Tous les rôles</option>
            <option value="ROLE_USER">Utilisateurs</option>
            <option value="ROLE_ADMIN">Administrateurs</option>
          </select>
        </div>
      </form>
    </div>

    <!-- Loading state -->
    <div v-if="isLoading" class="flex justify-center py-12" role="status" aria-label="Chargement en cours">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500" aria-hidden="true"></div>
      <span class="sr-only">Chargement des utilisateurs...</span>
    </div>

    <!-- Error state -->
    <div
      v-else-if="error"
      role="alert"
      class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-400"
    >
      <strong class="font-semibold">Erreur :</strong> {{ error }}
    </div>

    <!-- Users Table -->
    <template v-else>
      <AccessibleTable
        :columns="usersColumns"
        :data="users"
        caption="Liste des utilisateurs avec leurs informations et actions disponibles"
        empty-message="Aucun utilisateur trouvé"
        row-key="id"
        sortable
        :initial-sort-column="filters.sortBy"
        :initial-sort-direction="filters.sortDirection"
        @sort="handleSort"
      >
        <!-- ID -->
        <template #cell-id="{ value }">
          <span class="text-secondary-400 font-mono text-xs">{{ value }}</span>
        </template>

        <!-- Pseudo -->
        <template #cell-pseudo="{ value }">
          <span class="font-medium text-secondary-200">{{ value }}</span>
        </template>

        <!-- Email -->
        <template #cell-email="{ value }">
          <span class="text-secondary-300">{{ value }}</span>
        </template>

        <!-- Rôles -->
        <template #cell-roles="{ row }">
          <span
            v-for="role in (row.roles as string[])"
            :key="role"
            class="inline-block px-2 py-1 text-xs rounded mr-1"
            :class="{
              'bg-primary-500/20 text-primary-400': role === 'ROLE_ADMIN',
              'bg-secondary-600 text-secondary-300': role === 'ROLE_USER',
            }"
          >
            {{ role.replace('ROLE_', '') }}
          </span>
        </template>

        <!-- Statut -->
        <template #cell-status="{ row }">
          <span
            class="inline-block px-2 py-1 text-xs rounded"
            :class="getStatusClass(row)"
          >
            {{ getStatusLabel(row) }}
          </span>
        </template>

        <!-- Dernière connexion -->
        <template #cell-lastLogin="{ value }">
          <span class="text-secondary-400">
            {{ value ? formatDate(String(value)) : 'Jamais' }}
          </span>
        </template>

        <!-- Actions -->
        <template #cell-actions="{ row }">
          <div class="flex justify-end space-x-2">
            <button
              v-if="row.deletedAt"
              type="button"
              class="px-2 py-1 text-xs bg-green-500/20 text-green-400 hover:bg-green-500/30 rounded transition-colors focus:outline-none focus:ring-2 focus:ring-green-500"
              :aria-label="`Restaurer l'utilisateur ${row.pseudo}`"
              @click="restoreUser(row.id as number)"
            >
              Restaurer
            </button>
            <button
              v-else
              type="button"
              class="px-2 py-1 text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
              :aria-label="`Supprimer l'utilisateur ${row.pseudo}`"
              @click="confirmDelete(row)"
            >
              Supprimer
            </button>
          </div>
        </template>
      </AccessibleTable>

      <!-- Pagination -->
      <div v-if="meta.totalPages > 1" class="mt-4">
        <AccessiblePagination
          :current-page="meta.page"
          :total-pages="meta.totalPages"
          :total="meta.total"
          :per-page="meta.limit"
          aria-label="Pagination des utilisateurs"
          @page-change="goToPage"
        />
      </div>
    </template>

    <!-- Confirmation Modal -->
    <AccessibleModal
      :is-open="showDeleteModal"
      :title="`Supprimer ${userToDelete?.pseudo || 'l\'utilisateur'} ?`"
      @close="showDeleteModal = false"
    >
      <p class="text-secondary-300">
        Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est réversible
        (soft delete).
      </p>

      <template #footer="{ close }">
        <button
          type="button"
          class="px-4 py-2 bg-secondary-700 hover:bg-secondary-600 text-secondary-200 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-secondary-500"
          @click="close"
        >
          Annuler
        </button>
        <button
          type="button"
          class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
          @click="deleteUser"
        >
          Supprimer
        </button>
      </template>
    </AccessibleModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { adminApi, type AdminUser, type UserFilterParams } from '@/services/api/adminApi'
import { AccessibleTable, AccessibleModal, AccessiblePagination, type TableColumn } from '@/components/a11y'

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
  sortBy: 'id',
  sortDirection: 'ASC',
})

const showDeleteModal = ref(false)
const userToDelete = ref<AdminUser | null>(null)

let searchTimeout: ReturnType<typeof setTimeout> | null = null

const usersColumns: TableColumn[] = [
  { key: 'id', label: 'ID', sortable: true },
  { key: 'pseudo', label: 'Pseudo', sortable: true },
  { key: 'email', label: 'Email', sortable: true },
  { key: 'roles', label: 'Rôles', sortable: false },
  { key: 'status', label: 'Statut', sortable: false },
  { key: 'lastLogin', label: 'Dernière connexion', sortable: true },
  { key: 'actions', label: 'Actions', align: 'right', sortable: false },
]

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}

const getStatusClass = (user: Record<string, unknown>): string => {
  if (user.deletedAt) return 'bg-red-500/20 text-red-400'
  if (user.lockedUntil) return 'bg-yellow-500/20 text-yellow-400'
  if (user.isActive) return 'bg-green-500/20 text-green-400'
  return 'bg-secondary-600 text-secondary-400'
}

const getStatusLabel = (user: Record<string, unknown>): string => {
  if (user.deletedAt) return 'Supprimé'
  if (user.lockedUntil) return 'Verrouillé'
  if (user.isActive) return 'Actif'
  return 'Inactif'
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

const handleSort = (column: string, direction: 'ASC' | 'DESC') => {
  filters.sortBy = column as UserFilterParams['sortBy']
  filters.sortDirection = direction
  loadUsers()
}

const goToPage = (page: number) => {
  filters.page = page
  loadUsers()
}

const confirmDelete = (user: Record<string, unknown>) => {
  userToDelete.value = user as AdminUser
  showDeleteModal.value = true
}

const deleteUser = async () => {
  if (!userToDelete.value) return

  try {
    await adminApi.users.delete(userToDelete.value.id)
    showDeleteModal.value = false
    userToDelete.value = null
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
