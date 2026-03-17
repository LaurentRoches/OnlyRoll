<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import FilterSelect from './FilterSelect.vue'
import { RACE_SPEED_TYPES, RACE_VISION_TYPES } from '@/constants/wiki'

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
    :label="t('wiki.raceFilters.speedType')"
    :model-value="String(modelValue.speed_type ?? '')"
    :placeholder="t('wiki.raceFilters.allSpeedTypes')"
    :options="RACE_SPEED_TYPES"
    @update:model-value="update('speed_type', $event)"
  />
  <FilterSelect
    :label="t('wiki.raceFilters.vision')"
    :model-value="String(modelValue.vision ?? '')"
    :placeholder="t('wiki.raceFilters.allVisions')"
    :options="RACE_VISION_TYPES"
    @update:model-value="update('vision', $event)"
  />
</template>
