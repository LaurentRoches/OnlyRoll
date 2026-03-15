<script setup lang="ts">
import { computed, defineAsyncComponent, type Component } from 'vue'
import { useI18n } from 'vue-i18n'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const { t } = useI18n()

const FILTER_COMPONENTS: Record<string, Component> = {
  spells:   defineAsyncComponent(() => import('./filters/SpellFilters.vue')),
  races:    defineAsyncComponent(() => import('./filters/RaceFilters.vue')),
  monsters: defineAsyncComponent(() => import('./filters/MonsterFilters.vue')),
  items:    defineAsyncComponent(() => import('./filters/ItemFilters.vue')),
}

const props = defineProps<{
  category: string
  modelValue: Record<string, string | number | undefined>
}>()

const emit = defineEmits<{
  'update:modelValue': [value: Record<string, string | number | undefined>]
}>()

const CategoryFilters = computed<Component | null>(() => FILTER_COMPONENTS[props.category] ?? null)

function update(key: string, value: string) {
  emit('update:modelValue', { ...props.modelValue, [key]: value || undefined, page: 1 })
}

function clearFilter(key: string) {
  const updated = { ...props.modelValue }
  delete updated[key]
  updated.page = 1
  emit('update:modelValue', updated)
}

const activeFilters = computed(() =>
  Object.entries(props.modelValue).filter(([k, v]) => v !== undefined && v !== '' && k !== 'page' && k !== 'limit')
)
</script>

<template>
  <div class="space-y-4">
    <div v-if="activeFilters.length" class="flex flex-wrap gap-2">
      <span
        v-for="[key, val] in activeFilters"
        :key="key"
        class="inline-flex items-center gap-1 px-2 py-1 bg-primary-900/50 text-primary-300 border border-primary-700/50 rounded text-xs"
      >
        <span>{{ key }}: {{ val }}</span>
        <button class="hover:text-primary-100" @click="clearFilter(key)">
          <XMarkIcon class="w-3 h-3" />
        </button>
      </span>
    </div>

    <div>
      <label class="block text-xs text-secondary-400 mb-1">{{ t('wiki.filters.search') }}</label>
      <input
        :value="String(modelValue.search ?? '')"
        type="text"
        :placeholder="t('wiki.filters.searchPlaceholder')"
        class="w-full px-3 py-2 bg-secondary-700 border border-secondary-600 rounded text-sm text-secondary-100 placeholder-secondary-500 focus:outline-none focus:border-primary-500"
        @input="update('search', ($event.target as HTMLInputElement).value)"
      />
    </div>

    <component
      :is="CategoryFilters"
      v-if="CategoryFilters"
      :model-value="modelValue"
      @update:model-value="emit('update:modelValue', $event)"
    />

    <div>
      <label class="block text-xs text-secondary-400 mb-1">{{ t('wiki.filters.source') }}</label>
      <input
        :value="String(modelValue.source ?? '')"
        type="text"
        :placeholder="t('wiki.filters.sourcePlaceholder')"
        class="w-full px-3 py-2 bg-secondary-700 border border-secondary-600 rounded text-sm text-secondary-100 placeholder-secondary-500 focus:outline-none focus:border-primary-500"
        @input="update('source', ($event.target as HTMLInputElement).value)"
      />
    </div>
  </div>
</template>
