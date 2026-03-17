<template>
  <nav class="bg-secondary-800 backdrop-blur-sm border-b border-secondary-700 relative z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <RouterLink to="/" class="flex items-center space-x-3">
          <div class="w-8 h-8 flex items-center justify-center">
            <img style="width: 100%; height: 100%" src="/images/logo.png" />
          </div>
          <span class="text-xl font-bold text-secondary-50">OnlyRoll</span>
        </RouterLink>

        <div class="hidden md:flex items-center space-x-1">
          <RouterLink
            to="/dashboard"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="[
              route.path === '/dashboard'
                ? 'bg-primary-500 text-white'
                : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
            ]"
          >
            Dashboard
          </RouterLink>
          <RouterLink
            to="/games"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="[
              route.path.startsWith('/games')
                ? 'bg-primary-500 text-white'
                : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
            ]"
          >
            {{ t('common.nav.games') }}
          </RouterLink>
          <RouterLink
            v-if="isAdmin"
            to="/admin"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="[
              route.path.startsWith('/admin')
                ? 'bg-primary-500 text-white'
                : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
            ]"
          >
            Admin
          </RouterLink>
        </div>

        <div class="flex items-center gap-1">
          <div class="relative" ref="bellRef">
            <button
              @click="toggleNotifications"
              class="relative p-2 rounded-lg text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700 transition-colors"
              title="Notifications"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="2"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
              </svg>
              <span
                v-if="notificationStore.invitationCount > 0"
                class="absolute -top-1 -right-1 bg-error text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"
              >
                {{
                  notificationStore.invitationCount > 9 ? '9+' : notificationStore.invitationCount
                }}
              </span>
            </button>

            <Transition name="fade-down">
              <div
                v-if="showNotifications"
                class="absolute right-0 top-12 w-80 sm:w-96 bg-secondary-800 border border-secondary-700 rounded-xl shadow-2xl z-50"
              >
                <div class="p-4 border-b border-secondary-700 flex items-center justify-between">
                  <h3 class="font-semibold text-secondary-50">
                    {{ t('common.nav.invitations.title') }}
                  </h3>
                  <button
                    @click="showNotifications = false"
                    class="text-secondary-400 hover:text-secondary-200 transition-colors"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                      stroke-width="2"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12"
                      />
                    </svg>
                  </button>
                </div>

                <div class="max-h-96 overflow-y-auto">
                  <div
                    v-if="notificationStore.isLoading"
                    class="p-6 text-center text-secondary-400"
                  >
                    <div
                      class="animate-spin w-6 h-6 border-2 border-primary-500 border-t-transparent rounded-full mx-auto mb-2"
                    ></div>
                    {{ t('common.nav.invitations.loading') }}
                  </div>

                  <div
                    v-else-if="notificationStore.invitations.length === 0"
                    class="p-6 text-center text-secondary-400"
                  >
                    <div class="text-3xl mb-2">🔔</div>
                    <p class="text-sm">{{ t('common.nav.invitations.empty') }}</p>
                  </div>

                  <div v-else class="divide-y divide-secondary-700">
                    <div
                      v-for="invitation in notificationStore.invitations"
                      :key="invitation.id"
                      class="p-4 hover:bg-secondary-700/50 transition-colors"
                    >
                      <div class="mb-3">
                        <p class="font-medium text-secondary-50 text-sm">
                          {{ invitation.game.name }}
                        </p>
                        <p class="text-xs text-secondary-400 mt-0.5">
                          {{
                            t('common.nav.invitations.gameMaster', {
                              pseudo: invitation.gameMaster.pseudo,
                            })
                          }}
                        </p>
                      </div>
                      <div class="flex gap-2">
                        <button
                          @click="handleAccept(invitation)"
                          :disabled="processingId === invitation.id"
                          class="flex-1 px-3 py-1.5 bg-primary-500 text-white text-sm rounded-lg hover:bg-primary-600 transition-colors disabled:opacity-50"
                        >
                          {{ t('common.nav.invitations.accept') }}
                        </button>
                        <button
                          @click="handleDecline(invitation)"
                          :disabled="processingId === invitation.id"
                          class="flex-1 px-3 py-1.5 bg-secondary-600 text-secondary-200 text-sm rounded-lg hover:bg-secondary-500 transition-colors disabled:opacity-50"
                        >
                          {{ t('common.nav.invitations.decline') }}
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </Transition>
          </div>

          <div class="relative hidden md:block" ref="langRef">
            <button
              @click="toggleLangDropdown"
              class="p-2 rounded-lg hover:bg-secondary-700 transition-colors"
              :title="t('common.nav.language.switchLanguage')"
            >
              <img
                :src="currentFlag"
                :alt="t('common.nav.language.current')"
                class="w-5 h-5 rounded-sm object-cover"
              />
            </button>

            <Transition name="fade-down">
              <div
                v-if="showLangDropdown"
                class="absolute right-0 top-12 bg-secondary-800 border border-secondary-700 rounded-xl shadow-2xl z-50 py-1 min-w-max"
              >
                <button
                  @click="switchLanguage(otherLocale)"
                  class="flex items-center gap-2 px-4 py-2 text-sm text-secondary-200 hover:bg-secondary-700 transition-colors w-full"
                >
                  <img
                    :src="otherFlag"
                    :alt="otherLocale.toUpperCase()"
                    class="w-5 h-5 rounded-sm object-cover"
                  />
                  <span>{{ t(`common.nav.language.${otherLocale}`) }}</span>
                </button>
              </div>
            </Transition>
          </div>

          <div class="hidden md:flex items-center space-x-4 ml-2">
            <UserProfileBadge />
            <button
              @click="handleLogout"
              class="px-4 py-2 text-sm text-secondary-300 hover:text-secondary-50 transition-colors"
            >
              {{ t('common.nav.logout') }}
            </button>
          </div>

          <button
            class="md:hidden p-2 rounded-lg text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700 transition-colors"
            :aria-expanded="isMobileMenuOpen"
            :aria-label="t('common.nav.navigationMenu')"
            @click="isMobileMenuOpen = !isMobileMenuOpen"
          >
            <svg
              v-if="!isMobileMenuOpen"
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M4 6h16M4 12h16M4 18h16"
              />
            </svg>
            <svg
              v-else
              class="w-6 h-6"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <div v-if="isMobileMenuOpen" class="md:hidden border-t border-secondary-700 bg-secondary-800">
      <div class="px-4 py-3 space-y-1">
        <RouterLink
          to="/dashboard"
          class="block px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="[
            route.path === '/dashboard'
              ? 'bg-primary-500 text-white'
              : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
          ]"
        >
          Dashboard
        </RouterLink>
        <RouterLink
          to="/games"
          class="block px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="[
            route.path.startsWith('/games')
              ? 'bg-primary-500 text-white'
              : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
          ]"
        >
          {{ t('common.nav.games') }}
        </RouterLink>
        <RouterLink
          v-if="isAdmin"
          to="/admin"
          class="block px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="[
            route.path.startsWith('/admin')
              ? 'bg-primary-500 text-white'
              : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
          ]"
        >
          Admin
        </RouterLink>
      </div>
      <div class="border-t border-secondary-700 px-4 py-3">
        <div class="flex items-center gap-3">
          <span class="text-sm text-secondary-400">{{ t('common.nav.language.label') }}</span>
          <div class="flex gap-2">
            <button
              v-for="lang in ['fr', 'en'] as const"
              :key="lang"
              @click="
                switchLanguage(lang)
                isMobileMenuOpen = false
              "
              class="p-1.5 rounded-lg transition-colors"
              :class="[
                locale === lang
                  ? 'bg-primary-500/20 ring-2 ring-primary-500'
                  : 'hover:bg-secondary-700',
              ]"
            >
              <img
                :src="`/images/flag/${lang}.png`"
                :alt="t(`common.nav.language.${lang}`)"
                class="w-6 h-6 rounded-sm object-cover"
              />
            </button>
          </div>
        </div>
      </div>

      <div class="border-t border-secondary-700 px-4 py-3 flex items-center justify-between">
        <UserProfileBadge />
        <button
          @click="handleLogout"
          class="px-4 py-2 text-sm text-secondary-300 hover:text-secondary-50 transition-colors"
        >
          {{ t('common.nav.logout') }}
        </button>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { computed, ref, watch, onMounted, onUnmounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuth } from '@/composables/useAuth'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notificationStore'
