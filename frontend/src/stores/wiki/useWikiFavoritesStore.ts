import { defineStore } from 'pinia'
import { ref } from 'vue'
import { wikiCommonApi } from '@/services/api/wiki/wikiCommonApi'
import { useAuthStore } from '@/stores/auth'

export const useWikiFavoritesStore = defineStore('wikiFavorites', () => {
  const authStore = useAuthStore()

  const favorites = ref<Set<string>>(new Set())

  async function fetchFavorites(): Promise<void> {
    if (!authStore.isAuthenticated) return

    try {
      const data = await wikiCommonApi.getFavorites()
      favorites.value = new Set()
      for (const [table, entries] of Object.entries(data.favorites)) {
        for (const entry of entries) {
          favorites.value.add(`${table}:${entry.srdId}`)
        }
      }
    } catch (e) {
      console.error('Failed to fetch favorites', e)
    }
  }

  async function toggleFavorite(srdTable: string, srdId: number): Promise<void> {
    if (!authStore.isAuthenticated) return

    const key = `${srdTable}:${srdId}`
    const wasFavorited = favorites.value.has(key)

    if (wasFavorited) {
      favorites.value.delete(key)
    } else {
      favorites.value.add(key)
    }

    try {
      if (wasFavorited) {
        await wikiCommonApi.removeFavorite(srdTable, srdId)
      } else {
        await wikiCommonApi.addFavorite(srdTable, srdId)
      }
    } catch (e) {
      if (wasFavorited) {
        favorites.value.add(key)
      } else {
        favorites.value.delete(key)
      }
      console.error('Failed to toggle favorite', e)
    }
  }

  function isFavorited(srdTable: string, srdId: number): boolean {
    return favorites.value.has(`${srdTable}:${srdId}`)
  }

  return { favorites, fetchFavorites, toggleFavorite, isFavorited }
})
