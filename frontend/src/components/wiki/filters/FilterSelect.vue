<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

defineProps<{
  label: string
  modelValue: string | number | undefined
  placeholder: string
  options: ReadonlyArray<string | number | { value: string; label?: string; labelKey?: string }>
}>()

defineEmits<{
  'update:modelValue': [value: string]
}>()

function optionLabel(opt: { value: string; label?: string; labelKey?: string }): string {
  return opt.labelKey ? t(opt.labelKey) : opt.label ?? opt.value
}
</script>

<template>
  <div>
    <label class="block text-xs text-secondary-400 mb-1">{{ label }}</label>
    <select
      :value="modelValue ?? ''"
      class="w-full px-3 py-2 bg-secondary-700 border border-secondary-600 rounded text-sm text-secondary-100 focus:outline-none focus:border-primary-500"
      @change="$emit('update:modelValue', ($event.target as HTMLSelectElement).value)"
    >
      <option value="">{{ placeholder }}</option>
      <template v-for="opt in options" :key="typeof opt === 'object' ? opt.value : opt">
        <option
          v-if="typeof opt === 'object'"
          :value="opt.value"
        >{{ optionLabel(opt) }}</option>
        <option
          v-else
          :value="opt"
          class="capitalize"
        >{{ opt }}</option>
      </template>
    </select>
  </div>
</template>
