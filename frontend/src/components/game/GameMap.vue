<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useMapStore } from '@/stores/mapStore'
import { useAuthStore } from '@/stores/auth'
import type { GameMap, GameToken, GamePlayer } from '@/types/game'
import { TokenType } from '@/types/game'
import EditTokenModal from './EditTokenModal.vue'

const props = defineProps<{
  map: GameMap | null
  tokens: GameToken[]
  editable: boolean
  selectedTool: string
  zoom: number
  isGameMaster?: boolean
  gamePlayers?: GamePlayer[]
  gameId: number
}>()

const mapStore = useMapStore()
const authStore = useAuthStore()

const selectedTokenId = ref<number | null>(null)
const draggingToken = ref<number | null>(null)
const dragStartPos = ref({ x: 0, y: 0 })
const showPermissionsModal = ref(false)
const permissionsTokenId = ref<number | null>(null)
const showEditTokenModal = ref(false)
const editingToken = ref<GameToken | null>(null)

const mapContainer = ref<HTMLElement | null>(null)
const mapElement = ref<HTMLElement | null>(null)

const gridSize = computed(() => props.map?.gridSize || 50)
const mapWidth = computed(() => (props.map?.width || 20) * gridSize.value)
const mapHeight = computed(() => (props.map?.height || 20) * gridSize.value)

const gridColor = computed(() => props.map?.settings?.gridColor || '#ffffff')
const gridOpacity = computed(() => props.map?.settings?.gridOpacity ?? 0.1)
const showGrid = computed(() => props.map?.settings?.showGrid ?? true)

const mapImageUrl = computed(() => {
  if (!props.map?.imageUrl) return null

  if (props.map.imageUrl.startsWith('http://') || props.map.imageUrl.startsWith('https://')) {
    return props.map.imageUrl
  }

  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const baseUrl = apiUrl.replace(/\/api$/, '')
  return `${baseUrl}${props.map.imageUrl}`
})

const zoomScale = computed(() => props.zoom / 100)

// ============================================
// Gestion des tokens - Utilise mapStore
// ============================================

/**
 * Tokens filtrés que l'utilisateur devrait voir
 * Les tokens invisibles ne sont visibles que pour le MJ ou les joueurs qui ont le contrôle
 */
const visibleTokens = computed(() => {
  return props.tokens.filter((token) => {
    if (token.isVisible) return true

    const canControl = canControlToken(token)
    return props.isGameMaster || canControl
  })
})

const emit = defineEmits<{
  createToken: [position: { x: number; y: number }]
}>()

function selectToken(tokenId: number) {
  if (selectedTokenId.value === tokenId) {
    selectedTokenId.value = null
  } else {
    selectedTokenId.value = tokenId
  }
}

function handleTokenMouseDown(event: MouseEvent, token: GameToken) {
  if (!props.editable || token.isLocked || props.selectedTool !== 'select') return

  if (!canControlToken(token)) {
    console.log("Vous n'avez pas la permission de contrôler ce token")
    return
  }

  draggingToken.value = token.id
  dragStartPos.value = { x: token.x, y: token.y }

  event.preventDefault()
}

function handleMouseMove(event: MouseEvent) {
  if (!draggingToken.value || !props.editable || !mapElement.value || !mapContainer.value) return

  const mapRect = mapElement.value.getBoundingClientRect()

  const mouseX = (event.clientX - mapRect.left) / zoomScale.value
  const mouseY = (event.clientY - mapRect.top) / zoomScale.value

  const x = Math.floor(mouseX / gridSize.value)
  const y = Math.floor(mouseY / gridSize.value)

  const constrainedX = Math.max(0, Math.min(x, (props.map?.width || 20) - 1))
  const constrainedY = Math.max(0, Math.min(y, (props.map?.height || 20) - 1))

  const tokenElement = document.querySelector(
    `[data-token-id="${draggingToken.value}"]`
  ) as HTMLElement
  if (tokenElement) {
    tokenElement.style.left = `${constrainedX * gridSize.value}px`
    tokenElement.style.top = `${constrainedY * gridSize.value}px`
  }
}

