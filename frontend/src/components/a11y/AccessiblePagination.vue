<template>
  <nav
    role="navigation"
    :aria-label="ariaLabel"
    class="flex items-center justify-between bg-secondary-700 px-4 py-3 rounded-lg"
  >
    <!-- Info sur les résultats -->
    <div class="text-sm text-secondary-400">
      <span class="sr-only">Pagination : </span>
      <template v-if="showInfo">
        {{ startItem }} - {{ endItem }} sur {{ total }}
        <span class="sr-only">éléments</span>
      </template>
      <template v-else>
        Page {{ currentPage }} sur {{ totalPages }}
      </template>
    </div>

    <!-- Contrôles de pagination -->
    <div class="flex items-center space-x-1">
      <!-- Première page -->
      <button
        v-if="showFirstLast"
        type="button"
        class="pagination-button"
        :disabled="currentPage <= 1"
        :aria-disabled="currentPage <= 1"
        aria-label="Aller à la première page"
        @click="goToPage(1)"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
        </svg>
      </button>

      <!-- Page précédente -->
      <button
        type="button"
        class="pagination-button"
        :disabled="currentPage <= 1"
        :aria-disabled="currentPage <= 1"
        aria-label="Page précédente"
        @click="goToPage(currentPage - 1)"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
        </svg>
        <span class="sr-only sm:not-sr-only sm:ml-1">Précédent</span>
      </button>

      <!-- Numéros de pages -->
      <div v-if="showPageNumbers" class="hidden sm:flex items-center space-x-1">
        <template v-for="page in visiblePages" :key="page">
          <!-- Ellipsis -->
          <span
            v-if="page === 'ellipsis-start' || page === 'ellipsis-end'"
            class="px-2 text-secondary-500"
            aria-hidden="true"
          >
            …
          </span>

          <!-- Page number -->
          <button
            v-else
            type="button"
            class="pagination-number"
            :class="{ 'pagination-number--active': page === currentPage }"
            :aria-current="page === currentPage ? 'page' : undefined"
            :aria-label="`Page ${page}${page === currentPage ? ', page actuelle' : ''}`"
            @click="goToPage(page as number)"
          >
            {{ page }}
          </button>
        </template>
      </div>

      <!-- Sélecteur de page (mobile) -->
      <div class="sm:hidden flex items-center">
        <label :for="selectId" class="sr-only">Sélectionner une page</label>
        <select
          :id="selectId"
          :value="currentPage"
          class="bg-secondary-600 border border-secondary-500 text-secondary-200 rounded px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
          @change="handleSelectChange"
        >
          <option v-for="page in totalPages" :key="page" :value="page">
            {{ page }}
          </option>
        </select>
        <span class="ml-2 text-secondary-400 text-sm">/ {{ totalPages }}</span>
      </div>

      <!-- Page suivante -->
      <button
        type="button"
        class="pagination-button"
        :disabled="currentPage >= totalPages"
        :aria-disabled="currentPage >= totalPages"
        aria-label="Page suivante"
        @click="goToPage(currentPage + 1)"
      >
        <span class="sr-only sm:not-sr-only sm:mr-1">Suivant</span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
        </svg>
      </button>

      <!-- Dernière page -->
      <button
        v-if="showFirstLast"
        type="button"
        class="pagination-button"
        :disabled="currentPage >= totalPages"
        :aria-disabled="currentPage >= totalPages"
        aria-label="Aller à la dernière page"
        @click="goToPage(totalPages)"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
        </svg>
      </button>
    </div>

    <!-- Annonce pour les lecteurs d'écran -->
    <div
      role="status"
      aria-live="polite"
      aria-atomic="true"
      class="sr-only"
    >
      {{ announcement }}
    </div>
  </nav>
</template>

<script setup lang="ts">
import { computed, ref, useId } from 'vue'

interface Props {
  currentPage: number
  totalPages: number
  total?: number
  perPage?: number
  ariaLabel?: string
  showInfo?: boolean
  showPageNumbers?: boolean
  showFirstLast?: boolean
  siblingCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  total: 0,
  perPage: 20,
  ariaLabel: 'Pagination',
  showInfo: true,
  showPageNumbers: true,
  showFirstLast: true,
  siblingCount: 1,
})

const emit = defineEmits<{
  'update:currentPage': [page: number]
  'page-change': [page: number]
}>()

const selectId = useId()
const announcement = ref('')

const startItem = computed(() => {
  if (props.total === 0) return 0
  return (props.currentPage - 1) * props.perPage + 1
})

const endItem = computed(() => {
  return Math.min(props.currentPage * props.perPage, props.total)
})

/**
 * Calcule les pages visibles avec ellipsis
 * Algorithme similaire à celui utilisé dans usePagination
 */
const visiblePages = computed(() => {
  const pages: (number | string)[] = []
  const totalPages = props.totalPages
  const current = props.currentPage
  const siblings = props.siblingCount

  pages.push(1)

  const leftSibling = Math.max(current - siblings, 2)
  const rightSibling = Math.min(current + siblings, totalPages - 1)

  if (leftSibling > 2) {
    pages.push('ellipsis-start')
  } else if (leftSibling === 2) {
    pages.push(2)
  }

  for (let i = leftSibling; i <= rightSibling; i++) {
    if (i !== 1 && i !== totalPages) {
      pages.push(i)
    }
  }

  if (rightSibling < totalPages - 1) {
    pages.push('ellipsis-end')
  } else if (rightSibling === totalPages - 1 && totalPages > 1) {
    pages.push(totalPages - 1)
  }

  if (totalPages > 1) {
    pages.push(totalPages)
  }

  return pages
})

const goToPage = (page: number) => {
  if (page < 1 || page > props.totalPages || page === props.currentPage) {
    return
  }

  emit('update:currentPage', page)
  emit('page-change', page)

  announcement.value = `Page ${page} sur ${props.totalPages}`
  setTimeout(() => {
    announcement.value = ''
  }, 1000)
}

const handleSelectChange = (event: Event) => {
  const target = event.target as HTMLSelectElement
  goToPage(Number(target.value))
}
</script>

<style scoped lang="postcss">
.pagination-button {
  @apply flex items-center px-3 py-1.5 bg-secondary-600 text-secondary-200 rounded transition-colors;
  @apply hover:bg-secondary-500 hover:text-secondary-100;
  @apply focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-secondary-700;
  @apply disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-secondary-600;
}

.pagination-number {
  @apply w-8 h-8 flex items-center justify-center rounded text-sm text-secondary-300;
  @apply hover:bg-secondary-600 hover:text-secondary-100 transition-colors;
  @apply focus:outline-none focus:ring-2 focus:ring-primary-500;
}

.pagination-number--active {
  @apply bg-primary-600 text-white hover:bg-primary-500;
}
</style>
