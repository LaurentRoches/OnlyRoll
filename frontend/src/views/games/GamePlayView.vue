<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router'
import { useGameStore } from '@/stores/game'
import { useAuthStore } from '@/stores/auth'
import { useMapStore } from '@/stores/mapStore'
import { useChatStore } from '@/stores/chatStore'
import { usePresenceStore } from '@/stores/presenceStore'
import { mercureService } from '@/services/mercure'
import { presenceApi } from '@/services/api/presenceApi'
import { gameApi } from '@/services/api/gameApi'
import type {
  MercureTokenEventData,
  MercureMapEventData,
  MercureChatMessageData,
  MercurePresenceEventData,
} from '@/types/websocket'
import type { GameMap as GameMapType } from '@/types/game'

import GameHeader from '@/components/game/GameHeader.vue'
import GameMap from '@/components/game/GameMap.vue'
import MapToolbar from '@/components/game/MapToolbar.vue'
import ChatPanel from '@/components/game/ChatPanel.vue'
import PlayersList from '@/components/game/PlayersList.vue'
import DiceRoller from '@/components/game/DiceRoller.vue'
import EmptyMapState from '@/components/game/EmptyMapState.vue'
import UploadMapModal from '@/components/game/UploadMapModal.vue'
import EditMapModal from '@/components/game/EditMapModal.vue'
import CreateTokenModal from '@/components/game/CreateTokenModal.vue'
import GameSettingsModal from '@/components/game/GameSettingsModal.vue'

const route = useRoute()
const router = useRouter()
const gameId = computed(() => Number(route.params.id))

const gameStore = useGameStore()
const authStore = useAuthStore()
const mapStore = useMapStore()
const chatStore = useChatStore()
const presenceStore = usePresenceStore()

const rightPanelOpen = ref(true)
const activeTab = ref<'chat' | 'players' | 'dice'>('chat')
const isLoading = ref(true)
const selectedTool = ref('select')
const mapZoom = ref(100)

const isConnected = ref(false)
const connectionState = ref<'connecting' | 'open' | 'closed'>('connecting')

const showUploadModal = ref(false)

const showEditModal = ref(false)
const editingMap = ref<GameMapType | null>(null)

const showCreateTokenModal = ref(false)
const tokenCreationPosition = ref<{ x: number; y: number } | null>(null)

const showSettingsModal = ref(false)

const gameMapRef = ref<InstanceType<typeof GameMap> | null>(null)

function notifyDisconnectionBeacon() {
  try {
    const url = `/api/games/${gameId.value}/presence/leave`
    const blob = new Blob([JSON.stringify({})], { type: 'application/json' })
    navigator.sendBeacon(url, blob)
  } catch (error) {
    console.error('Erreur sendBeacon:', error)
  }
}

async function notifyDisconnection() {
  try {
    await presenceApi.leave(gameId.value)
  } catch (error) {
    console.error('Erreur lors de la notification de déconnexion:', error)
  }
}

onMounted(async () => {
  await initializeGame()
  setupMercure()
  setupBeforeUnload()
})

onBeforeRouteLeave(async () => {
  await notifyDisconnection()

  mercureService.disconnect()
  presenceStore.clearGamePresence(gameId.value)

  return true
})

onUnmounted(() => {
  mercureService.disconnect()
  presenceStore.clearGamePresence(gameId.value)
})

async function initializeGame() {
  try {
    isLoading.value = true

    await Promise.all([
      gameStore.fetchGameById(gameId.value),
      mapStore.loadActiveMap(gameId.value),
      chatStore.loadRecentMessages(gameId.value, 30),
    ])

    if (!gameStore.isGameMaster && !gameStore.isPlayerInGame) {
      router.push({ name: 'games' })
      return
    }

    try {
      const response = await presenceApi.join(gameId.value)
      if (response.onlineUsers) {
        presenceStore.setOnlineUsers(gameId.value, response.onlineUsers)
      }
    } catch (error) {
      console.error('Erreur lors de la notification de présence:', error)
    }
  } catch (error) {
    console.error('Erreur lors du chargement de la partie:', error)
    router.push('/games')
  } finally {
    isLoading.value = false
  }
}

