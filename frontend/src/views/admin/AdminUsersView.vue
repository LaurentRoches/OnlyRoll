<template>
  <div class="admin-users">
    <header class="mb-6">
      <h2 class="text-2xl font-bold text-secondary-50">{{ t('admin.users.title') }}</h2>
      <p class="text-secondary-400 mt-1">
        {{ t('admin.users.totalUsers', { count: meta.total }) }}
      </p>
    </header>

    <div class="bg-secondary-800 rounded-lg p-4 border border-secondary-700 mb-6">
      <form
        @submit.prevent
        class="flex flex-wrap gap-4"
        role="search"
        :aria-label="t('admin.users.searchAriaLabel')"
      >
        <div class="flex flex-col">
          <label for="search-users" class="sr-only">{{ t('admin.users.searchLabel') }}</label>
          <input
            id="search-users"
            v-model="filters.search"
            type="search"
            :placeholder="t('admin.users.searchPlaceholder')"
            autocomplete="off"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @input="debouncedSearch"
          />
        </div>

        <div class="flex flex-col">
          <label for="filter-status" class="sr-only">{{
            t('admin.users.filters.statusLabel')
          }}</label>
          <select
            id="filter-status"
            v-model="filters.status"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @change="loadUsers"
          >
            <option value="all">{{ t('admin.users.filters.allStatuses') }}</option>
            <option value="active">{{ t('admin.users.filters.active') }}</option>
            <option value="inactive">{{ t('admin.users.filters.inactive') }}</option>
            <option value="deleted">{{ t('admin.users.filters.deleted') }}</option>
            <option value="locked">{{ t('admin.users.filters.locked') }}</option>
          </select>
        </div>

        <div class="flex flex-col">
          <label for="filter-role" class="sr-only">{{ t('admin.users.filters.roleLabel') }}</label>
          <select
            id="filter-role"
            v-model="filters.role"
            class="px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            @change="loadUsers"
          >
            <option value="">{{ t('admin.users.filters.allRoles') }}</option>
            <option value="ROLE_USER">{{ t('admin.users.filters.users') }}</option>
            <option value="ROLE_ADMIN">{{ t('admin.users.filters.administrators') }}</option>
          </select>
        </div>
      </form>
    </div>

    <div
      v-if="isLoading"
      class="flex justify-center py-12"
      role="status"
      :aria-label="t('admin.users.loading')"
    >
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"
        aria-hidden="true"
      ></div>
      <span class="sr-only">{{ t('admin.users.loadingUsers') }}</span>
    </div>

    <div
      v-else-if="error"
      role="alert"
      class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-400"
    >
      <strong class="font-semibold">{{ t('admin.users.errorLabel') }}</strong> {{ error }}
    </div>

    <template v-else>
      <AccessibleTable
        :columns="usersColumns"
        :data="users"
        :caption="t('admin.users.table.caption')"
        :empty-message="t('admin.users.table.emptyMessage')"
        row-key="id"
        sortable
        :initial-sort-column="filters.sortBy"
        :initial-sort-direction="filters.sortDirection"
        @sort="handleSort"
      >
        <template #cell-id="{ value }">
          <span class="text-secondary-400 font-mono text-xs">{{ value }}</span>
        </template>

        <template #cell-pseudo="{ value }">
          <span class="font-medium text-secondary-200">{{ value }}</span>
        </template>

        <template #cell-email="{ value }">
          <span class="text-secondary-300">{{ value }}</span>
        </template>

        <template #cell-roles="{ row }">
          <span
            v-for="role in row.roles as string[]"
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

        <template #cell-status="{ row }">
          <span class="inline-block px-2 py-1 text-xs rounded" :class="getStatusClass(row)">
            {{ getStatusLabel(row) }}
          </span>
        </template>

        <template #cell-lastLogin="{ value }">
          <span class="text-secondary-400">
            {{ value ? formatDate(String(value)) : t('admin.users.lastLoginNever') }}
          </span>
        </template>

        <template #cell-actions="{ row }">
          <div class="flex justify-end space-x-2">
            <button
              v-if="row.deletedAt"
              type="button"
              class="px-2 py-1 text-xs bg-green-500/20 text-green-400 hover:bg-green-500/30 rounded transition-colors focus:outline-none focus:ring-2 focus:ring-green-500"
              :aria-label="
                t('admin.users.actions.restoreAriaLabel', { pseudo: row.pseudo as string })
              "
              @click="restoreUser(row.id as number)"
            >
              {{ t('admin.users.actions.restore') }}
            </button>
            <button
              v-else
              type="button"
              class="px-2 py-1 text-xs bg-red-500/20 text-red-400 hover:bg-red-500/30 rounded transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
              :aria-label="
                t('admin.users.actions.deleteAriaLabel', { pseudo: row.pseudo as string })
              "
              @click="confirmDelete(row)"
            >
              {{ t('admin.users.actions.delete') }}
            </button>
          </div>
        </template>
      </AccessibleTable>

      <div v-if="meta.totalPages > 1" class="mt-4">
        <AccessiblePagination
          :current-page="meta.page"
          :total-pages="meta.totalPages"
          :total="meta.total"
          :per-page="meta.limit"
          :aria-label="t('admin.users.pagination.ariaLabel')"
          @page-change="goToPage"
        />
      </div>
    </template>

    <AccessibleModal
      :is-open="showDeleteModal"
      :title="
        userToDelete?.pseudo
          ? t('admin.users.deleteModal.title', { pseudo: userToDelete.pseudo })
          : t('admin.users.deleteModal.titleFallback')
      "
      @close="showDeleteModal = false"
    >
      <p class="text-secondary-300">
        {{ t('admin.users.deleteModal.message') }}
      </p>

      <template #footer="{ close }">
        <button
          type="button"
          class="px-4 py-2 bg-secondary-700 hover:bg-secondary-600 text-secondary-200 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-secondary-500"
          @click="close"
        >
          {{ t('admin.users.deleteModal.cancel') }}
        </button>
        <button
          type="button"
          class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500"
          @click="deleteUser"
        >
          {{ t('admin.users.deleteModal.confirm') }}
        </button>
      </template>
    </AccessibleModal>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { adminApi, type AdminUser, type UserFilterParams } from '@/services/api/adminApi'
