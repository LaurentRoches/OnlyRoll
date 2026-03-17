<script setup lang="ts">
import { ref, nextTick, watch, onMounted, computed } from 'vue'
import { useChatStore } from '@/stores/chatStore'
import { useAuthStore } from '@/stores/auth'
import DiceResultDisplay from '@/components/game/DiceResultDisplay.vue'
import SkeletonMessage from '@/components/game/SkeletonMessage.vue'
import { getErrorMessage } from '@/utils/errorHelpers'
import { useInfiniteScroll } from '@/composables/useInfiniteScroll'
import AccessibleModal from '@/components/a11y/AccessibleModal.vue'
import type {
  GameMessage,
  MessageType,
  DiceResult,
  LegacyDiceResult,
  GamePlayer,
} from '@/types/game'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps<{
  messages: GameMessage[]
  gameId: number
  players: GamePlayer[]
}>()

const chatStore = useChatStore()
const authStore = useAuthStore()

const messageInput = ref('')
const isInCharacter = ref(false)
const chatContainer = ref<HTMLElement | null>(null)
const isAtBottom = ref(true)
const chatInputError = ref<string | null>(null)
const loadOlderSentinel = ref<HTMLElement | null>(null)

async function loadOlderMessages() {
  if (!chatContainer.value) return
  const prevHeight = chatContainer.value.scrollHeight
  await chatStore.loadMoreMessages(props.gameId)
  await nextTick()
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight - prevHeight
  }
}

const infiniteScrollDisabled = computed(() => !chatStore.hasMore || chatStore.isLoading)

useInfiniteScroll(loadOlderSentinel, loadOlderMessages, {
  disabled: infiniteScrollDisabled,
  rootMargin: '50px',
})

let errorClearTimer: ReturnType<typeof setTimeout> | null = null

function showInputError(message: string) {
  if (errorClearTimer) clearTimeout(errorClearTimer)
  chatInputError.value = message
  errorClearTimer = setTimeout(() => {
    chatInputError.value = null
  }, 5000)
}

function clearInputError() {
  if (errorClearTimer) clearTimeout(errorClearTimer)
  chatInputError.value = null
}

const showChatHelpModal = ref(false)
const showSuggestions = ref(false)
const selectedSuggestionIndex = ref(0)
const textareaRef = ref<HTMLTextAreaElement | null>(null)

const visibleMessages = computed(() => {
  const currentUserId = authStore.user?.id
  if (!currentUserId) return props.messages

  return props.messages.filter((msg) => {
    if (msg.recipient) {
      return msg.user.id === currentUserId || msg.recipient.id === currentUserId
    }

    return true
  })
})

const filteredPlayers = computed(() => {
  if (!showSuggestions.value) return []

  const whisperMatch = messageInput.value.match(/^\/(w|whisper)\s+(\S*)$/)
  if (!whisperMatch) return []

  const searchTerm = whisperMatch[2].toLowerCase()

  return props.players
    .filter((p) => p.user.id !== authStore.user?.id)
    .filter((p) => p.user.pseudo.toLowerCase().includes(searchTerm))
    .slice(0, 5)
})

watch(messageInput, (newValue) => {
  const whisperMatch = newValue.match(/^\/(w|whisper)\s+(\S*)$/)
  showSuggestions.value = !!whisperMatch
  if (showSuggestions.value) {
    selectedSuggestionIndex.value = 0
  }
})

function selectPlayer(player: GamePlayer) {
  const whisperMatch = messageInput.value.match(/^\/(w|whisper)\s+/)
  if (whisperMatch) {
    messageInput.value = `${whisperMatch[0]}${player.user.pseudo} `
    showSuggestions.value = false
    nextTick(() => {
      textareaRef.value?.focus()
    })
  }
}

watch(
  () => props.messages.length,
  async () => {
    if (isAtBottom.value) {
      await nextTick()
      scrollToBottom()
    }
  }
)

function scrollToBottom() {
  if (chatContainer.value) {
    chatContainer.value.scrollTop = chatContainer.value.scrollHeight
  }
}