async function setupMercure() {
  try {
    await gameApi.getMercureToken(gameId.value)

    mercureService.connect(gameId.value)

    const checkConnection = setInterval(() => {
      isConnected.value = mercureService.isConnected()
      connectionState.value = mercureService.getConnectionState()

      if (isConnected.value) {
        clearInterval(checkConnection)
      }
    }, 500)
  } catch (error) {
    console.error('Erreur lors de la récupération du token Mercure:', error)
  }

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  mercureService.on('token', (event: any) => {
    mapStore.handleTokenEvent(event.data as MercureTokenEventData)
  })

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  mercureService.on('map', (event: any) => {
    mapStore.handleMapEvent(event.data as MercureMapEventData)
  })

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  mercureService.on('chat', (event: any) => {
    chatStore.handleChatMessage(event.data as MercureChatMessageData)
  })

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  mercureService.on('player', async (event: any) => {
    if (event.data?.action === 'kicked' && event.data?.userId === authStore.user?.id) {
      mercureService.disconnect()
      presenceStore.clearGamePresence(gameId.value)
      router.push({ name: 'games' })
      return
    }

    await gameStore.fetchGameById(gameId.value)
  })

  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  mercureService.on('presence', (event: any) => {
    const presenceData: MercurePresenceEventData = {
      gameId: event.gameId,
      userId: event.data.userId,
      type: event.data.type,
      onlineUsers: event.data.onlineUsers,
      timestamp: event.data.timestamp,
    }
    presenceStore.handlePresenceEvent(presenceData)
  })

  const heartbeatInterval = setInterval(async () => {
    if (mercureService.isConnected() && gameId.value && !isNaN(gameId.value)) {
      try {
        await presenceApi.heartbeat(gameId.value)
      } catch (error: unknown) {
        console.error("Erreur lors de l'envoi du heartbeat:", error)

        if (
          error &&
          typeof error === 'object' &&
          'statusCode' in error &&
          error.statusCode === 401
        ) {
          console.warn('⚠️ Session expirée détectée, redirection vers la page de connexion...')
        }
      }
    }
  }, 30000)

  onUnmounted(() => {
    clearInterval(heartbeatInterval)
  })
}

function setupBeforeUnload() {
  window.addEventListener('beforeunload', notifyDisconnectionBeacon)

  onUnmounted(() => {
    window.removeEventListener('beforeunload', notifyDisconnectionBeacon)
  })
}

const currentGame = computed(() => gameStore.currentGame)
const activeMap = computed(() => mapStore.activeMap)
const tokens = computed(() => mapStore.tokens)
const messages = computed(() => chatStore.sortedMessages)
const isGameMaster = computed(() => gameStore.isGameMaster)
const hasActiveMap = computed(() => mapStore.hasActiveMap)

function handleCreateMap() {
  showUploadModal.value = true
}

async function handleMapCreated() {
  await mapStore.loadActiveMap(gameId.value)
  showUploadModal.value = false
}

async function handleEditMap(map: GameMapType) {
  try {
    const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost/api'
    const response = await fetch(`${apiUrl}/games/${gameId.value}/maps/${map.id}`, {
      method: 'GET',
      credentials: 'include',
    })

    if (!response.ok) {
      throw new Error('Erreur lors du chargement de la carte')
    }

    const fullMapData = await response.json()
    editingMap.value = fullMapData
    showEditModal.value = true
  } catch (error) {
    console.error('Erreur lors du chargement de la carte:', error)
    alert('Impossible de charger les données de la carte')
  }
}

async function handleMapUpdated() {
  showEditModal.value = false
  editingMap.value = null
}

function handleToolChanged(tool: string) {
  selectedTool.value = tool
}

function handleZoomChanged(zoom: number) {
  mapZoom.value = zoom
}

function handleCenterMap() {
  if (gameMapRef.value) {
    gameMapRef.value.centerView()
  }
}

async function handleGridSettingsChanged(settings: {
  showGrid: boolean
  gridColor: string
  gridOpacity: number
}) {
  if (!mapStore.activeMap) return

  try {
    await mapStore.updateMapSettings(mapStore.activeMap.id, settings)
  } catch (error) {
    console.error('Erreur lors de la mise à jour des paramètres de grille:', error)
  }
}

function handleOpenSettings() {
  if (isGameMaster.value) {
    showSettingsModal.value = true
  }
}

function handleGoBack() {
  router.push('/games')
}

async function handleLeaveGame() {
  if (
    !confirm(
      'Êtes-vous sûr de vouloir quitter cette partie ?\n\nVous serez retiré en tant que membre et ne pourrez plus y accéder.'
    )
  )
    return

  try {
    await gameStore.leaveGame(gameId.value)
    router.push('/games')
  } catch (error) {
    console.error('Erreur en quittant la partie:', error)
  }
}

function handleCreateToken(position: { x: number; y: number }) {
  tokenCreationPosition.value = position
  showCreateTokenModal.value = true
}

async function handleTokenCreated() {
  showCreateTokenModal.value = false
  tokenCreationPosition.value = null
  selectedTool.value = 'select'
}
</script>

