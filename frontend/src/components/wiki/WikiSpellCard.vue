<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import WikiFavoriteButton from './WikiFavoriteButton.vue'
import type { SpellListItem } from '@/types/wiki'
import { SCHOOL_COLORS } from '@/constants/wiki'

const { t } = useI18n()

const props = withDefaults(
  defineProps<{
    spell: SpellListItem
    mode?: 'navigate' | 'select'
    isSelected?: boolean
  }>(),
  {
    mode: 'navigate',
    isSelected: false,
  }
)

const emit = defineEmits<{
  select: [id: number]
}>()

const router = useRouter()

function handleClick() {
  if (props.mode === 'select') {
    emit('select', props.spell.id)
  } else {
    router.push({ name: 'wiki.detail', params: { category: 'spells', id: props.spell.id } })
  }
}
</script>

<template>
  <div
    class="rounded-lg p-4 cursor-pointer transition-all duration-150"
    :class="
      isSelected
        ? 'bg-secondary-800 border border-primary-500 bg-primary-900/10'
        : 'bg-secondary-800 border border-secondary-700 hover:border-secondary-500'
    "
    @click="handleClick"
  >
    <div class="flex items-start justify-between gap-2">
      <div class="min-w-0 flex-1">
        <div class="flex items-center gap-2 flex-wrap">
          <p class="font-medium text-secondary-100 truncate">{{ spell.name }}</p>
          <span class="text-xs px-1.5 py-0.5 bg-secondary-700 text-secondary-300 rounded">
            {{
              spell.level === 0
                ? t('wiki.spellCard.cantrip')
                : t('wiki.spellCard.levelAbbr') + spell.level
            }}
          </span>
          <span
            v-if="spell.isConcentration"
            class="text-xs px-1.5 py-0.5 bg-secondary-700 text-yellow-400 rounded"
            >C</span
          >
          <span
            v-if="spell.isRitual"
            class="text-xs px-1.5 py-0.5 bg-secondary-700 text-green-400 rounded"
            >R</span
          >
        </div>

        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
          <span
            v-if="spell.school"
            class="text-xs px-2 py-0.5 rounded capitalize"
            :class="SCHOOL_COLORS[spell.schoolSlug ?? ''] ?? 'bg-secondary-700 text-secondary-400'"
          >
            {{
              spell.schoolSlug ? t('wiki.reference.spellSchool.' + spell.schoolSlug) : spell.school
            }}
          </span>
        </div>

        <p class="text-xs text-secondary-400 mt-1">{{ spell.castingTime }} · {{ spell.range }}</p>
      </div>

      <WikiFavoriteButton srd-table="spell" :srd-id="spell.id" />
    </div>
  </div>
</template>