import { profileApi } from '@/services/api/profileApi'
import { logger } from '@/utils/logger'
import UserProfileBadge from '@/components/common/UserProfileBadge.vue'
import type { GameInvitation } from '@/types/game'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const { logout } = useAuth()
const authStore = useAuthStore()
const notificationStore = useNotificationStore()

const isAdmin = computed(() => authStore.isAdmin)
const isMobileMenuOpen = ref(false)
const showNotifications = ref(false)
const showLangDropdown = ref(false)
const processingId = ref<number | null>(null)
const bellRef = ref<HTMLElement | null>(null)
const langRef = ref<HTMLElement | null>(null)

const currentFlag = computed(() => `/images/flag/${locale.value}.png`)
const otherLocale = computed(() => (locale.value === 'fr' ? 'en' : 'fr'))
const otherFlag = computed(() => `/images/flag/${otherLocale.value}.png`)

watch(
  () => route.path,
  () => {
    isMobileMenuOpen.value = false
    showNotifications.value = false
    showLangDropdown.value = false
  }
)

onMounted(async () => {
  if (authStore.isAuthenticated) {
    await notificationStore.fetchInvitations()
    await notificationStore.connectToNotifications()
  }

  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  notificationStore.disconnectFromNotifications()
  document.removeEventListener('click', handleClickOutside)
})

