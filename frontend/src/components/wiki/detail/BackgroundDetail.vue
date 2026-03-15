<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { BackgroundDetail } from '@/types/wiki'

const { t } = useI18n()

withDefaults(defineProps<{
  background: BackgroundDetail
  mode?: 'page' | 'inline'
}>(), { mode: 'page' })
</script>

<template>
  <div class="space-y-4">
    <div v-if="background.skills?.length" class="flex flex-wrap gap-2">
      <span class="text-xs text-secondary-400">{{ t('wiki.backgroundDetail.skills') }}</span>
      <span v-for="skill in background.skills" :key="skill" class="px-2 py-1 bg-secondary-700 text-secondary-300 rounded text-xs capitalize">{{ skill }}</span>
    </div>
    <div v-if="background.description" class="bg-secondary-800 rounded-lg p-4 text-secondary-200 text-sm whitespace-pre-line">{{ background.description }}</div>
    <div v-if="background.featureName">
      <h3 class="text-sm font-medium text-secondary-300 mb-2">{{ background.featureName }}</h3>
      <p class="text-secondary-400 text-sm">{{ background.featureDescription }}</p>
    </div>
    <div v-if="background.equipment?.length" class="flex flex-wrap gap-2">
      <span class="text-xs text-secondary-400">{{ t('wiki.backgroundDetail.startingEquipment') }}</span>
      <span v-for="item in background.equipment" :key="item.name" class="px-2 py-1 bg-secondary-700 text-secondary-300 rounded text-xs">
        <span v-if="item.qty > 1">{{ item.qty }}× </span>{{ item.name }}
      </span>
    </div>
  </div>
</template>
