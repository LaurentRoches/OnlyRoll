<template>
  <section ref="sectionRef" class="py-24 relative overflow-hidden">
    <div
      class="absolute inset-0 pointer-events-none"
      aria-hidden="true"
      style="
        background: radial-gradient(
          ellipse 80% 60% at 50% 50%,
          rgba(99, 102, 241, 0.08) 0%,
          transparent 70%
        );
      "
    ></div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
      <h2
        class="text-4xl md:text-5xl font-bold text-secondary-50 mb-6 text-shadow-stronger reveal-up"
        :class="{ revealed: isVisible }"
      >
        {{ t('landing.cta.title') }}
      </h2>
      <p
        class="text-xl text-secondary-400 mb-10 reveal-up"
        :class="{ revealed: isVisible }"
        style="transition-delay: 150ms"
      >
        {{ t('landing.cta.subtitle') }}
      </p>

      <div class="reveal-scale" :class="{ revealed: isVisible }" style="transition-delay: 300ms">
        <RouterLink
          to="/auth/register"
          class="inline-flex items-center gap-3 px-10 py-5 bg-primary-500 hover:bg-primary-600 text-white text-xl font-bold rounded-xl transition-all duration-200 hover:-translate-y-1 hover:shadow-purple-lg cta-glow"
        >
          {{ t('landing.cta.button') }}
          <svg
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 8l4 4m0 0l-4 4m4-4H3"
            />
          </svg>
        </RouterLink>
      </div>

      <p
        class="mt-6 text-secondary-500 text-sm reveal-up"
        :class="{ revealed: isVisible }"
        style="transition-delay: 450ms"
      >
        {{ t('landing.cta.footer') }}
      </p>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import { useScrollReveal } from '@/composables/useScrollReveal'

const { t } = useI18n()
const sectionRef = ref<HTMLElement>()
const { isVisible } = useScrollReveal(sectionRef, { threshold: 0.3 })
</script>

<style scoped>
.cta-glow {
  animation: glow-pulse 3s ease-in-out infinite;
}
</style>
