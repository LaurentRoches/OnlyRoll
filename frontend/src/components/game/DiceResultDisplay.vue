<script setup lang="ts">
import { computed } from 'vue'
import type { DiceResult } from '@/types/game'

const props = defineProps<{
  diceResult: DiceResult
}>()

const effectiveRolls = computed(() => props.diceResult.keptRolls ?? props.diceResult.results)

const sidesPerDie = computed<number | null>(() => {
  if (props.diceResult.sidesPerDie) return props.diceResult.sidesPerDie
  const m = props.diceResult.formula.match(/\d+d(\d+)/)
  return m ? parseInt(m[1]) : null
})

const isAllMax = computed(
  () =>
    sidesPerDie.value !== null &&
    effectiveRolls.value.length > 0 &&
    effectiveRolls.value.every((r) => r === sidesPerDie.value)
)

const isAllMin = computed(
  () => effectiveRolls.value.length > 0 && effectiveRolls.value.every((r) => r === 1)
)

const modeBadge = computed(() => {
  if (!props.diceResult.keepType) return null
  if (props.diceResult.keepType === 'kl') return 'Désavantage'
  const keepCount = props.diceResult.keepCount ?? 1
  return keepCount >= 2 ? 'Super-avantage' : 'Avantage'
})

function dieColor(value: number): string {
  if (sidesPerDie.value !== null && value === sidesPerDie.value) return 'text-green-400 font-bold'
  if (value === 1) return 'text-red-400 font-bold'
  return 'text-white'
}
</script>

<template>
  <div
    :class="[
      'rounded-lg p-3 border-2 transition-colors',
      isAllMax ? 'border-green-500' : isAllMin ? 'border-red-500' : 'border-transparent',
    ]"
  >
    <div class="flex items-center gap-2 text-sm text-secondary-400 mb-1">
      <span>🎲 {{ diceResult.formula }}</span>
      <span
        v-if="modeBadge"
        :class="[
          'px-1.5 py-0.5 rounded text-xs font-medium',
          diceResult.keepType === 'kl'
            ? 'bg-red-500/30 text-red-300'
            : modeBadge === 'Super-avantage'
              ? 'bg-yellow-500/30 text-yellow-300'
              : 'bg-green-500/30 text-green-300',
        ]"
      >
        {{ modeBadge }}
      </span>
    </div>

    <div class="flex flex-wrap items-center gap-1.5 text-xs mb-2">
      <span class="text-secondary-500 mr-1">Lancés :</span>

      <span
        v-for="(roll, i) in effectiveRolls"
        :key="`kept-${i}`"
        :class="['px-1.5 py-0.5 bg-secondary-800 rounded', dieColor(roll)]"
      >
        {{ roll }}
      </span>

      <span
        v-for="(roll, i) in diceResult.dropped ?? []"
        :key="`dropped-${i}`"
        class="px-1.5 py-0.5 bg-secondary-800 rounded line-through opacity-40 text-secondary-500"
      >
        {{ roll }}
      </span>

      <span v-if="diceResult.modifier !== 0" class="text-secondary-400 ml-1">
        {{ diceResult.modifier > 0 ? '+' : '' }}{{ diceResult.modifier }}
      </span>
    </div>

    <div
      :class="[
        'text-3xl font-bold text-right',
        isAllMax ? 'text-green-400' : isAllMin ? 'text-red-400' : 'text-white',
      ]"
    >
      {{ diceResult.total }}
    </div>
  </div>
</template>
