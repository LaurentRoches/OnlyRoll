import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'
import i18n from '@/i18n'
import { wikiApi } from '@/services/api/wikiApi'
import { DEFAULT_PAGINATION_LIMIT } from '@/constants/wiki'
import type { WikiCategorySlug } from '@/constants/wiki'
import type {
  SpellListItem,
  SpellFilters,
  RaceListItem,
  RaceFilters,
  ClassListItem,
  ItemListItem,
  ItemFilters,
  MonsterListItem,
  MonsterFilters,
  BackgroundListItem,
  FeatListItem,
  FavoriteItem,
  WikiFilters,
} from '@/types/wiki'

type AnyWikiItem = SpellListItem | RaceListItem | ClassListItem | ItemListItem | MonsterListItem | BackgroundListItem | FeatListItem | FavoriteItem

export const useWikiListStore = defineStore('wikiList', () => {
  const currentCategory = ref<WikiCategorySlug | 'favorites' | null>(null)
  const items = ref<AnyWikiItem[]>([])
  const pagination = ref({ total: 0, page: 1, limit: DEFAULT_PAGINATION_LIMIT, totalPages: 0 })
  const filters = ref<WikiFilters & Record<string, unknown>>({})
  const isLoading = ref(false)
  const isLoadingMore = ref(false)
  const error = ref<string | null>(null)

  const hasMore = computed(() => pagination.value.page < pagination.value.totalPages)

  async function fetchItems(category: WikiCategorySlug | 'favorites', newFilters: Record<string, unknown> = {}): Promise<void> {
    currentCategory.value = category
    filters.value = { ...newFilters, page: 1, limit: DEFAULT_PAGINATION_LIMIT }
    items.value = []
    isLoading.value = true
    error.value = null

    try {
      const result = await _fetch(category, filters.value)
      items.value = result.data
      pagination.value = { total: result.total, page: result.page, limit: result.limit, totalPages: result.totalPages }
    } catch (e: unknown) {
      error.value = e instanceof Error ? e.message : i18n.global.t('wiki.errors.loadingError')
    } finally {
      isLoading.value = false
    }
  }

  async function appendItems(): Promise<void> {
    if (!hasMore.value || isLoadingMore.value || !currentCategory.value) return

    isLoadingMore.value = true
    try {
      const nextPage = pagination.value.page + 1
      const result = await _fetch(currentCategory.value, { ...filters.value, page: nextPage })
      items.value.push(...result.data)
      pagination.value.page = result.page
    } catch (e) {
      console.error('Failed to load more wiki items', e)
    } finally {
      isLoadingMore.value = false
    }
  }

  async function _fetch(category: WikiCategorySlug | 'favorites', params: Record<string, unknown>) {
    const f = params as Record<string, string | number | undefined>
    switch (category) {
      case 'spells':     return wikiApi.getSpells(f as SpellFilters)
      case 'races':      return wikiApi.getRaces(f as RaceFilters)
      case 'classes':    return wikiApi.getClasses(f)
      case 'items':      return wikiApi.getItems(f as ItemFilters)
      case 'monsters':   return wikiApi.getMonsters(f as MonsterFilters)
      case 'backgrounds':return wikiApi.getBackgrounds(f)
      case 'feats':      return wikiApi.getFeats(f)
      case 'favorites': {
        const data = await wikiApi.getFavoriteItems()
        return { data, total: data.length, page: 1, limit: data.length, totalPages: 1 }
      }
      default:           throw new Error(`Unknown category: ${category}`)
    }
  }

  function $reset() {
    items.value = []
    currentCategory.value = null
    pagination.value = { total: 0, page: 1, limit: DEFAULT_PAGINATION_LIMIT, totalPages: 0 }
    filters.value = {}
    isLoading.value = false
    isLoadingMore.value = false
    error.value = null
  }

  watch(() => i18n.global.locale.value, () => {
    if (currentCategory.value) {
      fetchItems(currentCategory.value, { ...filters.value })
    }
  })

  return {
    currentCategory,
    items,
    pagination,
    filters,
    isLoading,
    isLoadingMore,
    error,
    hasMore,
    fetchItems,
    appendItems,
    $reset,
  }
})
