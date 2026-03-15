<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute } from 'vue-router'
import { usePresenceStore } from '@/stores/presenceStore'
import { useAuthStore } from '@/stores/auth'
import { gameApi } from '@/services/api/gameApi'
import type { GamePlayer } from '@/types/game'
import { PlayerRole, PlayerStatus } from '@/types/game'
import InvitePlayerModal from './InvitePlayerModal.vue'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps<{
  players: GamePlayer[]
  gameMasterId: number | undefined
  maxPlayers?: number
}>()

const emit = defineEmits<{
  playerKicked: []
  playerInvited: []
}>()

const route = useRoute()
const gameId = computed(() => Number(route.params.id))
const presenceStore = usePresenceStore()
const authStore = useAuthStore()

const showInviteModal = ref(false)
const kickingPlayerId = ref<number | null>(null)

const isGameMaster = computed(() => {
  if (!authStore.user || !props.gameMasterId) return false
  return authStore.user.id === props.gameMasterId
})

const activeAndPendingCount = computed(() => {
  return props.players.filter(
    (p) =>
      p.status === PlayerStatus.ACTIVE ||
      p.status === PlayerStatus.INACTIVE ||
      p.status === PlayerStatus.PENDING
  ).length
})

const isAtCapacity = computed(() => {
  if (!props.maxPlayers) return false
  return activeAndPendingCount.value >= props.maxPlayers
})

const sortedPlayers = computed(() => {
  return [...props.players]
    .filter(
      (p) =>
        p.status === PlayerStatus.ACTIVE ||
        p.status === PlayerStatus.INACTIVE ||
        p.status === PlayerStatus.PENDING
    )
    .sort((a, b) => {
      if (a.role === PlayerRole.GAME_MASTER) return -1
      if (b.role === PlayerRole.GAME_MASTER) return 1
      if (a.status === PlayerStatus.ACTIVE && b.status !== PlayerStatus.ACTIVE) return -1
      if (b.status === PlayerStatus.ACTIVE && a.status !== PlayerStatus.ACTIVE) return 1
      return 0
    })
})

const onlinePlayers = computed(() => {
  return props.players.filter((p) => presenceStore.isUserOnline(gameId.value, p.user.id))
})

const activePlayers = computed(() => {
  return props.players.filter((p) => p.status === PlayerStatus.ACTIVE)
})

function isPlayerOnline(player: GamePlayer): boolean {
  return presenceStore.isUserOnline(gameId.value, player.user.id)
}

const playersByRole = computed(() => {
  return {
    gameMaster: props.players.filter((p) => p.role === PlayerRole.GAME_MASTER),
    players: props.players.filter((p) => p.role === PlayerRole.PLAYER),
    spectators: props.players.filter((p) => p.role === PlayerRole.SPECTATOR),
  }
})

function getRoleLabel(role: PlayerRole): string {
  const labels = {
    [PlayerRole.GAME_MASTER]: t('game.players.roles.gameMaster'),
    [PlayerRole.PLAYER]: t('game.players.roles.player'),
    [PlayerRole.SPECTATOR]: t('game.players.roles.spectator'),
  }
  return labels[role] || t('game.players.roles.unknown')
}

function getRoleColor(role: PlayerRole): string {
  const colors = {
    [PlayerRole.GAME_MASTER]: 'bg-accent-purple text-white',
    [PlayerRole.PLAYER]: 'bg-primary-500/30 text-primary-200',
    [PlayerRole.SPECTATOR]: 'bg-secondary-600 text-secondary-300',
  }
  return colors[role] || 'bg-secondary-600 text-secondary-300'
}

function getAvatarColor(userId: number): string {
  const colors = [
    'bg-primary-500',
    'bg-accent-amber',
    'bg-accent-emerald',
    'bg-accent-rose',
    'bg-accent-cyan',
    'bg-accent-purple',
  ]
  return colors[userId % colors.length]
}

function formatJoinedAt(dateString: string): string {
  const date = new Date(dateString)
  const now = new Date()
  const diffMs = now.getTime() - date.getTime()
  const diffMins = Math.floor(diffMs / 60000)

  if (diffMins < 1) return t('game.players.timeAgo.justNow')
  if (diffMins < 60) return t('game.players.timeAgo.minutes', { count: diffMins })

  const diffHours = Math.floor(diffMins / 60)
  if (diffHours < 24) return t('game.players.timeAgo.hours', { count: diffHours })

  const diffDays = Math.floor(diffHours / 24)
  return t('game.players.timeAgo.days', { count: diffDays })
}

function isCurrentUserGameMaster(player: GamePlayer): boolean {
  return player.role === PlayerRole.GAME_MASTER
}

async function handleKick(player: GamePlayer) {
  if (!confirm(t('game.players.kick.confirm', { pseudo: player.user.pseudo }))) return

  kickingPlayerId.value = player.id
  try {
    await gameApi.kickPlayer(gameId.value, player.id)
    emit('playerKicked')
  } catch (e) {
    console.error("Erreur lors de l'expulsion:", e)
    alert(t('game.players.kick.error'))
  } finally {
    kickingPlayerId.value = null
  }
}
</script>

