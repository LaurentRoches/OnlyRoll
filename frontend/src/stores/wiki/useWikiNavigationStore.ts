import { defineStore } from 'pinia'
import { ref, watch } from 'vue'
import i18n from '@/i18n'
import { wikiCommonApi } from '@/services/api/wiki/wikiCommonApi'
import type { WikiCategory } from '@/types/wiki'

export const useWikiNavigationStore = defineStore('wikiNavigation', () => {
  const categories = ref<WikiCategory[]>([])
  const currentCategory = ref<string | null>(null)

  async function fetchCategories(): Promise<void> {
    try {
      categories.value = await wikiCommonApi.getCategories()
    } catch (e) {
      console.error('Failed to fetch wiki categories', e)
    }
  }

  watch(() => i18n.global.locale.value, () => {
    if (categories.value.length > 0) {
      fetchCategories()
    }
  })

  return { categories, currentCategory, fetchCategories }
})
