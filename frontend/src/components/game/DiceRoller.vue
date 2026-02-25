<script setup lang="ts">
import { ref, computed } from 'vue'
import { useChatStore } from '@/stores/chatStore'
import DiceResultDisplay from '@/components/game/DiceResultDisplay.vue'
import { getErrorMessage } from '@/utils/errorHelpers'
import type { DiceResult } from '@/types/game'

const props = defineProps<{
  gameId: number
}>()

const chatStore = useChatStore()

const formula = ref('')
const modifier = ref(0)
const isInCharacter = ref(true)
const advantageMode = ref<'normal' | 'advantage' | 'disadvantage' | 'super'>('normal')

const lastResult = ref<(DiceResult & { timestamp: string }) | null>(null)
const rollError = ref<string | null>(null)
let rollErrorTimer: ReturnType<typeof setTimeout> | null = null

function showRollError(message: string) {
  if (rollErrorTimer) clearTimeout(rollErrorTimer)
  rollError.value = message
  rollErrorTimer = setTimeout(() => { rollError.value = null }, 5000)
}

// ============================================
// Dés rapides prédéfinis
// ============================================
const quickDice = [
  { label: 'd20', value: '1d20', color: 'bg-primary-500', emoji: '🎲' },
  { label: 'd12', value: '1d12', color: 'bg-accent-rose', emoji: '🔶' },
  { label: 'd10', value: '1d10', color: 'bg-accent-amber', emoji: '🔟' },
  { label: 'd8', value: '1d8', color: 'bg-accent-cyan', emoji: '🔷' },
  { label: 'd6', value: '1d6', color: 'bg-accent-emerald', emoji: '🎲' },
  { label: 'd4', value: '1d4', color: 'bg-accent-purple', emoji: '🔺' },
]

const commonRolls = [
  { label: 'Initiative', formula: '1d20', icon: '⚡' },
  { label: 'Initiative (avantage)', formula: '2d20kh1', icon: '⚡' },
  { label: 'Attaque', formula: '1d20+5', icon: '⚔️' },
  { label: 'Attaque (désavantage)', formula: '2d20kl1', icon: '⚔️' },
  { label: 'Dégâts (épée)', formula: '1d8+3', icon: '🗡️' },
  { label: 'Dégâts (arc)', formula: '1d6+2', icon: '🏹' },
  { label: 'Jet de sauvegarde', formula: '1d20+2', icon: '🛡️' },
  { label: 'Soin (potion)', formula: '2d4+2', icon: '💊' },
]

const advantageModes = [
  {
    key: 'normal' as const,
    label: 'Normal',
    color: 'bg-secondary-700 hover:bg-secondary-600',
    activeColor: 'bg-secondary-500 ring-2 ring-white',
  },
  {
    key: 'advantage' as const,
    label: 'Avantage',
    color: 'bg-green-700 hover:bg-green-600',
    activeColor: 'bg-green-500 ring-2 ring-white',
  },
  {
    key: 'disadvantage' as const,
    label: 'Désavantage',
    color: 'bg-red-700 hover:bg-red-600',
    activeColor: 'bg-red-500 ring-2 ring-white',
  },
  {
    key: 'super' as const,
    label: 'Super-avantage',
    color: 'bg-yellow-700 hover:bg-yellow-600',
    activeColor: 'bg-yellow-500 ring-2 ring-white',
  },
]

// ============================================
// Computed
// ============================================
const fullFormula = computed(() => {
  if (!formula.value) return ''

  const match = formula.value.match(/^(\d+)d(\d+)$/)
  let base = formula.value

  if (match && advantageMode.value !== 'normal') {
    const sides = match[2]
    const mult = advantageMode.value === 'super' ? 3 : 2
    const keep = advantageMode.value === 'disadvantage' ? 'kl1' : 'kh1'
    base = `${mult}d${sides}${keep}`
  }

  if (modifier.value === 0) return base
  return `${base}${modifier.value > 0 ? '+' : ''}${modifier.value}`
})

const canRoll = computed(() => {
  return formula.value.match(/^\d+d\d+/) !== null
})

const advantagePreview = computed(() => {
  if (!formula.value || advantageMode.value === 'normal') return null
  return fullFormula.value || null
})

