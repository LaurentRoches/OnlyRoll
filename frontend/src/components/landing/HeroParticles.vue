<template>
  <div
    ref="containerRef"
    class="absolute inset-0 overflow-hidden pointer-events-none"
    aria-hidden="true"
  >
    <div
      :style="{
        transform: `translate(${parallaxX}px, ${parallaxY}px)`,
        transition: 'transform 0.15s ease-out',
        position: 'absolute',
        inset: '-30px',
      }"
    >
      <span
        v-for="particle in particles"
        :key="particle.id"
        class="absolute select-none"
        :style="{
          left: particle.x + '%',
          top: particle.y + '%',
          fontSize: particle.size + 'px',
          opacity: particle.opacity,
          color: particle.color,
          animationName: 'float-particle',
          animationDuration: particle.duration + 's',
          animationDelay: particle.delay + 's',
          animationTimingFunction: 'ease-in-out',
          animationIterationCount: 'infinite',
          willChange: 'transform',
        }"
      >
        {{ particle.symbol }}
      </span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useMouse } from '@vueuse/core'

const containerRef = ref<HTMLElement>()

const { x: mouseX, y: mouseY } = useMouse()

const parallaxX = computed(() => {
  if (!containerRef.value) return 0
  const rect = containerRef.value.getBoundingClientRect()
  const relX = mouseX.value - rect.left
  if (relX < 0 || relX > rect.width) return 0
  return (relX / rect.width - 0.5) * 18
})

const parallaxY = computed(() => {
  if (!containerRef.value) return 0
  const rect = containerRef.value.getBoundingClientRect()
  const relY = mouseY.value - rect.top
  if (relY < 0 || relY > rect.height) return 0
  return (relY / rect.height - 0.5) * 12
})

const symbols = ['✦', '✧', '✶', '◆', '⋆', '✴']
const colors = ['#818cf8', '#c084fc', '#38bdf8', '#a78bfa', '#e2e8f0', '#7dd3fc']

interface Particle {
  id: number
  x: number
  y: number
  symbol: string
  size: number
  opacity: number
  duration: number
  delay: number
  color: string
}

const particles = ref<Particle[]>([])

const prefersReducedMotion =
  typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches

onMounted(() => {
  if (prefersReducedMotion) return

  const count = window.innerWidth < 768 ? 8 : 15
  const generated: Particle[] = []

  for (let i = 0; i < count; i++) {
    generated.push({
      id: i,
      x: Math.random() * 100,
      y: Math.random() * 100,
      symbol: symbols[Math.floor(Math.random() * symbols.length)],
      size: 8 + Math.random() * 12,
      opacity: 0.1 + Math.random() * 0.3,
      duration: 8 + Math.random() * 12,
      delay: Math.random() * 5,
      color: colors[Math.floor(Math.random() * colors.length)],
    })
  }

  particles.value = generated
})
</script>
