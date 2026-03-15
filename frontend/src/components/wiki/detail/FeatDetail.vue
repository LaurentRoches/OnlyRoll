<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { FeatDetail } from '@/types/wiki'

const { t } = useI18n()

withDefaults(defineProps<{
  feat: FeatDetail
  mode?: 'page' | 'inline'
}>(), { mode: 'page' })
</script>

<template>
  <div class="space-y-4">
    <div v-if="feat.prerequisite" class="text-sm text-secondary-400">
      <span class="text-secondary-500">{{ t('wiki.featDetail.prerequisite') }} </span>{{ feat.prerequisite }}
    </div>
    <div v-if="feat.abilityModifiers?.length" class="flex flex-wrap gap-2">
      <span v-for="am in feat.abilityModifiers" :key="am.ability" class="px-2 py-1 bg-blue-900/30 text-blue-300 border border-blue-700/40 rounded text-xs uppercase">
        +{{ am.value }} {{ am.ability }}
      </span>
    </div>
    <div v-if="feat.description" class="bg-secondary-800 rounded-lg p-4 text-secondary-200 text-sm whitespace-pre-line">{{ feat.description }}</div>
  </div>
</template>
