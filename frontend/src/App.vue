<template>
  <div>
    <RouterView />

    <KonamiVictory :is-active="konamiActivated" @close="resetKonami" />
  </div>
</template>

<script setup lang="ts">
import { useKonamiCode } from '@/composables/useKonamiCode'
import KonamiVictory from '@/components/common/KonamiVictory.vue'

const { isActivated: konamiActivated, reset: resetKonami } = useKonamiCode()
</script>

<style></style>