// ============================================
// Actions - Utilise chatStore.rollDice
// ============================================
async function rollDice() {
  if (!canRoll.value) return

  try {
    console.log('🎲 Lancer de dés:', fullFormula.value)

    const result = await chatStore.rollDice(props.gameId, fullFormula.value, isInCharacter.value)

    if (result.diceResult) {
      lastResult.value = {
        formula:     fullFormula.value,
        results:     result.diceResult.results,
        keptRolls:   result.diceResult.keptRolls,
        dropped:     result.diceResult.dropped,
        total:       result.diceResult.total,
        modifier:    result.diceResult.modifier,
        keepType:    result.diceResult.keepType,
        keepCount:   result.diceResult.keepCount,
        sidesPerDie: result.diceResult.sidesPerDie,
        timestamp:   result.createdAt,
      }
      console.log('✅ Résultat:', lastResult.value)
    }

    formula.value = ''
    modifier.value = 0
  } catch (error) {
    showRollError(getErrorMessage(error, 'Erreur lors du lancer de dés.'))
  }
}

function useQuickDice(diceFormula: string) {
  formula.value = diceFormula
}

async function useCommonRoll(rollFormula: string) {
  formula.value = rollFormula
  advantageMode.value = 'normal'
  await rollDice()
}

function clearFormula() {
  formula.value = ''
  modifier.value = 0
  advantageMode.value = 'normal'
}

function addToFormula(text: string) {
  formula.value += text
}
</script>