import {
  AccessibleTable,
  AccessibleModal,
  AccessiblePagination,
  type TableColumn,
} from '@/components/a11y'

const { t, locale } = useI18n()

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

const usersColumns = computed<TableColumn[]>(() => [
  { key: 'id', label: t('admin.users.columns.id'), sortable: true },
  { key: 'pseudo', label: t('admin.users.columns.pseudo'), sortable: true },
  { key: 'email', label: t('admin.users.columns.email'), sortable: true },
  { key: 'roles', label: t('admin.users.columns.roles'), sortable: false },
  { key: 'status', label: t('admin.users.columns.status'), sortable: false },
  { key: 'lastLogin', label: t('admin.users.columns.lastLogin'), sortable: true },
  { key: 'actions', label: t('admin.users.columns.actions'), align: 'right', sortable: false },
])

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString(locale.value === 'fr' ? 'fr-FR' : 'en-US', {
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
  if (user.deletedAt) return t('admin.users.status.deleted')
  if (user.lockedUntil) return t('admin.users.status.locked')
  if (user.isActive) return t('admin.users.status.active')
  return t('admin.users.status.inactive')
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
    error.value = err instanceof Error ? err.message : t('admin.users.errorLoading')
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
  userToDelete.value = user as unknown as AdminUser
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
    error.value = err instanceof Error ? err.message : t('admin.users.errorDeleting')
  }
}

const restoreUser = async (id: number) => {
  try {
    await adminApi.users.restore(id)
    loadUsers()
  } catch (err) {
    error.value = err instanceof Error ? err.message : t('admin.users.errorRestoring')
  }
}

onMounted(() => {
  loadUsers()
})
</script>