<template>
  <div v-if="isLoading" class="h-screen flex items-center justify-center bg-primary-900">
    <div class="text-center">
      <div
        class="animate-spin w-16 h-16 border-4 border-primary-500 border-t-transparent rounded-full mx-auto mb-4"
      ></div>
      <p class="text-secondary-50 text-lg">Chargement de la partie...</p>
    </div>
  </div>

  <div v-else class="h-screen bg-gradient-dark flex flex-col overflow-hidden">
    <GameHeader
      :game="currentGame"
      :is-connected="isConnected"
      :connection-state="connectionState"
      @go-back="handleGoBack"
      @open-settings="handleOpenSettings"
      @leave-game="handleLeaveGame"
    />

    <div class="flex-1 flex overflow-hidden relative">
      <div class="hidden lg:flex lg:flex-1 lg:flex-col min-w-0">
        <MapToolbar
          :is-game-master="isGameMaster"
          :game-id="gameId"
          @tool-changed="handleToolChanged"
          @open-upload-modal="handleCreateMap"
          @open-edit-modal="handleEditMap"
          @zoom-changed="handleZoomChanged"
          @center-map="handleCenterMap"
          @grid-settings-changed="handleGridSettingsChanged"
        />

        <div class="flex-1 relative overflow-hidden min-h-0">
          <EmptyMapState
            v-if="!hasActiveMap"
            :is-game-master="isGameMaster"
            @create-map="handleCreateMap"
          />

          <GameMap
            v-else
            ref="gameMapRef"
            :map="activeMap"
            :tokens="tokens"
            :editable="isGameMaster"
            :is-game-master="isGameMaster"
            :game-players="currentGame?.gamePlayers || []"
            :game-id="gameId"
            :selected-tool="selectedTool"
            :zoom="mapZoom"
            @create-token="handleCreateToken"
          />
        </div>
      </div>

      <Transition name="slide-left">
        <div
          v-if="rightPanelOpen"
          class="w-full lg:w-96 bg-secondary-800 lg:border-l border-secondary-700 flex flex-col"
        >
          <div class="flex border-b border-secondary-700">
            <button
              v-for="tab in ['chat', 'players', 'dice'] as const"
              :key="tab"
              @click="activeTab = tab"
              :class="[
                'flex-1 px-4 py-3 font-medium transition-colors',
                activeTab === tab
                  ? 'bg-primary-500 text-white'
                  : 'text-secondary-300 hover:bg-secondary-700',
              ]"
            >
              <span v-if="tab === 'chat'">💬 Chat</span>
              <span v-else-if="tab === 'players'">👥 Joueurs</span>
              <span v-else>🎲 Dés</span>
            </button>
          </div>

          <ChatPanel
            v-if="activeTab === 'chat'"
            :messages="messages"
            :game-id="gameId"
            :players="currentGame?.gamePlayers || []"
          />

          <PlayersList
            v-if="activeTab === 'players'"
            :players="currentGame?.gamePlayers || []"
            :game-master-id="currentGame?.gameMaster?.id"
            :max-players="currentGame?.maxPlayers"
            @player-kicked="gameStore.fetchGameById(gameId)"
            @player-invited="gameStore.fetchGameById(gameId)"
          />

          <DiceRoller v-if="activeTab === 'dice'" :game-id="gameId" />
        </div>
      </Transition>

      <button
        @click="rightPanelOpen = !rightPanelOpen"
        :class="[
          'hidden lg:flex absolute top-1/2 -translate-y-1/2 bg-secondary-800 border border-secondary-700 p-3 hover:bg-secondary-700 transition-all z-20 shadow-lg',
          rightPanelOpen ? 'right-96' : 'right-0',
          rightPanelOpen ? 'rounded-l-lg' : 'rounded-l-lg',
        ]"
        :title="
          rightPanelOpen
            ? 'Masquer le panel (chat, joueurs, dés)'
            : 'Afficher le panel (chat, joueurs, dés)'
        "
      >
        <div class="flex items-center gap-2">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5 text-secondary-300 transition-transform duration-300"
            :class="{ 'rotate-180': !rightPanelOpen }"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <polyline points="9 18 15 12 9 6"></polyline>
          </svg>
          <span v-if="!rightPanelOpen" class="text-xs text-secondary-400 font-medium"> Panel </span>
        </div>
      </button>
    </div>

    <UploadMapModal
      :show="showUploadModal"
      :game-id="gameId"
      @close="showUploadModal = false"
      @success="handleMapCreated"
    />

    <EditMapModal
      v-if="editingMap"
      :show="showEditModal"
      :game-id="gameId"
      :map="editingMap"
      @close="showEditModal = false"
      @success="handleMapUpdated"
    />

    <CreateTokenModal
      :show="showCreateTokenModal"
      :position="tokenCreationPosition"
      :game-id="gameId"
      :map-id="activeMap?.id || 0"
      @close="showCreateTokenModal = false"
      @success="handleTokenCreated"
    />

    <GameSettingsModal
      v-if="isGameMaster && currentGame"
      :show="showSettingsModal"
      :game="currentGame"
      @close="showSettingsModal = false"
      @updated="gameStore.fetchGameById(gameId)"
    />
  </div>
</template>

<style scoped>
.gradient-dark {
  background: linear-gradient(135deg, #1a0b2e, #0f172a);
}

.slide-left-enter-active,
.slide-left-leave-active {
  transition: transform 0.3s ease;
}

.slide-left-enter-from {
  transform: translateX(100%);
}

.slide-left-leave-to {
  transform: translateX(100%);
}
</style>
