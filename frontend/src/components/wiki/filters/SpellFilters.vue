<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import FilterSelect from './FilterSelect.vue'
import { SPELL_LEVELS, SPELL_SCHOOLS, DAMAGE_TYPES } from '@/constants/wiki'

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

const levelOptions = SPELL_LEVELS.map((lvl) => ({
  value: String(lvl),
  label: lvl === 0 ? t('wiki.spellFilters.cantrip') : t('wiki.spellFilters.levelN', { n: lvl }),
}))
</script>

<template>
  <FilterSelect
    :label="t('wiki.spellFilters.level')"
    :model-value="String(modelValue.level ?? '')"
    :placeholder="t('wiki.spellFilters.allLevels')"
    :options="levelOptions"
    @update:model-value="update('level', $event)"
  />
  <FilterSelect
    :label="t('wiki.spellFilters.school')"
    :model-value="String(modelValue.school ?? '')"
    :placeholder="t('wiki.spellFilters.allSchools')"
    :options="SPELL_SCHOOLS"
    @update:model-value="update('school', $event)"
  />
  <FilterSelect
    :label="t('wiki.spellFilters.damageType')"
    :model-value="String(modelValue.damage_type ?? '')"
    :placeholder="t('wiki.spellFilters.allDamageTypes')"
    :options="DAMAGE_TYPES"
    @update:model-value="update('damage_type', $event)"
  />
</template>