function handleScroll() {
  if (chatContainer.value) {
    const { scrollTop, scrollHeight, clientHeight } = chatContainer.value
    isAtBottom.value = scrollHeight - scrollTop - clientHeight < 50
  }
}

onMounted(() => {
  scrollToBottom()
})

watch(messageInput, () => {
  if (chatInputError.value) clearInputError()
})

async function sendMessage() {
  if (!messageInput.value.trim()) return

  clearInputError()

  try {
    if (messageInput.value.startsWith('/roll ') || messageInput.value.startsWith('/r ')) {
      const formula = messageInput.value.replace(/^\/(roll|r) /, '').trim()
      await chatStore.rollDice(props.gameId, formula, isInCharacter.value)
    } else if (messageInput.value.startsWith('/whisper ') || messageInput.value.startsWith('/w ')) {
      const content = messageInput.value.replace(/^\/(whisper|w) /, '').trim()

      const firstSpaceIndex = content.indexOf(' ')
      if (firstSpaceIndex === -1) {
        showInputError(t('game.chat.errors.invalidWhisperFormat'))
        return
      }

      const recipientPseudo = content.substring(0, firstSpaceIndex).trim()
      const message = content.substring(firstSpaceIndex + 1).trim()

      if (!message) {
        showInputError(t('game.chat.errors.emptyMessage'))
        return
      }

      const recipient = props.players.find(
        (p) => p.user.pseudo.toLowerCase() === recipientPseudo.toLowerCase()
      )

      if (!recipient) {
        showInputError(t('game.chat.errors.playerNotFound', { pseudo: recipientPseudo }))
        return
      }

      if (message.startsWith('/roll ') || message.startsWith('/r ')) {
        const formula = message.replace(/^\/(roll|r) /, '').trim()
        if (!formula) {
          showInputError(t('game.chat.errors.emptyDiceFormula'))
          return
        }
        await chatStore.rollDice(props.gameId, formula, isInCharacter.value, recipient.user.id)
      } else {
        await chatStore.sendWhisper(props.gameId, recipient.user.id, message)
      }
    } else if (messageInput.value.startsWith('/me ')) {
      const content = messageInput.value.replace('/me ', '').trim()
      await chatStore.sendEmote(props.gameId, content)
    } else {
      await chatStore.sendMessage(props.gameId, messageInput.value, isInCharacter.value)
    }

    messageInput.value = ''
  } catch (error) {
    showInputError(getErrorMessage(error, t('game.chat.errors.sendFailed')))
  }
}

function handleKeyDown(event: KeyboardEvent) {
  if (showSuggestions.value && filteredPlayers.value.length > 0) {
    if (event.key === 'ArrowDown') {
      event.preventDefault()
      selectedSuggestionIndex.value = Math.min(
        selectedSuggestionIndex.value + 1,
        filteredPlayers.value.length - 1
      )
      return
    }
    if (event.key === 'ArrowUp') {
      event.preventDefault()
      selectedSuggestionIndex.value = Math.max(selectedSuggestionIndex.value - 1, 0)
      return
    }
    if (event.key === 'Tab' || (event.key === 'Enter' && !event.shiftKey)) {
      event.preventDefault()
      const selectedPlayer = filteredPlayers.value[selectedSuggestionIndex.value]
      if (selectedPlayer) {
        selectPlayer(selectedPlayer)
      }
      return
    }
    if (event.key === 'Escape') {
      event.preventDefault()
      showSuggestions.value = false
      return
    }
  }

  if (event.key === 'Enter' && !event.shiftKey) {
    event.preventDefault()
    sendMessage()
  }
}

function getMessageClass(type: MessageType) {
  const classes = {
    system: 'bg-cyan-900/50 border-l-4 border-cyan-500',
    chat: 'bg-secondary-700',
    emote: 'bg-purple-900/50 border-l-4 border-purple-500 italic',
    whisper: 'bg-yellow-900/50 border-l-4 border-yellow-500',
    dice_roll: 'bg-purple-900/50 border-l-4 border-purple-500',
  }
  return classes[type] || 'bg-secondary-700'
}

function formatTime(dateString: string) {
  return new Date(dateString).toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit',
  })
}

