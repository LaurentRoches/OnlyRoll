<template>
  <section id="features" ref="sectionRef" class="py-20 bg-secondary-50/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16">
        <h2
          class="text-3xl md:text-4xl font-bold text-secondary-50 mb-4 text-shadow-strong reveal-up"
          :class="{ revealed: isVisible }"
        >
          {{ t('landing.features.title') }}
        </h2>
        <p
          class="text-secondary-400 text-lg reveal-up"
          :class="{ revealed: isVisible }"
          style="transition-delay: 100ms"
        >
          {{ t('landing.features.subtitle') }}
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div
          class="md:col-span-2 reveal-left"
          :class="{ revealed: isVisible }"
          style="transition-delay: 150ms"
        >
          <RouterLink
            to="/wiki"
            class="block h-full bg-secondary-800 rounded-xl p-8 border border-secondary-700 hover:border-primary-500/50 transition-all duration-300 group hover:-translate-y-1 hover:shadow-purple-lg cursor-pointer"
            style="min-height: 260px"
          >
            <div
              class="flex items-center justify-center w-20 h-20 mb-6 group-hover:scale-110 transition-transform duration-200"
            >
              <img
                src="/images/wiki/icons/wiki_icon.png"
                :alt="t('landing.features.wiki.title')"
                class="w-20 h-20 object-contain"
              />
            </div>
            <h3 class="text-xl font-bold text-secondary-50 mb-3">
              {{ t('landing.features.wiki.title') }}
            </h3>
            <p class="text-secondary-400 leading-relaxed">
              {{ t('landing.features.wiki.description') }}
            </p>
          </RouterLink>
        </div>

        <div class="flex flex-col gap-6">
          <div class="reveal-up" :class="{ revealed: isVisible }" style="transition-delay: 250ms">
            <div
              class="bg-secondary-800 rounded-xl p-6 border border-secondary-700 hover:border-emerald-500/50 transition-all duration-300 group hover:-translate-y-1 hover:shadow-lg"
            >
              <div
                class="flex items-center justify-center w-16 h-16 mb-4 group-hover:scale-110 transition-transform duration-200"
              >
                <img
                  src="/images/wiki/icons/character_sheet_icon.png"
                  :alt="t('landing.features.characterSheet.title')"
                  class="w-16 h-16 object-contain"
                />
              </div>
              <h3 class="text-base font-semibold text-secondary-50 mb-1">
                {{ t('landing.features.characterSheet.title') }}
              </h3>
              <p class="text-secondary-400 text-sm leading-relaxed">
                {{ t('landing.features.characterSheet.description') }}
              </p>
            </div>
          </div>

          <div class="reveal-up" :class="{ revealed: isVisible }" style="transition-delay: 350ms">
            <div
              class="bg-secondary-800 rounded-xl p-6 border border-secondary-700 hover:border-cyan-500/50 transition-all duration-300 group hover:-translate-y-1 hover:shadow-lg"
            >
              <div
                class="flex items-center justify-center w-16 h-16 mb-4 group-hover:scale-110 transition-transform duration-200"
              >
                <img
                  src="/images/wiki/icons/chat_icon.png"
                  :alt="t('landing.features.chat.title')"
                  class="w-16 h-16 object-contain"
                />
              </div>
              <h3 class="text-base font-semibold text-secondary-50 mb-1">
                {{ t('landing.features.chat.title') }}
              </h3>
              <p class="text-secondary-400 text-sm leading-relaxed">
                {{ t('landing.features.chat.description') }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="flex flex-col gap-6">
          <div class="reveal-up" :class="{ revealed: isVisible }" style="transition-delay: 400ms">
            <div
              class="bg-secondary-800 rounded-xl p-6 border border-secondary-700 hover:border-amber-500/50 transition-all duration-300 group hover:-translate-y-1 hover:shadow-lg"
            >
              <div
                class="flex items-center justify-center w-16 h-16 mb-4 group-hover:scale-110 transition-transform duration-200"
              >
                <img
                  src="/images/wiki/icons/rolling_dice_icon.png"
                  :alt="t('landing.features.diceRoller.title')"
                  class="w-16 h-16 object-contain"
                />
              </div>
              <h3 class="text-base font-semibold text-secondary-50 mb-1">
                {{ t('landing.features.diceRoller.title') }}
              </h3>
              <p class="text-secondary-400 text-sm leading-relaxed">
                {{ t('landing.features.diceRoller.description') }}
              </p>
            </div>
          </div>

          <div class="reveal-up" :class="{ revealed: isVisible }" style="transition-delay: 500ms">
            <RouterLink
              to="/games"
              class="block bg-secondary-800 rounded-xl p-6 border border-secondary-700 hover:border-rose-500/50 transition-all duration-300 group hover:-translate-y-1 hover:shadow-lg cursor-pointer"
            >
              <div
                class="flex items-center justify-center w-16 h-16 mb-4 group-hover:scale-110 transition-transform duration-200"
              >
                <img
                  src="/images/wiki/icons/multiplayer_icon.png"
                  :alt="t('landing.features.multiplayer.title')"
                  class="w-16 h-16 object-contain"
                />
              </div>
              <h3 class="text-base font-semibold text-secondary-50 mb-1">
                {{ t('landing.features.multiplayer.title') }}
              </h3>
              <p class="text-secondary-400 text-sm leading-relaxed">
                {{ t('landing.features.multiplayer.description') }}
              </p>
            </RouterLink>
          </div>
        </div>

        <div
          class="md:col-span-2 reveal-up"
          :class="{ revealed: isVisible }"
          style="transition-delay: 300ms"
        >
          <RouterLink
            to="/games"
            class="block h-full bg-secondary-800 rounded-xl p-8 border border-secondary-700 hover:border-purple-500/50 transition-all duration-300 group hover:-translate-y-1 hover:shadow-lg cursor-pointer"
            style="min-height: 260px"
          >
            <div
              class="flex items-center justify-center w-20 h-20 mb-6 group-hover:scale-110 transition-transform duration-200"
            >
              <img
                src="/images/wiki/icons/vtt_icon.png"
                :alt="t('landing.features.virtualTable.title')"
                class="w-20 h-20 object-contain"
              />
            </div>
            <h3 class="text-xl font-bold text-secondary-50 mb-3">
              {{ t('landing.features.virtualTable.title') }}
            </h3>
            <p class="text-secondary-400 leading-relaxed">
              {{ t('landing.features.virtualTable.description') }}
            </p>
          </RouterLink>
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
const { isVisible } = useScrollReveal(sectionRef, { threshold: 0.1 })
</script>
