<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { MonsterDetail } from '@/types/wiki'

const { t } = useI18n()

withDefaults(
  defineProps<{
    monster: MonsterDetail
    mode?: 'page' | 'inline'
  }>(),
  { mode: 'page' }
)

function abilityMod(score: number | null | undefined): string {
  if (score === null || score === undefined) return '—'
  const mod = Math.floor((score - 10) / 2)
  return `${score} (${mod >= 0 ? '+' : ''}${mod})`
}
</script>

<template>
  <div class="space-y-5">
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
      <div class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.monsterDetail.cr') }}</p>
        <p class="font-bold text-secondary-100">{{ monster.cr ?? '—' }}</p>
        <p v-if="monster.xp" class="text-xs text-secondary-500">
          {{ monster.xp.toLocaleString() }} XP
        </p>
      </div>
      <div class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.monsterDetail.ac') }}</p>
        <p class="font-bold text-secondary-100">{{ monster.armorClass ?? '—' }}</p>
        <p v-if="monster.armorDesc" class="text-xs text-secondary-500 truncate">
          {{ monster.armorDesc }}
        </p>
      </div>
      <div class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.monsterDetail.hp') }}</p>
        <p class="font-bold text-secondary-100">{{ monster.hitPointsAvg ?? '—' }}</p>
        <p v-if="monster.hitDiceFormula" class="text-xs text-secondary-500">
          {{ monster.hitDiceFormula }}
        </p>
      </div>
      <div class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.monsterDetail.speed') }}</p>
        <p class="font-bold text-secondary-100">
          {{ monster.walkSpeed ? monster.walkSpeed + ' ft' : '—' }}
        </p>
      </div>
    </div>

    <div class="bg-secondary-800 rounded-lg p-4">
      <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 text-center">
        <div
          v-for="(score, ability) in {
            [t('wiki.monsterDetail.abilities.str')]: monster.str,
            [t('wiki.monsterDetail.abilities.dex')]: monster.dex,
            [t('wiki.monsterDetail.abilities.con')]: monster.con,
            [t('wiki.monsterDetail.abilities.int')]: monster.int,
            [t('wiki.monsterDetail.abilities.wis')]: monster.wis,
            [t('wiki.monsterDetail.abilities.cha')]: monster.cha,
          }"
          :key="ability"
        >
          <p class="text-xs text-secondary-400">{{ ability }}</p>
          <p class="font-bold text-secondary-100">{{ abilityMod(score) }}</p>
        </div>
      </div>
    </div>

    <div
      v-if="monster.damageResistances?.length || monster.conditionImmunities?.length"
      class="space-y-2"
    >
      <div v-if="monster.damageResistances?.filter((r) => r.type === 'resistance').length">
        <span class="text-xs text-secondary-400">{{ t('wiki.monsterDetail.resistances') }} </span>
        <span class="text-xs text-secondary-300 capitalize">{{
          monster.damageResistances
            .filter((r) => r.type === 'resistance')
            .map((r) => r.damageType)
            .join(', ')
        }}</span>
      </div>
      <div v-if="monster.damageResistances?.filter((r) => r.type === 'immunity').length">
        <span class="text-xs text-secondary-400"
          >{{ t('wiki.monsterDetail.damageImmunities') }}
        </span>
        <span class="text-xs text-secondary-300 capitalize">{{
          monster.damageResistances
            .filter((r) => r.type === 'immunity')
            .map((r) => r.damageType)
            .join(', ')
        }}</span>
      </div>
      <div v-if="monster.conditionImmunities?.length">
        <span class="text-xs text-secondary-400"
          >{{ t('wiki.monsterDetail.conditionImmunities') }}
        </span>
        <span class="text-xs text-secondary-300 capitalize">{{
          monster.conditionImmunities.join(', ')
        }}</span>
      </div>
    </div>

    <div v-if="monster.senses?.length" class="text-sm text-secondary-400">
      <span class="text-secondary-500">{{ t('wiki.monsterDetail.senses') }} </span>
      <span class="capitalize">{{
        monster.senses.map((s) => s.type + ' ' + s.range + 'ft').join(', ')
      }}</span>
      <span v-if="monster.passivePerception">
        · {{ t('wiki.monsterDetail.passivePerception') }} {{ monster.passivePerception }}</span
      >
    </div>

    <div v-if="monster.languages?.length" class="text-sm text-secondary-400">
      <span class="text-secondary-500">{{ t('wiki.monsterDetail.languages') }} </span
      >{{ monster.languages.join(', ') }}
    </div>

    <div
      v-for="(actions, label) in {
        [t('wiki.monsterDetail.sections.traits')]: monster.traits,
        [t('wiki.monsterDetail.sections.actions')]: monster.actions?.filter(
          (a) => !a.isLegendary && !a.isReaction
        ),
        [t('wiki.monsterDetail.sections.reactions')]: monster.actions?.filter((a) => a.isReaction),
        [t('wiki.monsterDetail.sections.legendaryActions')]: monster.actions?.filter(
          (a) => a.isLegendary
        ),
      }"
      :key="label"
    >
      <template v-if="actions?.length">
        <h3 class="text-sm font-medium text-secondary-300 mb-3">{{ label }}</h3>
        <div class="space-y-3">
          <div v-for="entry in actions" :key="entry.name" class="bg-secondary-800 rounded-lg p-3">
            <p class="font-medium text-secondary-100 text-sm italic">{{ entry.name }}</p>
            <p class="text-secondary-400 text-xs mt-1">{{ entry.description }}</p>
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