function getMessageIcon(type: MessageType) {
  const icons = {
    system: '⚙️',
    chat: '💬',
    emote: '✨',
    whisper: '🤫',
    dice_roll: '🎲',
  }
  return icons[type] || '💬'
}

/**
 * Normalise diceResult
 *
 * Gère la compatibilité entre l'ancienne structure (fixtures) et la nouvelle :
 * - Ancienne : { config, results, total, timestamp }
 * - Nouvelle : { formula, results, total, modifier }
 *
 * Cette fonction assure que le composant fonctionne même avec d'anciennes données.
 */
function normalizeDiceResult(result: unknown): DiceResult | null {
  if (!result || typeof result !== 'object') return null

  if ('formula' in result && 'modifier' in result) {
    const newResult = result as {
      formula?: string
      rolls?: number[]
      results?: number[]
      keptRolls?: number[]
      dropped?: number[]
      total?: number
      modifier?: number
      keepType?: 'kh' | 'kl' | null
      keepCount?: number | null
      sidesPerDie?: number
    }

    return {
      formula: newResult.formula || '',
      results: newResult.results || newResult.rolls || [],
      keptRolls: newResult.keptRolls,
      dropped: newResult.dropped,
      total: newResult.total || 0,
      modifier: newResult.modifier || 0,
      keepType: newResult.keepType,
      keepCount: newResult.keepCount,
      sidesPerDie: newResult.sidesPerDie,
    }
  }

  if (
    'results' in result &&
    'config' in result &&
    Array.isArray((result as LegacyDiceResult).results)
  ) {
    const oldResult = result as LegacyDiceResult

    console.warn('Ancienne structure diceResult détectée, conversion en cours...')
    return {
      formula: oldResult.config?.dice || 'N/A',
      results: oldResult.results || [],
      total: oldResult.total || 0,
      modifier: 0,
    }
  }

  console.error('Structure diceResult invalide:', result)
  return null
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
</script>

<template>
  <div class="flex-1 flex flex-col overflow-hidden">
    <div ref="chatContainer" @scroll="handleScroll" class="flex-1 overflow-y-auto p-4 space-y-3">
      <div ref="loadOlderSentinel" class="h-1" aria-hidden="true"></div>

      <template v-if="chatStore.isLoading && chatStore.hasMore">
        <SkeletonMessage v-for="n in 3" :key="`skel-${n}`" />
      </template>

      <div
        v-for="msg in visibleMessages"
        :key="msg.id"
        :class="['rounded-lg p-3 transition-all hover:shadow-md', getMessageClass(msg.type)]"
      >
        <div class="flex items-center justify-between mb-1">
          <div class="flex items-center gap-2">
            <span class="text-sm">{{ getMessageIcon(msg.type) }}</span>
            <span class="font-semibold text-white text-sm">{{ msg.user.pseudo }}</span>
            <span
              v-if="msg.isInCharacter"
              class="px-2 py-0.5 bg-primary-500/30 text-primary-200 text-xs font-medium rounded"
            >
              IC
            </span>
            <span
              v-if="msg.recipient"
              class="px-2 py-0.5 bg-yellow-500/30 text-yellow-200 text-xs font-medium rounded"
            >
              → {{ msg.recipient.pseudo }}
            </span>
          </div>
          <span class="text-xs text-secondary-400">{{ formatTime(msg.createdAt) }}</span>
        </div>

        <p class="text-secondary-100 text-sm">{{ msg.content }}</p>

        <DiceResultDisplay
          v-if="normalizeDiceResult(msg.diceResult)"
          :diceResult="normalizeDiceResult(msg.diceResult)!"
          class="mt-2 bg-black/30"
        />
      </div>

      <div v-if="visibleMessages.length === 0" class="text-center py-8 text-secondary-400">
        <p class="text-lg mb-2">💬</p>
        <p>{{ t('game.chat.empty.title') }}</p>
        <p class="text-sm mt-1">{{ t('game.chat.empty.subtitle') }}</p>
      </div>
    </div>

    <Transition name="fade">
      <button
        v-if="!isAtBottom"
        @click="scrollToBottom"
        class="absolute bottom-24 right-8 px-3 py-2 bg-primary-500 text-white rounded-full shadow-lg hover:bg-primary-600 transition-colors"
        :title="t('game.chat.scrollDown')"
      >
        ↓
      </button>
    </Transition>

    <div class="border-t border-secondary-700 p-4 bg-secondary-800">
      <div class="flex items-center gap-2 mb-2">
        <button
          @click="isInCharacter = !isInCharacter"
          :class="[
            'px-3 py-1 text-xs rounded font-medium transition-colors',
            isInCharacter
              ? 'bg-primary-500 text-white shadow-purple'
              : 'bg-secondary-700 text-secondary-300 hover:bg-secondary-600',
          ]"
        >
          {{
            isInCharacter
              ? '🎭 ' + t('game.chat.inCharacter')
              : '🗣️ ' + t('game.chat.outOfCharacter')
          }}
        </button>

        <div class="relative group ml-auto">
          <button
            @click="showChatHelpModal = true"
            class="w-6 h-6 rounded-full bg-secondary-700 text-secondary-400 hover:bg-primary-500 hover:text-white flex items-center justify-center text-xs font-bold transition-colors"
            :aria-label="t('game.chat.help.tooltip')"
          >
            ?
          </button>
          <div
            class="absolute bottom-full right-0 mb-2 px-3 py-2 bg-secondary-900 text-secondary-200 text-xs rounded-lg shadow-lg w-56 opacity-0 group-hover:opacity-100 pointer-events-none transition-opacity z-10 border border-secondary-700"
          >
            {{ t('game.chat.help.tooltip') }}
          </div>
        </div>
      </div>

      <Transition name="fade">
        <div
          v-if="chatInputError"
          class="flex items-start gap-2 mb-2 px-3 py-2 bg-red-900/40 border border-red-500/50 rounded-lg text-sm text-red-300"
          role="alert"
        >
          <span class="flex-shrink-0 mt-0.5">⚠️</span>
          <span>{{ chatInputError }}</span>
          <button
            @click="clearInputError"
            class="ml-auto flex-shrink-0 text-red-400 hover:text-red-200 transition-colors"
            :aria-label="t('game.chat.closeError')"
          >
            ✕
          </button>
        </div>
      </Transition>

      <div class="relative">
        <Transition name="slide-up">
          <div
            v-if="showSuggestions && filteredPlayers.length > 0"
            class="absolute bottom-full left-0 right-0 mb-2 bg-secondary-700 border border-secondary-600 rounded-lg shadow-xl overflow-hidden max-h-48"
          >
            <div class="px-3 py-2 bg-secondary-800 border-b border-secondary-600">
              <span class="text-xs text-secondary-400">
                {{ t('game.chat.suggestions.title') }}
              </span>
            </div>
            <div class="overflow-y-auto max-h-40">
              <button
                v-for="(player, index) in filteredPlayers"
                :key="player.id"
                @click="selectPlayer(player)"
                :class="[
                  'w-full text-left px-3 py-2 hover:bg-secondary-600 transition-colors flex items-center gap-2',
                  index === selectedSuggestionIndex
                    ? 'bg-primary-500/20 border-l-2 border-primary-500'
                    : '',
                ]"
              >
                <div
                  :class="[
                    'w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold',
                    getAvatarColor(player.user.id),
                  ]"
                >
                  {{ player.user.pseudo.slice(0, 2).toUpperCase() }}
                </div>
                <span class="text-secondary-100">{{ player.user.pseudo }}</span>
              </button>
            </div>
          </div>
        </Transition>

        <textarea
          ref="textareaRef"
          v-model="messageInput"
          @keydown="handleKeyDown"
          rows="2"
          :placeholder="t('game.chat.placeholder')"
          class="form-input resize-none pr-12"
          :disabled="chatStore.isSending"
        />

        <button
          @click="sendMessage"
          :disabled="!messageInput.trim() || chatStore.isSending"
          class="absolute right-2 bottom-2 p-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          :title="t('game.chat.sendTitle')"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
          >
            <line x1="22" y1="2" x2="11" y2="13"></line>
            <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
          </svg>
        </button>
      </div>
    </div>

    <AccessibleModal
      :is-open="showChatHelpModal"
      :title="t('game.chat.help.modalTitle')"
      size="lg"
      @close="showChatHelpModal = false"
    >
      <div class="space-y-6">
        <div>
          <h3 class="text-sm font-semibold text-secondary-50 uppercase tracking-wider mb-3">
            {{ t('game.chat.help.commands.title') }}
          </h3>
          <div class="space-y-3">
            <div
              v-for="cmd in ['roll', 'whisper', 'whisperRoll', 'emote']"
              :key="cmd"
              class="bg-secondary-700/50 rounded-lg p-3"
            >
              <div class="flex items-center gap-2 mb-1">
                <code class="text-primary-400 font-mono text-sm font-bold">
                  {{ t(`game.chat.help.commands.${cmd}.name`) }}
                </code>
                <span class="text-secondary-400 text-xs font-mono">
                  {{ t(`game.chat.help.commands.${cmd}.syntax`) }}
                </span>
              </div>
              <p class="text-secondary-300 text-sm">
                {{ t(`game.chat.help.commands.${cmd}.description`) }}
              </p>
            </div>
          </div>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-secondary-50 uppercase tracking-wider mb-3">
            {{ t('game.chat.help.dice.title') }}
          </h3>
          <div class="bg-secondary-700/50 rounded-lg p-3 space-y-2">
            <p class="text-secondary-300 text-sm">
              {{ t('game.chat.help.dice.quickDice') }}
            </p>
            <p class="text-secondary-300 text-sm">
              {{ t('game.chat.help.dice.commonRolls') }}
            </p>
            <p class="text-secondary-400 text-xs italic">
              {{ t('game.chat.help.dice.commonRollsHint') }}
            </p>
          </div>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-secondary-50 uppercase tracking-wider mb-3">
            {{ t('game.chat.help.advantage.title') }}
          </h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            <div class="bg-secondary-700/50 rounded-lg p-3 border-l-4 border-secondary-500">
              <p class="text-secondary-300 text-sm">
                {{ t('game.chat.help.advantage.normal') }}
              </p>
            </div>
            <div class="bg-secondary-700/50 rounded-lg p-3 border-l-4 border-green-500">
              <p class="text-secondary-300 text-sm">
                {{ t('game.chat.help.advantage.advantage') }}
              </p>
            </div>
            <div class="bg-secondary-700/50 rounded-lg p-3 border-l-4 border-red-500">
              <p class="text-secondary-300 text-sm">
                {{ t('game.chat.help.advantage.disadvantage') }}
              </p>
            </div>
            <div class="bg-secondary-700/50 rounded-lg p-3 border-l-4 border-yellow-500">
              <p class="text-secondary-300 text-sm">
                {{ t('game.chat.help.advantage.super') }}
              </p>
            </div>
          </div>
        </div>

        <div>
          <h3 class="text-sm font-semibold text-secondary-50 uppercase tracking-wider mb-3">
            {{ t('game.chat.help.modes.title') }}
          </h3>
          <div class="space-y-2">
            <div class="bg-secondary-700/50 rounded-lg p-3">
              <p class="text-sm font-medium text-primary-400 mb-1">
                {{ t('game.chat.help.modes.inCharacter') }}
              </p>
              <p class="text-secondary-300 text-sm">
                {{ t('game.chat.help.modes.inCharacterDesc') }}
              </p>
            </div>
            <div class="bg-secondary-700/50 rounded-lg p-3">
              <p class="text-sm font-medium text-secondary-200 mb-1">
                {{ t('game.chat.help.modes.outOfCharacter') }}
              </p>
              <p class="text-secondary-300 text-sm">
                {{ t('game.chat.help.modes.outOfCharacterDesc') }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <template #footer="{ close }">
        <button
          type="button"
          class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg transition-colors"
          @click="close"
        >
          {{ t('game.chat.help.close') }}
        </button>
      </template>
    </AccessibleModal>
  </div>
</template>

<style scoped>
.shadow-purple {
  box-shadow: 0 4px 14px 0 rgba(99, 102, 241, 0.39);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.2s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
