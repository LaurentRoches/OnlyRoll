<template>
  <section ref="sectionRef" class="py-16 border-y border-secondary-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
        <div
          v-for="(stat, index) in stats"
          :key="stat.label"
          class="reveal-up"
          :class="{ revealed: isVisible }"
          :style="{ transitionDelay: index * 150 + 'ms' }"
        >
          <div class="text-3xl mb-2" aria-hidden="true">{{ stat.emoji }}</div>
          <div
            class="text-4xl font-bold bg-gradient-to-r from-primary-400 to-cyan-400 bg-clip-text text-transparent mb-2"
          >
            {{ Math.round(stat.display) }}+
          </div>
          <div class="text-secondary-400 text-sm">{{ stat.label }}</div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { useTransition, TransitionPresets } from '@vueuse/core'
import { useScrollReveal } from '@/composables/useScrollReveal'

const sectionRef = ref<HTMLElement>()
const { isVisible } = useScrollReveal(sectionRef, { threshold: 0.3 })

// Valeurs cibles hardcodées (MVP — à connecter à /api/stats le moment venu)
const dicesBase = ref(0)
const gamesBase = ref(0)
const playersBase = ref(0)
const spellsBase = ref(0)

const dicesDisplay = useTransition(dicesBase, {
  duration: 2000,
  transition: TransitionPresets.easeOutExpo,
})
const gamesDisplay = useTransition(gamesBase, {
  duration: 2000,
  transition: TransitionPresets.easeOutExpo,
})
const playersDisplay = useTransition(playersBase, {
  duration: 2000,
  transition: TransitionPresets.easeOutExpo,
})
const spellsDisplay = useTransition(spellsBase, {
  duration: 2000,
  transition: TransitionPresets.easeOutExpo,
})

const stats = computed(() => [
  { emoji: '🎲', label: 'Lancers de dés', display: dicesDisplay.value },
  { emoji: '⚔️', label: 'Parties créées', display: gamesDisplay.value },
  { emoji: '👥', label: 'Aventuriers inscrits', display: playersDisplay.value },
  { emoji: '📜', label: 'Sorts dans le Wiki', display: spellsDisplay.value },
])

// Déclencher les compteurs quand la section est visible
watch(isVisible, (visible) => {
  if (!visible) return
  // Stagger les départs pour un effet cascade
  setTimeout(() => {
    dicesBase.value = 1247
  }, 0)
  setTimeout(() => {
    gamesBase.value = 83
  }, 150)
  setTimeout(() => {
    playersBase.value = 312
  }, 300)
  setTimeout(() => {
    spellsBase.value = 400
  }, 450)
})
</script>