<template>
  <div class="flex-1 overflow-y-auto p-4">
    <div class="mb-6">
      <h3 class="font-bold text-secondary-50 text-lg mb-2 flex items-center gap-2">
        <span>👥</span>
        {{ t('game.players.title') }}
      </h3>

      <div class="grid grid-cols-3 gap-2 text-center text-sm">
        <div class="bg-secondary-700 rounded-lg p-2">
          <div class="text-success font-bold">{{ onlinePlayers.length }}</div>
          <div class="text-secondary-400 text-xs">{{ t('game.players.stats.online') }}</div>
        </div>
        <div class="bg-secondary-700 rounded-lg p-2">
          <div class="text-secondary-50 font-bold">{{ activePlayers.length }}</div>
          <div class="text-secondary-400 text-xs">{{ t('game.players.stats.members') }}</div>
        </div>
        <div class="bg-secondary-700 rounded-lg p-2">
          <div class="text-accent-purple font-bold">{{ playersByRole.gameMaster.length }}</div>
          <div class="text-secondary-400 text-xs">{{ t('game.players.stats.gm') }}</div>
        </div>
      </div>
    </div>

    <div class="space-y-2">
      <div
        v-for="player in sortedPlayers"
        :key="player.id"
        class="card p-3 hover:bg-secondary-700 transition-all group"
        :class="{ 'opacity-70': player.status === PlayerStatus.PENDING }"
      >
        <div class="flex items-center gap-3">
          <div
            :class="[
              'w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-lg flex-shrink-0 relative',
              getAvatarColor(player.user.id),
            ]"
          >
            <span>{{ player.user.pseudo.slice(0, 2).toUpperCase() }}</span>

            <div
              :class="[
                'absolute -bottom-0.5 -right-0.5 w-3.5 h-3.5 rounded-full border-2 border-secondary-800',
                isPlayerOnline(player) ? 'bg-success' : 'bg-secondary-500',
              ]"
              :title="isPlayerOnline(player) ? t('game.players.status.online') : t('game.players.status.offline')"
            ></div>
          </div>

          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
              <span class="font-semibold text-secondary-50 truncate">
                {{ player.user.pseudo }}
              </span>

              <span
                :class="[
                  'px-2 py-0.5 text-xs font-medium rounded whitespace-nowrap',
                  getRoleColor(player.role),
                ]"
              >
                {{ getRoleLabel(player.role) }}
              </span>

              <span
                v-if="player.status === PlayerStatus.PENDING"
                class="px-2 py-0.5 text-xs font-medium rounded whitespace-nowrap bg-accent-amber/20 text-accent-amber border border-accent-amber/30"
              >
                {{ t('game.players.status.pending') }}
              </span>
            </div>

            <div class="flex items-center gap-2 text-xs text-secondary-400">
              <span :class="isPlayerOnline(player) ? 'text-success' : 'text-secondary-400'">
                {{ isPlayerOnline(player) ? t('game.players.status.online') : t('game.players.status.offline') }}
              </span>
              <span>•</span>
              <span>{{ formatJoinedAt(player.joinedAt) }}</span>
            </div>
          </div>

          <div
            v-if="isGameMaster && !isCurrentUserGameMaster(player)"
            class="opacity-0 group-hover:opacity-100 transition-opacity"
          >
            <button
              @click="handleKick(player)"
              :disabled="kickingPlayerId === player.id"
              class="p-1.5 hover:bg-error/20 rounded transition-colors text-secondary-400 hover:text-error disabled:opacity-50"
              :title="t('game.players.kick.title')"
            >
              <svg
                v-if="kickingPlayerId !== player.id"
                xmlns="http://www.w3.org/2000/svg"
                class="w-4 h-4"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
              >
                <polyline points="3 6 5 6 21 6"></polyline>
                <path d="M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"></path>
                <path d="M10 11v6M14 11v6"></path>
                <path d="M9 6V4a1 1 0 011-1h4a1 1 0 011 1v2"></path>
              </svg>
              <div
                v-else
                class="animate-spin w-4 h-4 border-2 border-error border-t-transparent rounded-full"
              ></div>
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="sortedPlayers.length === 0" class="text-center py-8 text-secondary-400">
      <div class="text-4xl mb-3">👥</div>
      <p class="text-lg">{{ t('game.players.empty.title') }}</p>
      <p class="text-sm mt-1">{{ t('game.players.empty.subtitle') }}</p>
    </div>

    <div v-if="isGameMaster" class="mt-4">
      <button
        v-if="!isAtCapacity"
        @click="showInviteModal = true"
        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-primary-500/20 border border-primary-500/40 text-primary-300 rounded-lg hover:bg-primary-500/30 transition-colors text-sm font-medium"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
        </svg>
        {{ t('game.players.invite.button') }}
      </button>
      <div
        v-else
        class="w-full text-center py-2 text-secondary-400 text-xs bg-secondary-700/50 rounded-lg"
      >
        {{ t('game.players.invite.atCapacity', { current: activeAndPendingCount, max: maxPlayers }) }}
      </div>
    </div>

    <div v-if="playersByRole.spectators.length > 0" class="mt-6 pt-6 border-t border-secondary-700">
      <h4 class="text-sm font-semibold text-secondary-400 mb-3 flex items-center gap-2">
        <span>👁️</span>
        {{ t('game.players.spectators.title', { count: playersByRole.spectators.length }) }}
      </h4>
      <div class="space-y-2">
        <div
          v-for="spectator in playersByRole.spectators"
          :key="spectator.id"
          class="bg-secondary-700/50 rounded-lg p-2 flex items-center gap-2"
        >
          <div
            :class="[
              'w-8 h-8 rounded-full flex items-center justify-center text-white text-sm',
              getAvatarColor(spectator.user.id),
            ]"
          >
            {{ spectator.user.pseudo.slice(0, 2).toUpperCase() }}
          </div>
          <span class="text-sm text-secondary-300">{{ spectator.user.pseudo }}</span>
        </div>
      </div>
    </div>

    <InvitePlayerModal
      v-if="showInviteModal"
      :game-id="gameId"
      :max-players="maxPlayers ?? 6"
      :current-players="players"
      @success="emit('playerInvited')"
      @close="showInviteModal = false"
    />
  </div>
</template>

<style scoped>
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #1e293b;
}

::-webkit-scrollbar-thumb {
  background: #475569;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #64748b;
}
</style>
