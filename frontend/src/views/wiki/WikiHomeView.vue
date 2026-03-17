<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useWikiNavigationStore } from '@/stores/wiki/useWikiNavigationStore'
import { useWikiFavoritesStore } from '@/stores/wiki/useWikiFavoritesStore'
import { useAuthStore } from '@/stores/auth'
import WikiCategoryCard from '@/components/wiki/WikiCategoryCard.vue'
import DashboardNav from '@/components/dashboard/DashboardNav.vue'
import { MagnifyingGlassIcon, StarIcon } from '@heroicons/vue/24/outline'
import { MIN_SEARCH_LENGTH } from '@/constants/wiki'

const { t } = useI18n()

const router = useRouter()
const navigationStore = useWikiNavigationStore()
const favoritesStore = useWikiFavoritesStore()
const authStore = useAuthStore()

const searchQuery = ref('')

onMounted(() => {
  navigationStore.fetchCategories()
  if (authStore.isAuthenticated) {
    favoritesStore.fetchFavorites()
  }
})

function handleSearch() {
  if (searchQuery.value.trim().length < MIN_SEARCH_LENGTH) return
  router.push({
    name: 'wiki.category',
    params: { category: 'spells' },
    query: { search: searchQuery.value },
  })
}

function goToFavorites() {
  router.push({ name: 'wiki.category', params: { category: 'favorites' } })
}
</script>

<template>
  <div class="min-h-screen bg-secondary-900">
    <DashboardNav />
    <div
      class="bg-gradient-to-r from-primary-900 via-primary-800 to-primary-900 border-b border-secondary-700 px-4 py-8 sm:py-12"
    >
      <div class="max-w-4xl mx-auto text-center">
        <h1 class="text-3xl sm:text-4xl font-bold text-primary-400 mb-2">
          {{ t('wiki.home.title') }}
        </h1>
        <p class="text-secondary-400 mb-6">{{ t('wiki.home.subtitle') }}</p>

        <div class="relative max-w-xl mx-auto">
          <MagnifyingGlassIcon
            class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-secondary-400"
          />
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="t('wiki.home.searchPlaceholder')"
            class="w-full pl-10 pr-4 py-3 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-100 placeholder-secondary-500 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500"
            @keydown.enter="handleSearch"
          />
          <button
            class="absolute right-2 top-1/2 -translate-y-1/2 px-3 py-1.5 bg-primary-600 hover:bg-primary-500 text-white text-sm rounded transition-colors"
            @click="handleSearch"
          >
            {{ t('wiki.home.searchButton') }}
          </button>
        </div>
      </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 py-8">
      <div
        v-if="authStore.isAuthenticated"
        class="mb-6 p-4 bg-primary-900/30 border border-primary-700/50 rounded-lg flex items-center justify-between cursor-pointer hover:bg-primary-900/50 transition-colors"
        @click="goToFavorites"
      >
        <div class="flex items-center gap-3">
          <StarIcon class="w-6 h-6 text-primary-400" />
          <div>
            <p class="font-medium text-primary-300">{{ t('wiki.home.favoritesTitle') }}</p>
            <p class="text-sm text-secondary-400">{{ t('wiki.home.favoritesDescription') }}</p>
          </div>
        </div>
        <span class="text-secondary-400 text-sm">{{ t('wiki.home.favoritesViewAll') }}</span>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <WikiCategoryCard
          v-for="category in navigationStore.categories"
          :key="category.slug"
          :category="category"
        />
      </div>

      <div
        v-if="!navigationStore.categories.length"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
      >
        <div v-for="i in 7" :key="i" class="h-28 bg-secondary-800 rounded-lg animate-pulse" />
      </div>
    </div>
  </div>
</template>