<template>
  <div class="flex-1 overflow-y-auto p-4">
    <div class="mb-6">
      <h3 class="font-bold text-secondary-50 text-lg mb-2 flex items-center gap-2">
        <span>🎲</span>
        Lanceur de dés
      </h3>
      <p class="text-sm text-secondary-400">Formule personnalisée ou raccourcis rapides</p>
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium text-secondary-300 mb-3"> Dés rapides </label>
      <div class="grid grid-cols-3 gap-2">
        <button
          v-for="dice in quickDice"
          :key="dice.value"
          @click="useQuickDice(dice.value)"
          :class="[
            'px-4 py-3 rounded-lg font-bold text-white transition-all hover:scale-105 shadow-md',
            dice.color,
            formula === dice.value ? 'ring-2 ring-white scale-105' : '',
          ]"
        >
          <div class="text-xl mb-1">{{ dice.emoji }}</div>
          <div class="text-sm">{{ dice.label }}</div>
        </button>
      </div>
    </div>

    <div class="mb-6">
      <label class="block text-sm font-medium text-secondary-300 mb-2"> Mode </label>
      <div class="grid grid-cols-2 gap-2">
        <button
          v-for="mode in advantageModes"
          :key="mode.key"
          @click="advantageMode = mode.key"
          :title="advantageMode === mode.key && advantagePreview ? `Formule : ${advantagePreview}` : ''"
          :class="[
            'px-3 py-2 rounded-lg text-white text-sm font-medium transition-all',
            advantageMode === mode.key ? mode.activeColor : mode.color,
          ]"
        >
          {{ mode.label }}
        </button>
      </div>
      <p v-if="advantagePreview" class="mt-1 text-xs text-secondary-400 font-mono text-center">
        → {{ advantagePreview }}
      </p>
    </div>

    <div class="mb-4">
      <label class="block text-sm font-medium text-secondary-300 mb-2">
        Formule personnalisée
      </label>
      <div class="flex gap-2">
        <input
          v-model="formula"
          type="text"
          placeholder="2d6, 1d20, 3d8..."
          class="form-input flex-1 font-mono"
        />
        <button
          v-if="formula"
          @click="clearFormula"
          class="px-3 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 transition-colors"
          aria-label="Effacer la formule"
        >
          ✕
        </button>
      </div>

      <div class="grid grid-cols-4 gap-2 mt-2">
        <button
          v-for="num in [1, 2, 3, 4]"
          :key="num"
          @click="addToFormula(num.toString())"
          :aria-label="`Ajouter ${num}`"
          class="px-3 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 transition-colors font-mono"
        >
          {{ num }}
        </button>
        <button
          @click="addToFormula('d')"
          aria-label="Ajouter d (dé)"
          class="px-3 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors font-bold"
        >
          d
        </button>
        <button
          @click="addToFormula('+')"
          aria-label="Ajouter +"
          class="px-3 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 transition-colors font-mono"
        >
          +
        </button>
        <button
          @click="addToFormula('-')"
          aria-label="Ajouter -"
          class="px-3 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 transition-colors font-mono"
        >
          −
        </button>
        <button
          @click="formula = formula.slice(0, -1)"
          aria-label="Effacer le dernier caractère"
          class="px-3 py-2 bg-error/80 text-white rounded-lg hover:bg-error transition-colors"
        >
          ⌫
        </button>
      </div>
    </div>

    <div class="mb-6">
      <label class="block text-sm font-medium text-secondary-300 mb-2"> Modificateur </label>
      <div class="flex items-center gap-2">
        <button
          @click="modifier--"
          aria-label="Diminuer le modificateur"
          class="px-4 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 font-bold transition-colors"
        >
          −
        </button>
        <input
          v-model.number="modifier"
          type="number"
          aria-label="Modificateur"
          class="form-input text-center w-20 font-mono font-bold"
        />
        <button
          @click="modifier++"
          aria-label="Augmenter le modificateur"
          class="px-4 py-2 bg-secondary-700 text-secondary-300 rounded-lg hover:bg-secondary-600 font-bold transition-colors"
        >
          +
        </button>
        <div class="flex-1 text-right">
          <span class="text-secondary-50 font-mono text-lg font-bold">
            {{ fullFormula || 'Aucune formule' }}
          </span>
        </div>
      </div>
    </div>

    <div class="mb-6">
      <label class="flex items-center gap-2 cursor-pointer">
        <input
          v-model="isInCharacter"
          type="checkbox"
          class="w-4 h-4 rounded bg-secondary-700 border-secondary-600 text-primary-500 focus:ring-primary-500"
        />
        <span class="text-sm text-secondary-300"> Lancer en tant que personnage (IC) </span>
      </label>
    </div>

    <Transition name="slide-up">
      <div
        v-if="rollError"
        class="flex items-start gap-2 mb-3 px-3 py-2 bg-red-900/40 border border-red-500/50 rounded-lg text-sm text-red-300"
        role="alert"
      >
        <span class="flex-shrink-0">⚠️</span>
        <span>{{ rollError }}</span>
        <button
          @click="rollError = null"
          class="ml-auto flex-shrink-0 text-red-400 hover:text-red-200 transition-colors"
          aria-label="Fermer"
        >✕</button>
      </div>
    </Transition>

    <button
      @click="rollDice"
      :disabled="!canRoll || chatStore.isSending"
      class="btn-primary w-full py-4 text-lg font-bold shadow-purple mb-6 flex items-center justify-center gap-2"
    >
      <span class="text-2xl">🎲</span>
      <span>Lancer {{ fullFormula || 'les dés' }}</span>
    </button>

    <div class="mb-6">
      <label class="block text-sm font-medium text-secondary-300 mb-3"> Lancers courants </label>
      <div class="space-y-2">
        <button
          v-for="roll in commonRolls"
          :key="roll.label"
          @click="useCommonRoll(roll.formula)"
          class="w-full px-4 py-3 bg-secondary-700 text-secondary-50 rounded-lg hover:bg-secondary-600 transition-colors text-left flex items-center justify-between group"
        >
          <div class="flex items-center gap-3">
            <span class="text-xl">{{ roll.icon }}</span>
            <span class="font-medium">{{ roll.label }}</span>
          </div>
          <span class="text-secondary-400 font-mono text-sm group-hover:text-secondary-200">
            {{ roll.formula }}
          </span>
        </button>
      </div>
    </div>

    <Transition name="slide-up">
      <div v-if="lastResult" class="card bg-gradient-primary p-4 shadow-purple">
        <DiceResultDisplay :diceResult="lastResult" />
        <div class="text-xs text-primary-200 mt-2 text-center">
          {{ new Date(lastResult.timestamp).toLocaleTimeString('fr-FR') }}
        </div>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
.gradient-primary {
  background: linear-gradient(135deg, #6366f1, #818cf8);
}

.shadow-purple {
  box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.39);
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from {
  transform: translateY(20px);
  opacity: 0;
}

.slide-up-leave-to {
  transform: translateY(-20px);
  opacity: 0;
}
</style>
