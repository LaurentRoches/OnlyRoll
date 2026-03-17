import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useWikiFavoritesStore } from '@/stores/wiki/useWikiFavoritesStore'
import { useAuthStore } from '@/stores/auth'

export function useWikiFavoriteToggle(srdTable: string, srdId: number) {
  const router = useRouter()
  const favoritesStore = useWikiFavoritesStore()
  const authStore = useAuthStore()

  const isFavorited = computed(() => favoritesStore.isFavorited(srdTable, srdId))

  function toggle() {
    if (!authStore.isAuthenticated) {
      router.push({ name: 'login', query: { redirect: router.currentRoute.value.fullPath } })
      return
    }
    favoritesStore.toggleFavorite(srdTable, srdId)
  }

  return { isFavorited, toggle }
}
