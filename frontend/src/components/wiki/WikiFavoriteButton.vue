<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { useWikiFavoriteToggle } from '@/composables/useWikiFavoriteToggle'
import { StarIcon as StarOutline } from '@heroicons/vue/24/outline'
import { StarIcon as StarSolid } from '@heroicons/vue/24/solid'

const { t } = useI18n()

const props = defineProps<{
  srdTable: string
  srdId: number
}>()

const { isFavorited, toggle } = useWikiFavoriteToggle(props.srdTable, props.srdId)
</script>

<template>
  <button
    class="p-2 rounded-lg transition-colors"
    :class="isFavorited ? 'text-yellow-400 hover:text-yellow-300 bg-yellow-900/20' : 'text-secondary-400 hover:text-yellow-400 hover:bg-secondary-700'"
    :title="isFavorited ? t('wiki.favorite.remove') : t('wiki.favorite.add')"
    @click.prevent.stop="toggle"
  >
    <StarSolid v-if="isFavorited" class="w-5 h-5" />
    <StarOutline v-else class="w-5 h-5" />
  </button>
</template>
