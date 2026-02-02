import { onMounted, onUnmounted, ref } from 'vue'

/**
 * Composable pour détecter le Konami Code
 * Séquence : ↑ ↑ ↓ ↓ ← → ← → B A
 *
 * @example
 * const { isActivated, reset } = useKonamiCode(() => {
 *   console.log('Konami Code activé!')
 * })
 */
export function useKonamiCode(callback?: () => void) {
  const isActivated = ref(false)

  const konamiSequence = [
    'ArrowUp',
    'ArrowUp',
    'ArrowDown',
    'ArrowDown',
    'ArrowLeft',
    'ArrowRight',
    'ArrowLeft',
    'ArrowRight',
    (code: string) => ['KeyB', 'KeyQ'].includes(code),
    (code: string) => ['KeyA', 'KeyQ'].includes(code),
  ]

  let userSequence: string[] = []

  /**
   * Gère l'événement keydown
   */
  const handleKeydown = (event: KeyboardEvent) => {
    console.log('Touche pressée :', event.code)
    userSequence.push(event.code)

    if (userSequence.length > konamiSequence.length) {
      userSequence.shift()
    }

    const isMatch = userSequence.every((key, index) => {
      const expected = konamiSequence[index]
      if (typeof expected === 'function') {
        return expected(key)
      }
      return key === expected
    })

    if (isMatch && userSequence.length === konamiSequence.length) {
      isActivated.value = true
      callback?.()
      userSequence = []
    }
  }

  /**
   * Réinitialise l'état d'activation
   */
  const reset = () => {
    isActivated.value = false
    userSequence = []
  }

  onMounted(() => {
    window.addEventListener('keydown', handleKeydown)
  })

  onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown)
  })

  return {
    isActivated,
    reset,
  }
}
