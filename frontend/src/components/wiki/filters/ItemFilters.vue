<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import FilterSelect from './FilterSelect.vue'
import { ITEM_RARITIES } from '@/constants/wiki'

const { t } = useI18n()

const props = defineProps<{
  modelValue: Record<string, string | number | undefined>
}>()

const emit = defineEmits<{
  'update:modelValue': [value: Record<string, string | number | undefined>]
}>()

function update(key: string, value: string) {
  emit('update:modelValue', { ...props.modelValue, [key]: value || undefined, page: 1 })
}
</script>

<template>
  <FilterSelect
    :label="t('wiki.itemFilters.rarity')"
    :model-value="String(modelValue.rarity ?? '')"
    :placeholder="t('wiki.itemFilters.allRarities')"
    :options="ITEM_RARITIES"
    @update:model-value="update('rarity', $event)"
  />
</template>