async function handleMouseUp() {
  if (!draggingToken.value) return

  const tokenElement = document.querySelector(
    `[data-token-id="${draggingToken.value}"]`
  ) as HTMLElement
  if (tokenElement) {
    const x = Math.floor(parseInt(tokenElement.style.left) / gridSize.value)
    const y = Math.floor(parseInt(tokenElement.style.top) / gridSize.value)

    if (x !== dragStartPos.value.x || y !== dragStartPos.value.y) {
      try {
        await mapStore.moveToken(draggingToken.value, x, y)
        console.log('Token déplacé:', { id: draggingToken.value, x, y })
      } catch (error) {
        console.error('Erreur déplacement token:', error)
        tokenElement.style.left = `${dragStartPos.value.x * gridSize.value}px`
        tokenElement.style.top = `${dragStartPos.value.y * gridSize.value}px`
      }
    }
  }

  draggingToken.value = null
}

// ============================================
// Actions sur les tokens - Utilise mapStore
// ============================================
async function toggleTokenVisibility(tokenId: number) {
  try {
    await mapStore.toggleTokenVisibility(tokenId)
    console.log('Visibilité du token changée')
  } catch (error) {
    console.error('Erreur toggle visibility:', error)
  }
}

async function toggleTokenLock(tokenId: number) {
  try {
    await mapStore.toggleTokenLock(tokenId)
    console.log('Verrouillage du token changé')
  } catch (error) {
    console.error('Erreur toggle lock:', error)
  }
}

async function deleteToken(tokenId: number) {
  if (!confirm('Supprimer ce token ?')) return

  try {
    await mapStore.deleteToken(tokenId)
    selectedTokenId.value = null
    console.log('Token supprimé')
  } catch (error) {
    console.error('Erreur suppression token:', error)
  }
}

// ============================================
// Gestion des permissions
// ============================================
function openPermissionsModal(tokenId: number) {
  permissionsTokenId.value = tokenId
  showPermissionsModal.value = true
}

function closePermissionsModal() {
  showPermissionsModal.value = false
  permissionsTokenId.value = null
}

// ============================================
// Édition de token
// ============================================
function openEditModal(tokenId: number) {
  const token = props.tokens.find((t) => t.id === tokenId)
  if (token) {
    editingToken.value = token
    showEditTokenModal.value = true
  }
}

function closeEditModal() {
  showEditTokenModal.value = false
  editingToken.value = null
}

function handleTokenUpdated() {
  closeEditModal()
}

async function togglePlayerPermission(tokenId: number, userId: number, hasPermission: boolean) {
  try {
    const action = hasPermission ? 'remove' : 'add'
    await mapStore.manageTokenPermissions(tokenId, action, userId)
    console.log(`Permission ${action} pour l'utilisateur ${userId}`)
  } catch (error) {
    console.error('Erreur gestion permission:', error)
  }
}

const currentPermissions = computed(() => {
  if (!permissionsTokenId.value) return []
  const token = props.tokens.find((t) => t.id === permissionsTokenId.value)
  return (token?.settings?.controllableBy as number[]) || []
})

// ============================================
// Création de token par clic
// ============================================
function handleMapClick(event: MouseEvent) {
  if (!props.editable || props.selectedTool !== 'token' || !mapElement.value) return

  const target = event.target as HTMLElement
  if (target.closest('[data-token-id]')) return

  const mapRect = mapElement.value.getBoundingClientRect()

  const mouseX = (event.clientX - mapRect.left) / zoomScale.value
  const mouseY = (event.clientY - mapRect.top) / zoomScale.value

  const x = Math.floor(mouseX / gridSize.value)
  const y = Math.floor(mouseY / gridSize.value)

  const constrainedX = Math.max(0, Math.min(x, (props.map?.width || 20) - 1))
  const constrainedY = Math.max(0, Math.min(y, (props.map?.height || 20) - 1))

  console.log('Clic pour créer token à:', { x: constrainedX, y: constrainedY })

  emit('createToken', { x: constrainedX, y: constrainedY })
}

