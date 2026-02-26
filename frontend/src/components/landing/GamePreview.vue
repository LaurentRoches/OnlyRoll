<template>
  <section ref="sectionRef" class="py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
      <!-- Titre -->
      <h2
        class="text-3xl md:text-4xl font-bold text-secondary-50 mb-4 text-shadow-strong reveal-up"
        :class="{ revealed: isVisible }"
      >
        Plongez dans l'aventure
      </h2>
      <p
        class="text-lg text-secondary-400 mb-12 text-shadow-strong reveal-up"
        :class="{ revealed: isVisible }"
        style="transition-delay: 100ms"
      >
        Une interface pensée pour le jeu, pas contre le jeu
      </p>

      <!-- Conteneur de l'image avec effet tilt 3D -->
      <div
        class="relative inline-block reveal-scale"
        :class="{ revealed: isVisible }"
        style="transition-delay: 200ms"
      >
        <!-- Cadre décoratif gradient -->
        <div
          class="relative p-[2px] rounded-2xl max-w-6xl mx-auto"
          style="background: linear-gradient(135deg, #6366f1, #38bdf8, #8b5cf6)"
        >
          <!-- Glow ambient pulsant -->
          <div
            class="absolute inset-0 rounded-2xl blur-xl opacity-40"
            style="background: linear-gradient(135deg, #6366f1, #38bdf8, #8b5cf6)"
            aria-hidden="true"
          ></div>

          <!-- Image avec tilt 3D -->
          <div
            ref="imageContainerRef"
            class="relative rounded-2xl overflow-hidden"
            :style="tiltStyle"
          >
            <img
              src="/images/InGame_Exemple.png"
              alt="Aperçu de l'interface de jeu OnlyRoll"
              class="w-full h-full object-cover block"
            />
          </div>
        </div>

        <!-- Floating badges autour de l'image -->
        <div
          class="absolute -bottom-4 -left-4 md:-left-8 bg-secondary-800 border border-secondary-700 rounded-xl px-3 py-2 flex items-center gap-2 shadow-lg reveal-up"
          :class="{ revealed: isVisible }"
          style="transition-delay: 500ms"
        >
          <span aria-hidden="true">🎲</span>
          <span class="text-secondary-200 text-sm font-medium">Dés en temps réel</span>
        </div>

        <div
          class="absolute -top-4 -right-4 md:-right-8 bg-secondary-800 border border-secondary-700 rounded-xl px-3 py-2 flex items-center gap-2 shadow-lg reveal-up"
          :class="{ revealed: isVisible }"
          style="transition-delay: 650ms"
        >
          <span aria-hidden="true">💬</span>
          <span class="text-secondary-200 text-sm font-medium">Chat intégré</span>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useMouseInElement } from '@vueuse/core'
import { useScrollReveal } from '@/composables/useScrollReveal'

const sectionRef = ref<HTMLElement>()
const { isVisible } = useScrollReveal(sectionRef, { threshold: 0.15 })

const imageContainerRef = ref<HTMLElement>()
const { elementX, elementY, isOutside, elementWidth, elementHeight } =
  useMouseInElement(imageContainerRef)

// Désactiver le tilt sur les appareils touch / petits écrans
const isTouchDevice = ref(false)
onMounted(() => {
  isTouchDevice.value = window.matchMedia('(hover: none)').matches
})

const tiltStyle = computed(() => {
  if (isOutside.value || isTouchDevice.value) {
    return {
      transform: 'perspective(1000px) rotateX(0deg) rotateY(0deg)',
      transition: 'transform 0.4s ease-out',
    }
  }
  const rotateX = (elementY.value / elementHeight.value - 0.5) * -6
  const rotateY = (elementX.value / elementWidth.value - 0.5) * 6
  return {
    transform: `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg)`,
    transition: 'transform 0.1s ease-out',
  }
})
</script>
