<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { userApi } from '@/services/api/userApi'
import { gameApi } from '@/services/api/gameApi'
import type { UserSearchResult, GamePlayer } from '@/types/game'
import { PlayerStatus } from '@/types/game'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps<{
  gameId: number
  maxPlayers: number
  currentPlayers: GamePlayer[]
}>()

const emit = defineEmits<{
  success: []
  close: []
}>()

const searchQuery = ref('')
const searchResults = ref<UserSearchResult[]>([])
const isSearching = ref(false)
const isInviting = ref(false)
const selectedUser = ref<UserSearchResult | null>(null)
const error = ref<string | null>(null)
let debounceTimer: ReturnType<typeof setTimeout> | null = null

const existingPlayerIds = computed(
  () =>
    new Set(
      props.currentPlayers
        .filter((p) => p.status !== PlayerStatus.KICKED && p.status !== PlayerStatus.LEFT)
        .map((p) => p.user.id)
    )
)

watch(searchQuery, (value) => {
  error.value = null
  if (debounceTimer) clearTimeout(debounceTimer)

  if (value.length < 2) {
    searchResults.value = []
    return
  }

  debounceTimer = setTimeout(async () => {
    isSearching.value = true
    try {
      searchResults.value = await userApi.searchUsers(value)
    } catch {
      searchResults.value = []
    } finally {
      isSearching.value = false
    }
  }, 300)
})

function isAlreadyMember(userId: number): boolean {
  return existingPlayerIds.value.has(userId)
}

function selectUser(user: UserSearchResult) {
  if (isAlreadyMember(user.id)) return
  selectedUser.value = user
  searchQuery.value = user.pseudo
  searchResults.value = []
}

function clearSelectedUser() {
  selectedUser.value = null
  searchQuery.value = ''
}

async function confirmInvite() {
  if (!selectedUser.value) return

  isInviting.value = true
  error.value = null
  try {
    await gameApi.invite(props.gameId, selectedUser.value.id)
    emit('success')
    emit('close')
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : t('game.invite.error')
  } finally {
    isInviting.value = false
  }
}
</script>

<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60"
      @click.self="emit('close')"
    >
      <div
        class="bg-secondary-800 rounded-xl border border-secondary-700 shadow-2xl w-full max-w-md mx-4"
      >
        <div class="flex items-center justify-between p-4 border-b border-secondary-700">
          <h2 class="font-semibold text-secondary-50 text-lg">{{ t('game.invite.title') }}</h2>
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

        <div class="p-4 space-y-4">
          <div class="relative">
            <label class="block text-sm font-medium text-secondary-300 mb-1">
              {{ t('game.invite.search.label') }}
            </label>
            <div class="relative">
              <input
                v-model="searchQuery"
                type="text"
                :placeholder="t('game.invite.search.placeholder')"
                class="w-full bg-secondary-700 border border-secondary-600 text-secondary-50 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent placeholder-secondary-500"
                autocomplete="off"
              />
              <div v-if="isSearching" class="absolute right-3 top-1/2 -translate-y-1/2">
                <div
                  class="animate-spin w-4 h-4 border-2 border-primary-500 border-t-transparent rounded-full"
                ></div>
              </div>
            </div>

            <div
              v-if="searchResults.length > 0 && !selectedUser"
              class="absolute z-10 w-full top-full mt-1 bg-secondary-700 border border-secondary-600 rounded-lg shadow-xl overflow-hidden"
            >
              <div
                v-for="user in searchResults"
                :key="user.id"
                @click="selectUser(user)"
                :class="[
                  'flex items-center gap-3 px-4 py-3 transition-colors',
                  isAlreadyMember(user.id)
                    ? 'opacity-50 cursor-not-allowed'
                    : 'cursor-pointer hover:bg-secondary-600',
                ]"
              >
                <div
                  class="w-8 h-8 rounded-full bg-primary-500/30 flex items-center justify-center text-primary-200 text-sm font-medium flex-shrink-0"
                >
                  {{ user.pseudo.slice(0, 2).toUpperCase() }}
                </div>
                <span class="text-secondary-50 text-sm">{{ user.pseudo }}</span>
                <span v-if="isAlreadyMember(user.id)" class="ml-auto text-xs text-secondary-400">
                  {{ t('game.invite.alreadyMember') }}
                </span>
              </div>
            </div>
          </div>

          <div
            v-if="selectedUser"
            class="flex items-center gap-3 p-3 bg-primary-500/10 border border-primary-500/30 rounded-lg"
          >
            <div
              class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center text-white text-sm font-medium"
            >
              {{ selectedUser.pseudo.slice(0, 2).toUpperCase() }}
            </div>
            <span class="text-secondary-50 font-medium">{{ selectedUser.pseudo }}</span>
            <button
              @click="clearSelectedUser"
              class="ml-auto text-secondary-400 hover:text-secondary-200 transition-colors"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <p v-if="error" class="text-error text-sm">{{ error }}</p>
        </div>

        <div class="flex gap-3 p-4 border-t border-secondary-700">
          <button
            @click="emit('close')"
            class="flex-1 px-4 py-2 bg-secondary-700 text-secondary-200 rounded-lg hover:bg-secondary-600 transition-colors text-sm"
          >
            {{ t('game.invite.cancel') }}
          </button>
          <button
            @click="confirmInvite"
            :disabled="!selectedUser || isInviting"
            class="flex-1 px-4 py-2 bg-primary-500 text-white rounded-lg hover:bg-primary-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed text-sm font-medium"
          >
            <span v-if="isInviting">{{ t('game.invite.submit.sending') }}</span>
            <span v-else>{{ t('game.invite.submit.send') }}</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
