<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import FilterSelect from './FilterSelect.vue'
import { MONSTER_CR_VALUES, MONSTER_SIZES } from '@/constants/wiki'

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

const crOptions = MONSTER_CR_VALUES.map((cr) => ({ value: cr, label: t('wiki.monsterFilters.crLabel', { cr }) }))
</script>

<template>
  <FilterSelect
    :label="t('wiki.monsterFilters.cr')"
    :model-value="String(modelValue.cr ?? '')"
    :placeholder="t('wiki.monsterFilters.allCR')"
    :options="crOptions"
    @update:model-value="update('cr', $event)"
  />
  <FilterSelect
    :label="t('wiki.monsterFilters.size')"
    :model-value="String(modelValue.size ?? '')"
    :placeholder="t('wiki.monsterFilters.allSizes')"
    :options="MONSTER_SIZES"
    @update:model-value="update('size', $event)"
  />
</template>
