<script setup lang="ts">
import { MapIcon, PlusIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps<{
  isGameMaster: boolean
}>()

const emit = defineEmits<{
  createMap: []
}>()
</script>

<template>
  <div
    class="h-full flex items-center justify-center bg-gradient-to-br from-secondary-900 to-secondary-800"
  >
    <div class="text-center max-w-md px-6">
      <div
        class="w-24 h-24 mx-auto mb-6 bg-primary-500/10 rounded-full flex items-center justify-center"
      >
        <MapIcon class="w-12 h-12 text-primary-500" />
      </div>

      <h3 class="text-2xl font-bold text-white mb-3">{{ t('game.emptyMap.title') }}</h3>

      <p v-if="isGameMaster" class="text-secondary-300 mb-6">
        {{ t('game.emptyMap.gmMessage') }}
      </p>

      <p v-else class="text-secondary-300 mb-6">
        {{ t('game.emptyMap.playerMessage') }}
      </p>

      <button
        v-if="isGameMaster"
        @click="emit('createMap')"
        class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors shadow-lg hover:shadow-xl"
      >
        <PlusIcon class="w-5 h-5" />
        {{ t('game.emptyMap.createButton') }}
      </button>

      <div
        v-if="isGameMaster"
        class="mt-8 p-4 bg-secondary-800/50 rounded-lg border border-secondary-700 text-left"
      >
        <h4 class="text-sm font-semibold text-primary-400 mb-2">{{ t('game.emptyMap.tips.title') }}</h4>
        <ul class="text-sm text-secondary-400 space-y-1">
          <li>{{ t('game.emptyMap.tips.formats') }}</li>
          <li>{{ t('game.emptyMap.tips.maxSize') }}</li>
          <li>{{ t('game.emptyMap.tips.resolution') }}</li>
        </ul>
      </div>
    </div>
  </div>
</template>
