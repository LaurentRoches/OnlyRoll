<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = withDefaults(defineProps<{
  level: number
  damageFormula: string | null
  upcastDicePerLevel: number | null
  upcastDiceFaces: number | null
  damageType?: string | null
}>(), {
  damageType: null,
})

const selectedLevel = ref(props.level)

const baseDice = computed(() => {
  if (!props.damageFormula) return null
  const m = props.damageFormula.match(/(\d+)d(\d+)/)
  if (!m) return null
  return { count: parseInt(m[1]), faces: parseInt(m[2]) }
})

const totalDice = computed(() => {
  if (!baseDice.value) return null
  const extra = props.upcastDicePerLevel && props.upcastDiceFaces
    ? Math.max(0, selectedLevel.value - props.level) * props.upcastDicePerLevel
    : 0
  return {
    count: baseDice.value.count + extra,
    faces: baseDice.value.faces,
  }
})

const results = computed(() => {
  if (!totalDice.value) return null
  const { count, faces } = totalDice.value
  return {
    min: count,
    max: count * faces,
    avg: Math.floor(count * (faces + 1) / 2),
    formula: `${count}d${faces}`,
  }
})

const availableLevels = computed(() => {
  const levels = []
  for (let l = props.level; l <= 9; l++) {
    levels.push(l)
  }
  return levels
})
</script>

<template>
  <div v-if="results" class="bg-secondary-800 border border-secondary-700 rounded-lg p-4">
    <!-- Niveau du sort + boutons -->
    <div class="flex items-center gap-3 mb-4 flex-wrap">
      <span class="text-sm text-secondary-400 whitespace-nowrap">{{ t('wiki.damageCalculator.spellLevel') }}</span>
      <div class="flex gap-1.5">
        <button
          v-for="lvl in availableLevels"
          :key="lvl"
          class="w-8 h-8 text-sm rounded-full transition-colors flex items-center justify-center font-medium"
          :class="selectedLevel === lvl
            ? 'bg-primary-600 text-white'
            : 'bg-secondary-700 text-secondary-300 hover:bg-secondary-600'"
          @click="selectedLevel = lvl"
        >
          {{ lvl }}
        </button>
      </div>
    </div>

    <!-- Formule + type de dégât -->
    <p class="text-center text-primary-400 font-bold mb-2">
      {{ results.formula }}
      <span v-if="damageType" class="capitalize"> {{ t('wiki.damageCalculator.damageOf', { type: damageType }) }}</span>
    </p>

    <!-- Stats inline -->
    <p class="text-center text-sm text-secondary-400">
      {{ t('wiki.damageCalculator.min') }} {{ results.min }} | {{ t('wiki.damageCalculator.avg') }} {{ results.avg }} | {{ t('wiki.damageCalculator.max') }} {{ results.max }}
    </p>
  </div>
</template>
