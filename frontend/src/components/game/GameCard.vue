<script setup lang="ts">
import type { Game } from '@/types/game'
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { usePresenceStore } from '@/stores/presenceStore'
import { useAuthStore } from '@/stores/auth'
import { UsersIcon, LockClosedIcon } from '@heroicons/vue/24/outline'
import { gameApi } from '@/services/api/gameApi'

interface Props {
  game: Game
  showJoinButton?: boolean
  showDeleteButton?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showJoinButton: false,
  showDeleteButton: false,
})

const emit = defineEmits<{
  join: [game: Game]
  deleted: [game: Game]
}>()

const router = useRouter()
const presenceStore = usePresenceStore()
const authStore = useAuthStore()

const statusConfig = computed(() => {
  const configs = {
    preparation: {
      label: 'En préparation',
      class: 'bg-info text-white',
    },
    in_progress: {
      label: 'En cours',
      class: 'bg-accent-emerald text-white',
    },
    paused: {
      label: 'En pause',
      class: 'bg-warning text-primary-900',
    },
    completed: {
      label: 'Terminée',
      class: 'bg-secondary-600 text-white',
    },
    archived: {
      label: 'Archivée',
      class: 'bg-secondary-700 text-secondary-300',
    },
  }
  return configs[props.game.status] || configs.preparation
})

const isFull = computed(() => props.game.currentPlayersCount >= props.game.maxPlayers)

const campaignLevel = computed(() => {
  return props.game.settings?.campaignLevel || 6
})

const onlinePlayersCount = computed(() => {
  return presenceStore.getOnlineCount(props.game.id)
})

const isUserMember = computed(() => {
  if (!authStore.user) return false
  return (
    props.game.gameMaster?.id === authStore.user.id ||
    props.game.gamePlayers?.some((gp) => gp.user.id === authStore.user!.id)
  )
})

const isDeleting = ref(false)
const showHint = ref(false)
let hintTimer: ReturnType<typeof setTimeout> | null = null

function viewGame() {
  if (authStore.isAuthenticated && !isUserMember.value) {
    showHint.value = true
    if (hintTimer) clearTimeout(hintTimer)
    hintTimer = setTimeout(() => {
      showHint.value = false
    }, 3000)
    return
  }
  router.push({ name: 'game-play', params: { id: props.game.id } })
}

function handleJoin(event: Event) {
  event.stopPropagation()
  emit('join', props.game)
}

async function handleDelete(event: Event) {
  event.stopPropagation()
  if (
    !confirm(
      `Êtes-vous sûr de vouloir supprimer "${props.game.name}" ?\n\nCette action est définitive : tous les joueurs, messages et cartes associés seront effacés.`
    )
  )
    return
  isDeleting.value = true
  try {
    await gameApi.delete(props.game.id)
    emit('deleted', props.game)
  } catch (e) {
    console.error('Erreur lors de la suppression:', e)
    alert('Impossible de supprimer cette partie')
  } finally {
    isDeleting.value = false
  }
}
</script>

<template>
  <article
    class="bg-secondary-800 rounded-lg overflow-hidden border border-secondary-700 hover:border-primary-500 transition-all duration-300 cursor-pointer group relative"
    @click="viewGame"
    @keydown.enter="viewGame"
    @keydown.space.prevent="viewGame"
    tabindex="0"
    role="button"
    :aria-label="`Voir la partie ${game.name}`"
  >
    <div
      class="h-32 bg-gradient-to-br from-secondary-600 via-secondary-500 to-primary-400 relative"
    >
      <div
        v-if="showDeleteButton"
        class="absolute top-3 left-3 opacity-0 group-hover:opacity-100 transition-opacity"
      >
        <button
          @click="handleDelete"
          :disabled="isDeleting"
          class="p-1.5 bg-secondary-900/80 hover:bg-error/80 rounded-md text-secondary-300 hover:text-white transition-colors disabled:opacity-50"
          title="Supprimer la partie"
        >
          <svg
            v-if="!isDeleting"
            class="w-4 h-4"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            stroke-width="2"
          >
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"></path>
            <path d="M10 11v6M14 11v6"></path>
            <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"></path>
          </svg>
          <div
            v-else
            class="animate-spin w-4 h-4 border-2 border-white border-t-transparent rounded-full"
          ></div>
        </button>
      </div>

      <div class="absolute top-3 right-3">
        <span
          :class="statusConfig.class"
          class="px-3 py-1 rounded-full text-xs font-semibold shadow-lg"
        >
          {{ statusConfig.label }}
        </span>
      </div>

      <div v-if="!game.isPublic" class="absolute bottom-3 left-3">
        <div
          class="flex items-center gap-1 text-secondary-200 text-xs bg-secondary-900/60 px-2 py-1 rounded-md backdrop-blur-sm"
        >
          <LockClosedIcon class="w-3 h-3" aria-hidden="true" />
          <span>Privée</span>
        </div>
      </div>
    </div>

    <div class="p-4">
      <h3
        class="text-lg font-semibold text-secondary-50 mb-1 group-hover:text-primary-400 transition-colors"
      >
        {{ game.name }}
      </h3>

      <p class="text-secondary-400 text-sm mb-1">Maître du jeu</p>
      <p class="text-secondary-300 text-sm mb-4">Campagne niveau : {{ campaignLevel }}</p>

      <div class="flex items-center justify-between">
        <div class="flex items-center text-sm gap-2">
          <UsersIcon
            class="w-5 h-5"
            :class="onlinePlayersCount > 0 ? 'text-success' : 'text-secondary-400'"
            aria-hidden="true"
          />
          <span class="text-secondary-400">
            <span :class="onlinePlayersCount > 0 ? 'text-success font-semibold' : ''">{{
              onlinePlayersCount
            }}</span>
            joueur(s) connecté(s)
          </span>
        </div>

        <button
          v-if="showJoinButton && !isUserMember"
          @click="handleJoin"
          :disabled="isFull"
          :class="[
            'px-4 py-2 rounded-md text-sm font-medium transition-all duration-200',
            isFull
              ? 'bg-secondary-700 text-secondary-500 cursor-not-allowed'
              : 'bg-primary-500 hover:bg-primary-600 text-white hover:shadow-purple',
          ]"
        >
          {{ isFull ? 'Complète' : 'Rejoindre' }}
        </button>
      </div>
    </div>

    <Transition name="hint-fade">
      <div
        v-if="showHint"
        class="absolute inset-x-0 bottom-0 bg-primary-900/90 backdrop-blur-sm rounded-b-lg px-4 py-3 border-t border-primary-500/50"
      >
        <p class="text-sm text-primary-200 text-center">
          Cliquez sur <span class="font-semibold text-white">"Rejoindre"</span> pour accéder à cette
          partie
        </p>
      </div>
    </Transition>
  </article>
</template>

<style scoped>
.hint-fade-enter-active,
.hint-fade-leave-active {
  transition:
    opacity 0.2s ease,
    transform 0.2s ease;
}

.hint-fade-enter-from,
.hint-fade-leave-to {
  opacity: 0;
  transform: translateY(4px);
}
</style>
