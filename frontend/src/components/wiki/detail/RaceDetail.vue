<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { ChevronDownIcon } from '@heroicons/vue/24/outline'
import type { RaceDetail, SubraceDetail } from '@/types/wiki'
import { wikiApi } from '@/services/api/wikiApi'

const { t } = useI18n()

withDefaults(defineProps<{
  race: RaceDetail
  mode?: 'page' | 'inline'
}>(), { mode: 'page' })

const selectedSubraceId = ref<number | null>(null)
const subraceDetail = ref<SubraceDetail | null>(null)
const isLoadingSubrace = ref(false)

async function toggleSubrace(id: number) {
  if (selectedSubraceId.value === id) {
    selectedSubraceId.value = null
    subraceDetail.value = null
    return
  }
  selectedSubraceId.value = id
  subraceDetail.value = null
  isLoadingSubrace.value = true
  try {
    subraceDetail.value = await wikiApi.getSubrace(id)
  } finally {
    isLoadingSubrace.value = false
  }
}
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.raceDetail.size') }}</p>
        <p class="font-bold text-secondary-100">{{ race.sizeSlug ? t('wiki.reference.creatureSize.' + race.sizeSlug) : race.size ?? '—' }}</p>
      </div>
      <div class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.raceDetail.speed') }}</p>
        <p class="font-bold text-secondary-100">{{ race.walkSpeed ? race.walkSpeed + ' ft' : '—' }}</p>
      </div>
      <div class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.raceDetail.source') }}</p>
        <p class="font-bold text-secondary-100">{{ race.source }}</p>
      </div>
    </div>

    <div v-if="race.description" class="bg-secondary-800 rounded-lg p-4 text-secondary-200 text-sm whitespace-pre-line">{{ race.description }}</div>

    <div v-if="race.traits?.length">
      <h3 class="text-sm font-medium text-secondary-300 mb-3">{{ t('wiki.raceDetail.racialTraits') }}</h3>
      <div class="space-y-3">
        <div v-for="trait in race.traits" :key="trait.name" class="bg-secondary-800 rounded-lg p-3">
          <p class="font-medium text-secondary-100 text-sm">{{ trait.name }}</p>
          <p class="text-secondary-400 text-xs mt-1">{{ trait.description }}</p>
        </div>
      </div>
    </div>

    <div v-if="race.subraces?.length">
      <h3 class="text-sm font-medium text-secondary-300 mb-2">{{ t('wiki.raceDetail.subraces') }}</h3>
      <div class="flex flex-wrap gap-2">
        <button
          v-for="sr in race.subraces"
          :key="sr.id"
          class="flex items-center gap-1 px-3 py-1.5 rounded text-sm transition-colors"
          :class="selectedSubraceId === sr.id
            ? 'bg-primary-700 text-primary-100 border border-primary-500'
            : 'bg-secondary-700 text-secondary-300 border border-secondary-600 hover:border-secondary-400 hover:text-secondary-100'"
          @click="toggleSubrace(sr.id)"
        >
          {{ sr.name }}
          <ChevronDownIcon class="w-3.5 h-3.5 transition-transform" :class="selectedSubraceId === sr.id ? 'rotate-180' : ''" />
        </button>
      </div>
      <div v-if="selectedSubraceId !== null" class="mt-3">
        <div v-if="isLoadingSubrace" class="bg-secondary-800 rounded-lg p-4 space-y-2 animate-pulse">
          <div class="h-4 w-32 bg-secondary-700 rounded" />
          <div class="h-3 w-full bg-secondary-700 rounded" />
        </div>
        <div v-else-if="subraceDetail" class="bg-secondary-800 border border-secondary-600 rounded-lg p-4 space-y-3">
          <div class="flex items-center justify-between">
            <h4 class="font-semibold text-secondary-100">{{ subraceDetail.name }}</h4>
            <span class="text-xs text-secondary-500">{{ subraceDetail.source }}</span>
          </div>
          <div v-if="subraceDetail.abilityModifiers && Object.keys(subraceDetail.abilityModifiers).length" class="flex flex-wrap gap-2">
            <span
              v-for="(val, ability) in subraceDetail.abilityModifiers"
              :key="ability"
              class="px-2 py-0.5 bg-blue-900/30 text-blue-300 border border-blue-700/40 rounded text-xs uppercase"
            >+{{ val }} {{ ability }}</span>
          </div>
          <p v-if="subraceDetail.description" class="text-secondary-300 text-sm whitespace-pre-line">{{ subraceDetail.description }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