// ============================================
// Helpers
// ============================================
function getTokenColor(type: TokenType): string {
  const colors = {
    [TokenType.CHARACTER]: '#6366f1',
    [TokenType.MONSTER]: '#ef4444',
    [TokenType.NPC]: '#10b981',
    [TokenType.OBJECT]: '#f59e0b',
  }
  return colors[type] || '#6366f1'
}

function getTokenSize(token: GameToken): number {
  return gridSize.value * (token.size || 1)
}

function getTokenImageUrl(imageUrl: string | undefined): string | null {
  if (!imageUrl) return null

  if (imageUrl.startsWith('http://') || imageUrl.startsWith('https://')) {
    return imageUrl
  }

  const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'
  const baseUrl = apiUrl.replace(/\/api$/, '')
  return `${baseUrl}${imageUrl}`
}

// ============================================
// Contrôle au clavier
// ============================================

/**
 * Vérifie si l'utilisateur peut contrôler un token.
 * Le MJ peut toujours contrôler. Les joueurs peuvent contrôler si leur userId est dans token.settings.controllableBy
 */
function canControlToken(token: GameToken): boolean {
  if (props.isGameMaster) return true

  const userId = authStore.user?.id
  if (!userId) return false

  const controllableBy = token.settings?.controllableBy as number[] | undefined
  return controllableBy?.includes(userId) || false
}

/**
 * Déplace un token avec les flèches directionnelles
 */
async function moveTokenByKey(direction: 'up' | 'down' | 'left' | 'right') {
  if (!selectedTokenId.value || !props.map) return

  const token = props.tokens.find((t) => t.id === selectedTokenId.value)
  if (!token) return

  if (!canControlToken(token)) {
    console.log("Vous n'avez pas la permission de contrôler ce token")
    return
  }

  if (token.isLocked) {
    console.log('Ce token est verrouillé')
    return
  }

  let newX = token.x
  let newY = token.y

  switch (direction) {
    case 'up':
      newY -= 1
      break
    case 'down':
      newY += 1
      break
    case 'left':
      newX -= 1
      break
    case 'right':
      newX += 1
      break
  }

  newX = Math.max(0, Math.min(newX, props.map.width - 1))
  newY = Math.max(0, Math.min(newY, props.map.height - 1))

  if (newX === token.x && newY === token.y) return

  try {
    await mapStore.moveToken(token.id, newX, newY)
    console.log('Token déplacé:', { id: token.id, x: newX, y: newY })
  } catch (error) {
    console.error('Erreur déplacement token:', error)
  }
}

/**
 * Gestionnaire d'événement clavier
 */
function handleKeyDown(event: KeyboardEvent) {
  const target = event.target as HTMLElement
  if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA') return

  switch (event.key) {
    case 'ArrowUp':
      event.preventDefault()
      moveTokenByKey('up')
      break
    case 'ArrowDown':
      event.preventDefault()
      moveTokenByKey('down')
      break
    case 'ArrowLeft':
      event.preventDefault()
      moveTokenByKey('left')
      break
    case 'ArrowRight':
      event.preventDefault()
      moveTokenByKey('right')
      break
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeyDown)
})

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeyDown)
})

// ============================================
// Fonction de centrage
// ============================================
function centerView() {
  if (!mapContainer.value) return

  let targetX = 0
  let targetY = 0

  if (selectedTokenId.value) {
    const token = props.tokens.find((t) => t.id === selectedTokenId.value)
    if (token) {
      const tokenCenterX = (token.x + (token.size || 1) / 2) * gridSize.value
      const tokenCenterY = (token.y + (token.size || 1) / 2) * gridSize.value

      targetX = tokenCenterX * zoomScale.value
      targetY = tokenCenterY * zoomScale.value
    }
  } else {
    targetX = (mapWidth.value / 2) * zoomScale.value
    targetY = (mapHeight.value / 2) * zoomScale.value
  }

  const container = mapContainer.value
  const scrollLeft = targetX - container.clientWidth / 2
  const scrollTop = targetY - container.clientHeight / 2

  container.scrollTo({
    left: scrollLeft,
    top: scrollTop,
    behavior: 'smooth',
  })
}

