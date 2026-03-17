<script setup lang="ts">
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import WikiFavoriteButton from './WikiFavoriteButton.vue'

const { t } = useI18n()

interface GenericWikiItem {
  id: number
  name: string
  source: string
  isFavorited?: boolean
  [key: string]: unknown
}

const props = withDefaults(
  defineProps<{
    item: GenericWikiItem
    category: string
    srdTable?: string
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

const meta = computed(() => {
  const it = props.item
  const parts: string[] = []
  if (it.level !== undefined) parts.push(t('wiki.itemCard.level') + ' ' + it.level)
  if (it.schoolSlug) parts.push(t('wiki.reference.spellSchool.' + it.schoolSlug))
  else if (it.school) parts.push(String(it.school))
  if (it.cr !== undefined) parts.push(t('wiki.itemCard.cr') + ' ' + it.cr)
  if (it.typeSlug) parts.push(t('wiki.reference.creatureType.' + it.typeSlug))
  else if (it.type) parts.push(String(it.type))
  if (it.raritySlug) parts.push(t('wiki.reference.itemRarity.' + it.raritySlug))
  else if (it.rarity) parts.push(String(it.rarity))
  if (it.sizeSlug) parts.push(t('wiki.reference.creatureSize.' + it.sizeSlug))
  else if (it.size) parts.push(String(it.size))
  if (it.hitDie) parts.push('d' + it.hitDie)
  if (it.prerequisite) parts.push(String(it.prerequisite))
  return parts.join(' · ')
})

function handleClick() {
  if (props.mode === 'select') {
    emit('select', props.item.id)
  } else {
    router.push({ name: 'wiki.detail', params: { category: props.category, id: props.item.id } })
  }
}
</script>

<template>
  <div
    class="rounded-lg p-4 cursor-pointer transition-all duration-150 flex items-start justify-between gap-2"
    :class="
      isSelected
        ? 'bg-secondary-800 border border-primary-500 bg-primary-900/10'
        : 'bg-secondary-800 border border-secondary-700 hover:border-secondary-500 hover:bg-secondary-750'
    "
    @click="handleClick"
  >
    <div class="min-w-0">
      <p class="font-medium text-secondary-100 truncate">{{ item.name }}</p>
      <p v-if="meta" class="text-xs text-secondary-400 mt-0.5 capitalize truncate">{{ meta }}</p>
      <p class="text-xs text-secondary-500 mt-0.5">{{ item.source }}</p>
    </div>
    <WikiFavoriteButton :srd-table="srdTable ?? category.replace(/s$/, '')" :srd-id="item.id" />
  </div>
</template>
