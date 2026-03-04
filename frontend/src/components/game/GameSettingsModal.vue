<script setup lang="ts">
import { ref, watch } from 'vue'
import { gameApi } from '@/services/api/gameApi'
import { useGameStore } from '@/stores/game'
import type { Game } from '@/types/game'

const props = defineProps<{
  show: boolean
  game: Game
}>()

const emit = defineEmits<{
  close: []
  updated: []
}>()

const gameStore = useGameStore()

const name = ref(props.game.name)
const description = ref(props.game.description ?? '')
const maxPlayers = ref(props.game.maxPlayers)
const isSaving = ref(false)
const error = ref<string | null>(null)

watch(
  () => props.game,
  (game) => {
    name.value = game.name
    description.value = game.description ?? ''
    maxPlayers.value = game.maxPlayers
  }
)

async function handleSubmit() {
  if (!name.value.trim()) {
    error.value = 'Le nom de la partie est requis'
    return
  }

  if (maxPlayers.value < 1 || maxPlayers.value > 20) {
    error.value = 'Le nombre de joueurs doit être entre 1 et 20'
    return
  }

  isSaving.value = true
  error.value = null

  try {
    await gameApi.update(props.game.id, {
      name: name.value.trim(),
      description: description.value.trim() || undefined,
      maxPlayers: maxPlayers.value,
    })

    await gameStore.fetchGameById(props.game.id)
    emit('updated')
    emit('close')
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Erreur lors de la sauvegarde'
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
        @click.self="emit('close')"
      >
        <div
          class="bg-secondary-800 rounded-xl border border-secondary-700 shadow-2xl w-full max-w-md mx-4"
        >
          <div class="flex items-center justify-between p-4 border-b border-secondary-700">
            <h2 class="font-semibold text-secondary-50 text-lg flex items-center gap-2">
              <svg
                class="w-5 h-5 text-secondary-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="2"
              >
                <circle cx="12" cy="12" r="3"></circle>
                <path
                  d="M12 1v6m0 6v6m-9-9h6m6 0h6M4.93 4.93l4.24 4.24m5.66 5.66l4.24 4.24M4.93 19.07l4.24-4.24m5.66-5.66l4.24-4.24"
                ></path>
              </svg>
              Paramètres de la partie
            </h2>
            <button
              @click="emit('close')"
              class="text-secondary-400 hover:text-secondary-200 transition-colors p-1"
            >
              <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <form @submit.prevent="handleSubmit" class="p-4 space-y-4">
            <div>
              <label class="block text-sm font-medium text-secondary-300 mb-1">
                Nom de la partie <span class="text-error">*</span>
              </label>
              <input
                v-model="name"
                type="text"
                placeholder="Ex : La Forêt Oubliée"
                maxlength="250"
                class="w-full bg-secondary-700 border border-secondary-600 text-secondary-50 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent placeholder-secondary-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-secondary-300 mb-1"> Description </label>
              <textarea
                v-model="description"
                rows="3"
                placeholder="Décrivez votre partie..."
                class="w-full bg-secondary-700 border border-secondary-600 text-secondary-50 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent placeholder-secondary-500 resize-none"
              ></textarea>
            </div>

            <div>
              <label class="block text-sm font-medium text-secondary-300 mb-1">
                Nombre maximum de joueurs
              </label>
              <div class="flex items-center gap-3">
                <input
                  v-model.number="maxPlayers"
                  type="number"
                  min="1"
                  max="20"
                  class="w-24 bg-secondary-700 border border-secondary-600 text-secondary-50 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent text-center"
                />
                <span class="text-secondary-400 text-sm">joueurs (1 à 20)</span>
              </div>
            </div>

            <p
              v-if="error"
              class="text-error text-sm bg-error/10 border border-error/20 rounded-lg px-3 py-2"
            >
              {{ error }}
            </p>
          </form>

          <div class="flex gap-3 p-4 border-t border-secondary-700">
            <button
              type="button"
              @click="emit('close')"
              class="flex-1 px-4 py-2 bg-secondary-700 text-secondary-200 rounded-lg hover:bg-secondary-600 transition-colors text-sm"
            >
              Annuler
            </button>
            <button
              @click="handleSubmit"
              :disabled="isSaving"
              class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
            >
              <span v-if="isSaving">Sauvegarde...</span>
              <span v-else>Enregistrer</span>
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
