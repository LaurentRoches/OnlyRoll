<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import type { ItemDetail } from '@/types/wiki'

const { t } = useI18n()

withDefaults(
  defineProps<{
    item: ItemDetail
    mode?: 'page' | 'inline'
  }>(),
  { mode: 'page' }
)
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap gap-2">
      <span
        v-if="item.isMagical"
        class="px-2 py-1 bg-purple-900/30 text-purple-300 border border-purple-700/40 rounded text-xs"
        >{{ t('wiki.itemDetail.magical') }}</span
      >
      <span
        v-if="item.requiresAttunement"
        class="px-2 py-1 bg-yellow-900/30 text-yellow-400 border border-yellow-700/40 rounded text-xs"
      >
        {{ t('wiki.itemDetail.attunement')
        }}{{ item.attunementText ? ': ' + item.attunementText : '' }}
      </span>
      <span
        v-if="item.rarity"
        class="px-2 py-1 bg-secondary-700 text-secondary-300 rounded text-xs capitalize"
        >{{
          item.raritySlug ? t('wiki.reference.itemRarity.' + item.raritySlug) : item.rarity
        }}</span
      >
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div v-if="item.valueGp" class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.itemDetail.value') }}</p>
        <p class="font-bold text-secondary-100">
          {{ item.valueGp }} {{ t('wiki.itemDetail.goldPieces') }}
        </p>
      </div>
      <div v-if="item.weight" class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.itemDetail.weight') }}</p>
        <p class="font-bold text-secondary-100">
          {{ item.weight }} {{ t('wiki.itemDetail.weightUnit') }}
        </p>
      </div>
      <div v-if="item.armor" class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.itemDetail.ac') }}</p>
        <p class="font-bold text-secondary-100">{{ item.armor.ac }}</p>
      </div>
    </div>

    <div v-if="item.weaponDamages?.length" class="flex flex-wrap gap-2">
      <span
        v-for="(dmg, i) in item.weaponDamages"
        :key="i"
        class="px-2 py-1 bg-red-900/30 text-red-300 border border-red-700/40 rounded text-xs"
      >
        {{ dmg.dice }} {{ dmg.damageType }}
      </span>
    </div>

    <div v-if="item.weaponProperties?.length" class="flex flex-wrap gap-2">
      <span
        v-for="prop in item.weaponProperties"
        :key="prop"
        class="px-2 py-1 bg-secondary-700 text-secondary-300 rounded text-xs capitalize"
        >{{ prop }}</span
      >
    </div>

    <div
      v-if="item.description"
      class="bg-secondary-800 rounded-lg p-4 text-secondary-200 text-sm whitespace-pre-line"
    >
      {{ item.description }}
    </div>
  </div>
</template>
