<template>
  <div class="profile-view min-h-screen bg-secondary-900">
    <a
      href="#main-content"
      class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:p-4 focus:bg-primary-600 focus:text-white focus:z-50"
    >
      {{ t('profile.skipLink') }}
    </a>

    <DashboardNav />

    <header role="banner" class="bg-secondary-800 border-b border-secondary-700">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
          <div>
            <h1 class="text-2xl font-bold text-secondary-50">{{ t('profile.header.title') }}</h1>
            <p class="text-secondary-400 mt-1">{{ t('profile.header.subtitle') }}</p>
          </div>
          <RouterLink
            to="/dashboard"
            class="flex items-center px-4 py-2 bg-secondary-700 hover:bg-secondary-600 text-secondary-200 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500"
          >
            <svg
              class="w-5 h-5 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M10 19l-7-7m0 0l7-7m-7 7h18"
              />
            </svg>
            {{ t('profile.header.backToDashboard') }}
          </RouterLink>
        </div>
      </div>
    </header>

    <main
      id="main-content"
      role="main"
      tabindex="-1"
      class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
    >
      <div
        v-if="isLoading"
        class="flex justify-center py-12"
        role="status"
        :aria-label="t('profile.loading.ariaLabel')"
      >
        <div
          class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"
          aria-hidden="true"
        ></div>
        <span class="sr-only">{{ t('profile.loading.screenReader') }}</span>
      </div>

      <div
        v-else-if="loadError"
        role="alert"
        class="bg-red-500/10 border border-red-500/50 rounded-lg p-4 text-red-400"
      >
        <strong class="font-semibold">{{ t('profile.error.label') }}</strong> {{ loadError }}
        <button
          type="button"
          class="ml-4 text-red-300 underline hover:no-underline focus:outline-none focus:ring-2 focus:ring-red-500 rounded"
          @click="loadProfile"
        >
          {{ t('profile.error.retry') }}
        </button>
      </div>

      <div v-else-if="profile" class="space-y-8">
        <section
          aria-labelledby="avatar-section-heading"
          class="bg-secondary-800 rounded-lg p-6 border border-secondary-700"
        >
          <h2 id="avatar-section-heading" class="text-lg font-semibold text-secondary-50 mb-4">
            {{ t('profile.avatar.sectionTitle') }}
          </h2>
          <AvatarUploader
            :avatar="profile.avatar"
            :pseudo="profile.pseudo"
            @update:avatar="handleAvatarUpdate"
          />
        </section>

        <section
          aria-labelledby="info-section-heading"
          class="bg-secondary-800 rounded-lg p-6 border border-secondary-700"
        >
          <h2 id="info-section-heading" class="text-lg font-semibold text-secondary-50 mb-4">
            {{ t('profile.info.sectionTitle') }}
          </h2>

          <form @submit.prevent="handleProfileUpdate" novalidate class="space-y-6">
            <div class="form-group">
              <label :for="pseudoId" class="form-label">
                {{ t('profile.info.pseudo.label') }}
                <span class="text-red-500" aria-hidden="true">*</span>
                <span class="sr-only">{{ t('profile.info.pseudo.required') }}</span>
              </label>
              <input
                :id="pseudoId"
                v-model="editForm.pseudo"
                type="text"
                autocomplete="username"
                :aria-describedby="profileErrors.pseudo ? pseudoErrorId : undefined"
                :aria-invalid="profileErrors.pseudo ? 'true' : 'false'"
                :aria-required="true"
                class="form-input"
                :class="{ 'form-input--error': profileErrors.pseudo }"
              />
              <p
                v-if="profileErrors.pseudo"
                :id="pseudoErrorId"
                class="form-error"
                role="alert"
                aria-live="assertive"
              >
                {{ profileErrors.pseudo }}
              </p>
            </div>

            <div class="form-group">
              <label :for="emailId" class="form-label"> {{ t('profile.info.email.label') }} </label>
              <input
                :id="emailId"
                :value="profile.email"
                type="email"
                disabled
                readonly
                aria-readonly="true"
                class="form-input bg-secondary-800 text-secondary-400 cursor-not-allowed"
              />
              <p class="mt-1 text-xs text-secondary-500">
                {{ t('profile.info.email.hint') }}
              </p>
            </div>

            <div class="form-group">
              <label :for="timezoneId" class="form-label">
                {{ t('profile.info.timezone.label') }}
              </label>
              <select :id="timezoneId" v-model="editForm.timezone" class="form-input">
                <option value="Europe/Paris">Europe/Paris (UTC+1/+2)</option>
                <option value="Europe/London">Europe/London (UTC+0/+1)</option>
                <option value="America/New_York">America/New_York (UTC-5/-4)</option>
                <option value="America/Los_Angeles">America/Los_Angeles (UTC-8/-7)</option>
                <option value="Asia/Tokyo">Asia/Tokyo (UTC+9)</option>
                <option value="UTC">UTC</option>
              </select>
            </div>

            <div class="form-group">
              <label :for="languageId" class="form-label">
                {{ t('profile.info.language.label') }}
              </label>
              <select :id="languageId" v-model="editForm.language" class="form-input">
                <option value="fr">{{ t('profile.info.language.options.fr') }}</option>
                <option value="en">{{ t('profile.info.language.options.en') }}</option>
              </select>
            </div>

            <div
              v-if="profileSuccess"
              role="status"
              aria-live="polite"
              class="p-4 bg-green-500/10 border border-green-500/50 rounded-lg text-green-400"
            >
              {{ profileSuccess }}
            </div>

            <div
              v-if="profileGlobalError"
              role="alert"
              aria-live="assertive"
              class="p-4 bg-red-500/10 border border-red-500/50 rounded-lg text-red-400"
            >
              {{ profileGlobalError }}
            </div>

            <div class="flex justify-end">
              <button
                type="submit"
                :disabled="isUpdating"
                class="px-6 py-2 bg-primary-600 hover:bg-primary-500 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-secondary-900 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                <span v-if="isUpdating" class="flex items-center">
                  <span
                    class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"
                    aria-hidden="true"
                  ></span>
                  {{ t('profile.info.submit.saving') }}
                </span>
                <span v-else>{{ t('profile.info.submit.save') }}</span>
              </button>
            </div>
          </form>
        </section>

        <section
          aria-labelledby="security-section-heading"
          class="bg-secondary-800 rounded-lg p-6 border border-secondary-700"
        >
          <PasswordChangeForm @success="handlePasswordChanged" />
        </section>

        <section
          aria-labelledby="account-section-heading"
          class="bg-secondary-800 rounded-lg p-6 border border-secondary-700"
        >
          <h2 id="account-section-heading" class="text-lg font-semibold text-secondary-50 mb-4">
            {{ t('profile.account.sectionTitle') }}
          </h2>

          <dl class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <dt class="text-secondary-400">{{ t('profile.account.memberSince') }}</dt>
              <dd class="text-secondary-200">{{ formatDate(profile.createdAt) }}</dd>
            </div>
            <div>
              <dt class="text-secondary-400">{{ t('profile.account.lastLogin') }}</dt>
              <dd class="text-secondary-200">
                {{
                  profile.lastLogin
                    ? formatDate(profile.lastLogin)
                    : t('profile.account.lastLoginNever')
                }}
              </dd>
            </div>
            <div>
              <dt class="text-secondary-400">{{ t('profile.account.accountStatus') }}</dt>
              <dd>
                <span
                  v-if="profile.isVerified"
                  class="inline-flex items-center px-2 py-1 text-xs rounded bg-green-500/20 text-green-400"
                >
                  <svg
                    class="w-3 h-3 mr-1"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M5 13l4 4L19 7"
                    />
                  </svg>
                  {{ t('profile.account.verified') }}
                </span>
                <span
                  v-else
                  class="inline-flex items-center px-2 py-1 text-xs rounded bg-yellow-500/20 text-yellow-400"
                >
                  {{ t('profile.account.pendingVerification') }}
                </span>
              </dd>
            </div>
            <div>
              <dt class="text-secondary-400">{{ t('profile.account.roles') }}</dt>
              <dd class="flex flex-wrap gap-1">
                <span
                  v-for="role in profile.roles"
                  :key="role"
                  class="inline-block px-2 py-1 text-xs rounded"
                  :class="{
                    'bg-primary-500/20 text-primary-400': role === 'ROLE_ADMIN',
                    'bg-secondary-600 text-secondary-300': role === 'ROLE_USER',
                    'bg-blue-500/20 text-blue-400': role === 'ROLE_GM',
                  }"
                >
                  {{ role.replace('ROLE_', '') }}
                </span>
              </dd>
            </div>
          </dl>
        </section>
      </div>
    </main>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, useId } from 'vue'
