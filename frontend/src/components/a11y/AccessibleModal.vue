<template>
  <Teleport to="body">
    <Transition
      enter-active-class="transition-opacity duration-200 ease-out"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition-opacity duration-150 ease-in"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto" role="presentation">
        <!-- Overlay -->
        <div
          class="fixed inset-0 bg-black/60 backdrop-blur-sm"
          aria-hidden="true"
          @click="closeOnOverlay && close()"
        />

        <!-- Container pour centrage -->
        <div class="flex min-h-full items-center justify-center p-4">
          <!-- Modal dialog -->
          <div
            ref="modalRef"
            role="dialog"
            :aria-modal="true"
            :aria-labelledby="titleId"
            :aria-describedby="descriptionId"
            class="relative w-full bg-secondary-800 rounded-lg shadow-xl transform transition-all"
            :class="[sizeClasses]"
            @keydown.esc="close"
          >
            <!-- Header -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-secondary-700">
              <h2 :id="titleId" class="text-lg font-semibold text-secondary-50">
                {{ title }}
              </h2>
              <button
                ref="closeButtonRef"
                type="button"
                class="p-2 text-secondary-400 hover:text-secondary-200 hover:bg-secondary-700 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
                aria-label="Fermer la fenêtre de dialogue"
                @click="close"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>

            <!-- Body -->
            <div :id="descriptionId" class="px-6 py-4">
              <slot />
            </div>

            <!-- Footer -->
            <div
              v-if="$slots.footer"
              class="flex items-center justify-end gap-3 px-6 py-4 border-t border-secondary-700"
            >
              <slot name="footer" :close="close" />
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { ref, watch, nextTick, onUnmounted, useId, computed } from 'vue'

interface Props {
  isOpen: boolean
  title: string
  size?: 'sm' | 'md' | 'lg' | 'xl' | 'full'
  closeOnOverlay?: boolean
  closeOnEscape?: boolean
  initialFocus?: 'close' | 'first' | 'none'
}

const props = withDefaults(defineProps<Props>(), {
  size: 'md',
  closeOnOverlay: true,
  closeOnEscape: true,
  initialFocus: 'close',
})

const emit = defineEmits<{
  close: []
  'update:isOpen': [value: boolean]
}>()

const titleId = useId()
const descriptionId = useId()
const modalRef = ref<HTMLElement | null>(null)
const closeButtonRef = ref<HTMLButtonElement | null>(null)
const previouslyFocusedElement = ref<HTMLElement | null>(null)

const sizeClasses = computed(() => {
  const sizes: Record<string, string> = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    full: 'max-w-4xl',
  }
  return sizes[props.size] || sizes.md
})

const getFocusableElements = (): HTMLElement[] => {
  if (!modalRef.value) return []

  const focusableSelectors = [
    'button:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    'a[href]',
    '[tabindex]:not([tabindex="-1"])',
  ].join(', ')

  return Array.from(modalRef.value.querySelectorAll<HTMLElement>(focusableSelectors))
}

const handleTabKey = (event: KeyboardEvent) => {
  if (!props.isOpen) return

  const focusableElements = getFocusableElements()
  if (focusableElements.length === 0) return

  const firstElement = focusableElements[0]
  const lastElement = focusableElements[focusableElements.length - 1]

  if (event.shiftKey) {
    if (document.activeElement === firstElement) {
      event.preventDefault()
      lastElement.focus()
    }
  } else {
    if (document.activeElement === lastElement) {
      event.preventDefault()
      firstElement.focus()
    }
  }
}

const handleKeydown = (event: KeyboardEvent) => {
  if (event.key === 'Tab') {
    handleTabKey(event)
  }
  if (event.key === 'Escape' && props.closeOnEscape) {
    close()
  }
}

const close = () => {
  emit('close')
  emit('update:isOpen', false)
}

watch(
  () => props.isOpen,
  async (isOpen) => {
    if (isOpen) {
      previouslyFocusedElement.value = document.activeElement as HTMLElement

      document.body.style.overflow = 'hidden'

      document.addEventListener('keydown', handleKeydown)

      await nextTick()

      if (props.initialFocus === 'close' && closeButtonRef.value) {
        closeButtonRef.value.focus()
      } else if (props.initialFocus === 'first') {
        const focusableElements = getFocusableElements()
        if (focusableElements.length > 0) {
          focusableElements[0].focus()
        }
      }
    } else {
      document.body.style.overflow = ''

      document.removeEventListener('keydown', handleKeydown)

      if (previouslyFocusedElement.value) {
        previouslyFocusedElement.value.focus()
      }
    }
  }
)

onUnmounted(() => {
  document.body.style.overflow = ''
  document.removeEventListener('keydown', handleKeydown)
})
</script>
