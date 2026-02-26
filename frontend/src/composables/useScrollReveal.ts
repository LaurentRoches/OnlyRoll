import { ref, watch, onUnmounted } from 'vue'
import type { Ref } from 'vue'

interface ScrollRevealOptions {
  threshold?: number
  delay?: number
}

export function useScrollReveal(
  target: Ref<HTMLElement | undefined | null>,
  options: ScrollRevealOptions = {},
) {
  const { threshold = 0.1, delay = 0 } = options
  const isVisible = ref(false)

  // Respecter prefers-reduced-motion : révéler immédiatement sans animation
  const prefersReducedMotion =
    typeof window !== 'undefined' &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches

  if (prefersReducedMotion) {
    isVisible.value = true
    return { isVisible }
  }

  let observer: IntersectionObserver | null = null

  const stopWatch = watch(
    target,
    (el) => {
      if (observer) {
        observer.disconnect()
        observer = null
      }
      if (!el) return

      observer = new IntersectionObserver(
        (entries) => {
          const entry = entries[0]
          if (entry.isIntersecting) {
            if (delay > 0) {
              setTimeout(() => {
                isVisible.value = true
              }, delay)
            } else {
              isVisible.value = true
            }
            observer?.unobserve(el)
          }
        },
        { threshold },
      )

      observer.observe(el)
    },
    { immediate: true },
  )

  onUnmounted(() => {
    observer?.disconnect()
    stopWatch()
  })

  return { isVisible }
}
