import { ref, onMounted, onUnmounted, watch, type Ref } from 'vue'

/**
 * Options pour le composable useInfiniteScroll.
 */
export interface UseInfiniteScrollOptions {
  /**
   * Marge appliquée autour du sentinel avant de déclencher le callback.
   * Permet d'anticiper le chargement avant que l'élément soit visible.
   * @default '150px'
   */
  rootMargin?: string

  /**
   * Seuil d'intersection (0 = dès qu'un pixel est visible).
   * @default 0
   */
  threshold?: number

  /**
   * Ref réactive qui désactive le déclenchement quand `true`.
   * Utiliser pour stopper le scroll infini quand il n'y a plus de données.
   */
  disabled?: Ref<boolean>
}

/**
 * Composable pour le scroll infini basé sur IntersectionObserver.
 *
 * Observe un élément sentinel et appelle un callback dès qu'il entre dans le viewport.
 * Gère automatiquement les états de chargement et évite les appels concurrents.
 * Réutilisable pour la liste des parties, le chat, le wiki, etc.
 *
 * @param sentinel - Ref vers l'élément HTML à observer (sentinel en bas ou en haut)
 * @param callback - Fonction async appelée au déclenchement (ex: charger la page suivante)
 * @param options - Options de configuration
 *
 * @example
 * // Scroll infini descendant (liste)
 * const sentinel = ref<HTMLElement | null>(null)
 * const { isLoading } = useInfiniteScroll(sentinel, loadNextPage, {
 *   disabled: computed(() => !hasMore.value)
 * })
 *
 * @example
 * // Scroll infini ascendant (chat)
 * const topSentinel = ref<HTMLElement | null>(null)
 * const { isLoading } = useInfiniteScroll(topSentinel, loadOlderMessages, {
 *   rootMargin: '50px',
 *   disabled: computed(() => !chatStore.hasMore)
 * })
 */
export function useInfiniteScroll(
  sentinel: Ref<HTMLElement | null>,
  callback: () => Promise<void>,
  options: UseInfiniteScrollOptions = {}
) {
  const { rootMargin = '150px', threshold = 0, disabled } = options

  const isLoading = ref(false)
  let observer: IntersectionObserver | null = null

  async function handleIntersection(entries: IntersectionObserverEntry[]) {
    if (!entries[0].isIntersecting) return
    if (isLoading.value) return
    if (disabled?.value) return

    isLoading.value = true
    try {
      await callback()
    } finally {
      isLoading.value = false
    }
  }

  function setup() {
    if (!sentinel.value || typeof IntersectionObserver === 'undefined') return
    observer = new IntersectionObserver(handleIntersection, { rootMargin, threshold })
    observer.observe(sentinel.value)
  }

  function cleanup() {
    observer?.disconnect()
    observer = null
  }

  onMounted(setup)
  onUnmounted(cleanup)

  watch(sentinel, (newEl, oldEl) => {
    if (oldEl) cleanup()
    if (newEl) setup()
  })

  return { isLoading }
}
