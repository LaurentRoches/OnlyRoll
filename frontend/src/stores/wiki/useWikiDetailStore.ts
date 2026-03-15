import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import i18n from '@/i18n'
import { wikiApi } from '@/services/api/wikiApi'
import type { WikiCategorySlug } from '@/constants/wiki'

export const useWikiDetailStore = defineStore('wikiDetail', () => {
  const currentItem = ref<unknown>(null)
  const currentCategory = ref<WikiCategorySlug | null>(null)
  const currentId = ref<number | null>(null)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function fetchItem(category: WikiCategorySlug, id: number): Promise<void> {
    currentCategory.value = category
    currentId.value = id
    isLoading.value = true
    error.value = null
    currentItem.value = null

    try {
      currentItem.value = await _fetchOne(category, id)
    } catch (e: unknown) {
      error.value = e instanceof Error ? e.message : i18n.global.t('wiki.errors.loadingError')
    } finally {
      isLoading.value = false
    }
  }

  async function _fetchOne(category: WikiCategorySlug, id: number) {
    switch (category) {
      case 'spells':     return wikiApi.getSpell(id)
      case 'races':      return wikiApi.getRace(id)
      case 'classes':    return wikiApi.getClass(id)
      case 'items':      return wikiApi.getItem(id)
      case 'monsters':   return wikiApi.getMonster(id)
      case 'backgrounds':return wikiApi.getBackground(id)
      case 'feats':      return wikiApi.getFeat(id)
      default:           throw new Error(`Unknown category: ${category}`)
    }
  }

  watch(() => i18n.global.locale.value, () => {
    if (currentCategory.value && currentId.value) {
      fetchItem(currentCategory.value, currentId.value)
    }
  })

  function $reset() {
    currentItem.value = null
    currentCategory.value = null
    currentId.value = null
    isLoading.value = false
    error.value = null
  }

  return { currentItem, isLoading, error, fetchItem, $reset }
})
