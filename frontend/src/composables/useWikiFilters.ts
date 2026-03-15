import { ref, watch, onUnmounted } from 'vue'

export function useWikiFilters(
  category: () => string,
  onFiltersChange: (category: string, filters: Record<string, string | number | undefined>) => void,
  debounceMs = 300,
) {
  const filters = ref<Record<string, string | number | undefined>>({})
  let debounce: ReturnType<typeof setTimeout> | null = null

  watch(filters, (newFilters) => {
    if (debounce) clearTimeout(debounce)
    debounce = setTimeout(() => {
      onFiltersChange(category(), newFilters)
    }, debounceMs)
  }, { deep: true })

  watch(category, (newCat) => {
    filters.value = {}
    onFiltersChange(newCat, {})
  })

  onUnmounted(() => {
    if (debounce) clearTimeout(debounce)
  })

  return { filters }
}