import { useI18n } from 'vue-i18n'
import { profileApi, type UserProfile } from '@/services/api/profileApi'
import { AvatarUploader, PasswordChangeForm } from '@/components/profile'
import DashboardNav from '@/components/dashboard/DashboardNav.vue'
import { useAuthStore } from '@/stores/auth'

const { t, locale } = useI18n()
const authStore = useAuthStore()

const pseudoId = useId()
const pseudoErrorId = useId()
const emailId = useId()
const timezoneId = useId()
const languageId = useId()

const isLoading = ref(true)
const loadError = ref<string | null>(null)
const profile = ref<UserProfile | null>(null)

const editForm = reactive({
  pseudo: '',
  timezone: '',
  language: '',
})

const profileErrors = reactive<Record<string, string>>({})
const profileGlobalError = ref<string | null>(null)
const profileSuccess = ref<string | null>(null)
const isUpdating = ref(false)

const formatDate = (dateString: string): string => {
  const date = new Date(dateString)
  return date.toLocaleDateString(locale.value === 'fr' ? 'fr-FR' : 'en-US', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const loadProfile = async () => {
  isLoading.value = true
  loadError.value = null

  try {
    const data = await profileApi.get()
    profile.value = data

    editForm.pseudo = data.pseudo
    editForm.timezone = data.timezone || 'Europe/Paris'
    editForm.language = data.language || 'fr'
  } catch (err) {
    loadError.value = err instanceof Error ? err.message : t('profile.error.loadProfile')
  } finally {
    isLoading.value = false
  }
}

const handleProfileUpdate = async () => {
  Object.keys(profileErrors).forEach((key) => delete profileErrors[key])
  profileGlobalError.value = null
  profileSuccess.value = null

  if (!editForm.pseudo.trim()) {
    profileErrors.pseudo = t('profile.info.pseudo.errors.required')
    return
  }

  if (editForm.pseudo.length < 3) {
    profileErrors.pseudo = t('profile.info.pseudo.errors.minLength')
    return
  }

  isUpdating.value = true

  try {
    const updatedProfile = await profileApi.update({
      pseudo: editForm.pseudo,
      timezone: editForm.timezone,
      language: editForm.language,
    })

    profile.value = updatedProfile
    locale.value = editForm.language
    localStorage.setItem('locale', editForm.language)
    profileSuccess.value = t('profile.info.success')

    setTimeout(() => {
      profileSuccess.value = null
    }, 5000)
  } catch (err: unknown) {
    if (err && typeof err === 'object' && 'response' in err) {
      const response = (
        err as { response?: { data?: { error?: string; violations?: Record<string, string> } } }
      ).response

      if (response?.data?.violations) {
        Object.assign(profileErrors, response.data.violations)
      } else if (response?.data?.error) {
        profileGlobalError.value = response.data.error
      } else {
        profileGlobalError.value = t('profile.error.generic')
      }
    } else {
      profileGlobalError.value = err instanceof Error ? err.message : t('profile.error.generic')
    }
  } finally {
    isUpdating.value = false
  }
}

const handleAvatarUpdate = (newAvatar: string | null) => {
  if (profile.value) {
    profile.value.avatar = newAvatar
  }
  if (authStore.user) {
    authStore.user.avatar = newAvatar
  }
}

const handlePasswordChanged = () => {
  console.log('Mot de passe modifié avec succès')
}

onMounted(() => {
  loadProfile()
})
</script>

<style scoped>
.form-label {
  @apply block text-sm font-medium text-secondary-200 mb-1;
}

.form-input {
  @apply w-full px-4 py-2 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50;
  @apply placeholder-secondary-400 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent;
}

.form-input--error {
  @apply border-red-500 focus:ring-red-500;
}

.form-error {
  @apply mt-1 text-sm text-red-400;
}
</style>
