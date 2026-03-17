import { ref, onMounted, onUnmounted } from 'vue'

export function useBreakpoint() {
  const isMobile = ref(true)
  const isTablet = ref(false)
  const isDesktop = ref(false)

  let mqMd: MediaQueryList
  let mqXl: MediaQueryList

  function update() {
    isDesktop.value = mqXl.matches
    isTablet.value = mqMd.matches && !mqXl.matches
    isMobile.value = !mqMd.matches
  }

  onMounted(() => {
    mqMd = window.matchMedia('(min-width: 768px)')
    mqXl = window.matchMedia('(min-width: 1280px)')
    update()
    mqMd.addEventListener('change', update)
    mqXl.addEventListener('change', update)
  })

  onUnmounted(() => {
    mqMd?.removeEventListener('change', update)
    mqXl?.removeEventListener('change', update)
  })

  return { isMobile, isTablet, isDesktop }
}
