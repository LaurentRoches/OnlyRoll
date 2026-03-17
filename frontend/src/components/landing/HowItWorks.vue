<template>
  <section ref="sectionRef" class="py-20 bg-secondary-50/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16">
        <h2
          class="text-3xl md:text-4xl font-bold text-secondary-50 mb-4 text-shadow-strong reveal-up"
          :class="{ revealed: isVisible }"
        >
          {{ t('landing.howItWorks.title') }}
        </h2>
        <p
          class="text-secondary-400 text-lg reveal-up"
          :class="{ revealed: isVisible }"
          style="transition-delay: 100ms"
        >
          {{ t('landing.howItWorks.subtitle') }}
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-5 items-start gap-8 md:gap-0">
        <div
          class="flex flex-col items-center text-center reveal-up"
          :class="{ revealed: isVisible }"
          style="transition-delay: 200ms"
        >
          <div
            class="w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold text-white mb-6"
            :class="isVisible ? 'step-circle-animate' : 'opacity-0'"
            style="background: linear-gradient(135deg, #6366f1, #818cf8)"
          >
            1
          </div>
          <h3 class="text-lg font-semibold text-secondary-50 mb-2">
            {{ t('landing.howItWorks.step1.title') }}
          </h3>
          <p class="text-secondary-400 text-sm leading-relaxed">
            {{ t('landing.howItWorks.step1.description') }}
          </p>
        </div>

        <div class="hidden md:flex items-start pt-8" aria-hidden="true">
          <div class="w-full border-t-2 border-dashed border-secondary-700 opacity-40 mt-0"></div>
        </div>

        <div
          class="flex flex-col items-center text-center reveal-up"
          :class="{ revealed: isVisible }"
          style="transition-delay: 400ms"
        >
          <div
            class="w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold text-white mb-6"
            :class="isVisible ? 'step-circle-animate' : 'opacity-0'"
            style="background: linear-gradient(135deg, #8b5cf6, #a78bfa); animation-delay: 200ms"
          >
            2
          </div>
          <h3 class="text-lg font-semibold text-secondary-50 mb-2">
            {{ t('landing.howItWorks.step2.title') }}
          </h3>
          <p class="text-secondary-400 text-sm leading-relaxed">
            {{ t('landing.howItWorks.step2.description') }}
          </p>
        </div>

        <div class="hidden md:flex items-start pt-8" aria-hidden="true">
          <div class="w-full border-t-2 border-dashed border-secondary-700 opacity-40 mt-0"></div>
        </div>

        <div
          class="flex flex-col items-center text-center reveal-up"
          :class="{ revealed: isVisible }"
          style="transition-delay: 600ms"
        >
          <div
            class="w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold text-white mb-6"
            :class="isVisible ? 'step-circle-animate' : 'opacity-0'"
            style="background: linear-gradient(135deg, #06b6d4, #38bdf8); animation-delay: 400ms"
          >
            3
          </div>
          <h3 class="text-lg font-semibold text-secondary-50 mb-2">
            {{ t('landing.howItWorks.step3.title') }}
          </h3>
          <p class="text-secondary-400 text-sm leading-relaxed">
            {{ t('landing.howItWorks.step3.description') }}
          </p>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useScrollReveal } from '@/composables/useScrollReveal'

const { t } = useI18n()
const sectionRef = ref<HTMLElement>()
const { isVisible } = useScrollReveal(sectionRef, { threshold: 0.2 })
</script>

<style scoped>
.step-circle-animate {
  animation: step-appear 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
}
</style>