defineExpose({
  centerView,
  selectedTokenId,
})
</script>

<template>
  <div
    ref="mapContainer"
    class="w-full h-full relative overflow-auto select-none bg-secondary-900"
    @mousemove="handleMouseMove"
    @mouseup="handleMouseUp"
    @mouseleave="handleMouseUp"
  >
    <!-- Container de la carte avec dimensions et zoom -->
    <div
      ref="mapElement"
      @click="handleMapClick"
      class="relative bg-cover bg-center transition-transform duration-200"
      :class="{
        'cursor-crosshair': editable && selectedTool === 'token',
      }"
      :style="{
        width: mapWidth + 'px',
        height: mapHeight + 'px',
        minWidth: '100%',
        minHeight: '100%',
        backgroundImage: mapImageUrl
          ? `url(${mapImageUrl})`
          : 'linear-gradient(135deg, #1a0b2e 0%, #0f172a 100%)',
        transform: `scale(${zoomScale})`,
        transformOrigin: 'center center',
      }"
    >
      <!-- Grille -->
      <div
        v-if="map?.gridType === 'square' && showGrid"
        class="absolute inset-0 pointer-events-none"
        :style="{
          backgroundImage: `
            linear-gradient(to right, ${gridColor}${Math.round(gridOpacity * 255)
              .toString(16)
              .padStart(2, '0')} 1px, transparent 1px),
            linear-gradient(to bottom, ${gridColor}${Math.round(gridOpacity * 255)
              .toString(16)
              .padStart(2, '0')} 1px, transparent 1px)
          `,
          backgroundSize: `${gridSize}px ${gridSize}px`,
        }"
      />

      <!-- Tokens -->
      <div
        v-for="token in visibleTokens"
        :key="token.id"
        :data-token-id="token.id"
        @mousedown="(e) => handleTokenMouseDown(e, token)"
        @click.stop="selectToken(token.id)"
        class="absolute transition-all hover:scale-110"
        :class="{
          'ring-4 ring-primary-500 scale-110 z-20': selectedTokenId === token.id,
          'cursor-move': editable && selectedTool === 'select' && !token.isLocked,
          'cursor-pointer': selectedTool === 'select',
          'opacity-50': !token.isVisible,
          'z-10': selectedTokenId !== token.id,
        }"
        :style="{
          left: `${token.x * gridSize}px`,
          top: `${token.y * gridSize}px`,
          width: `${getTokenSize(token)}px`,
          height: `${getTokenSize(token)}px`,
        }"
      >
        <!-- Avatar du token -->
        <div
          class="w-full h-full rounded-full flex items-center justify-center font-bold text-white shadow-lg border-2 border-white overflow-hidden"
          :style="{ backgroundColor: getTokenColor(token.type) }"
        >
          <img
            v-if="getTokenImageUrl(token.imageUrl)"
            :src="getTokenImageUrl(token.imageUrl)!"
            :alt="token.name"
            class="w-full h-full object-cover"
          />
          <span v-else class="text-xl">
            {{ token.name.slice(0, 2).toUpperCase() }}
          </span>
        </div>

        <!-- Indicateurs -->
        <div class="absolute -top-1 -right-1 flex gap-1">
          <span v-if="token.isLocked" class="text-xs">🔒</span>
          <span v-if="!token.isVisible" class="text-xs">👁️</span>
        </div>

        <!-- Nom du token -->
        <div
          class="absolute -bottom-6 left-1/2 -translate-x-1/2 whitespace-nowrap text-xs font-medium px-2 py-1 rounded shadow-lg"
          :class="token.isVisible ? 'bg-black/80 text-white' : 'bg-gray-500/80 text-gray-300'"
        >
          {{ token.name }}
        </div>
      </div>

      <!-- Actions rapides pour token sélectionné -->
    </div>

    <!-- Menu token - Teleport pour positionner en dehors du container avec overflow -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="selectedTokenId && editable"
          class="fixed bottom-4 left-1/2 -translate-x-1/2 bg-secondary-800 border border-secondary-700 rounded-lg p-4 shadow-lg z-50"
        >
          <div class="flex gap-2">
            <button
              v-if="isGameMaster"
              @click="openEditModal(selectedTokenId)"
              class="btn-secondary text-sm"
              title="Modifier le token"
            >
              ✏️ Modifier
            </button>
            <button
              @click="toggleTokenVisibility(selectedTokenId)"
              class="btn-secondary text-sm"
              title="Toggle visibilité"
            >
              <span v-if="tokens.find((t) => t.id === selectedTokenId)?.isVisible">👁️</span>
              <span v-else>🚫</span>
              Visibilité
            </button>
            <button
              @click="toggleTokenLock(selectedTokenId)"
              class="btn-secondary text-sm"
              title="Verrouiller/Déverrouiller"
            >
              <span v-if="tokens.find((t) => t.id === selectedTokenId)?.isLocked">🔓</span>
              <span v-else>🔒</span>
              Verrouiller
            </button>
            <button
              v-if="isGameMaster"
              @click="openPermissionsModal(selectedTokenId)"
              class="btn-secondary text-sm"
              title="Gérer les permissions"
            >
              🎭 Permissions
            </button>
            <button
              @click="deleteToken(selectedTokenId)"
              class="px-3 py-2 bg-error text-white rounded-lg hover:bg-red-600 text-sm"
              title="Supprimer"
            >
              🗑️ Supprimer
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Modal de gestion des permissions -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showPermissionsModal && isGameMaster"
          class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
          @click="closePermissionsModal"
        >
          <div
            class="bg-secondary-800 border border-secondary-700 rounded-lg p-6 max-w-md w-full mx-4 shadow-xl"
            @click.stop
          >
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-white">Permissions de contrôle</h3>
              <button
                @click="closePermissionsModal"
                class="text-gray-400 hover:text-white"
                title="Fermer"
              >
                ✕
              </button>
            </div>

            <p class="text-sm text-gray-400 mb-4">
              Sélectionnez les joueurs qui peuvent contrôler ce token avec les flèches
              directionnelles
            </p>

            <div v-if="gamePlayers && gamePlayers.length > 0" class="space-y-2">
              <div
                v-for="gamePlayer in gamePlayers.filter((gp) => gp.role !== 'game_master')"
                :key="gamePlayer.id"
                class="flex items-center gap-3 p-3 bg-secondary-900 rounded-lg hover:bg-secondary-700 transition-colors"
              >
                <input
                  type="checkbox"
                  :id="`player-${gamePlayer.id}`"
                  :checked="currentPermissions.includes(gamePlayer.user.id)"
                  @change="
                    togglePlayerPermission(
                      permissionsTokenId!,
                      gamePlayer.user.id,
                      currentPermissions.includes(gamePlayer.user.id)
                    )
                  "
                  class="w-5 h-5 rounded border-gray-600 text-primary-500 focus:ring-primary-500 focus:ring-offset-secondary-900"
                />
                <label :for="`player-${gamePlayer.id}`" class="flex-1 text-white cursor-pointer">
                  {{ gamePlayer.user.pseudo }}
                </label>
              </div>
            </div>

            <div v-else class="text-center py-8 text-gray-500">Aucun joueur disponible</div>

            <div class="mt-6 flex justify-end">
              <button @click="closePermissionsModal" class="btn-primary">Fermer</button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Modal d'édition de token -->
    <EditTokenModal
      :show="showEditTokenModal"
      :token="editingToken"
      :game-id="gameId"
      :map-id="map?.id || 0"
      @close="closeEditModal"
      @success="handleTokenUpdated"
    />
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