function handleClickOutside(event: MouseEvent) {
  if (bellRef.value && !bellRef.value.contains(event.target as Node)) {
    showNotifications.value = false
  }
  if (langRef.value && !langRef.value.contains(event.target as Node)) {
    showLangDropdown.value = false
  }
}

function toggleNotifications() {
  showNotifications.value = !showNotifications.value
  showLangDropdown.value = false
}

function toggleLangDropdown() {
  showLangDropdown.value = !showLangDropdown.value
  showNotifications.value = false
}

async function switchLanguage(lang: 'fr' | 'en') {
  locale.value = lang
  localStorage.setItem('locale', lang)
  showLangDropdown.value = false

  if (authStore.isAuthenticated) {
    try {
      await profileApi.update({ language: lang })
      if (authStore.user) {
        authStore.user.language = lang
      }
    } catch (err) {
      logger.error('Failed to persist language preference:', err)
    }
  }
}

async function handleAccept(invitation: GameInvitation) {
  processingId.value = invitation.id
  try {
    await notificationStore.acceptInvitation(invitation.game.id)
    showNotifications.value = false
    router.push(`/games/${invitation.game.id}/play`)
  } catch {
    alert(t('common.nav.invitations.acceptError'))
  } finally {
    processingId.value = null
  }
}

async function handleDecline(invitation: GameInvitation) {
  processingId.value = invitation.id
  try {
    await notificationStore.declineInvitation(invitation.game.id)
  } catch {
    alert(t('common.nav.invitations.declineError'))
  } finally {
    processingId.value = null
  }
}

const handleLogout = async () => {
  await logout()
}
</script>

<style scoped>
.fade-down-enter-active,
.fade-down-leave-active {
  transition:
    opacity 0.15s ease,
    transform 0.15s ease;
}

.fade-down-enter-from,
.fade-down-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}
</style>
