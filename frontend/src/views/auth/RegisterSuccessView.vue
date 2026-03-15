<template>
  <div class="text-center space-y-6">
    <div class="flex justify-center">
      <div class="w-20 h-20 bg-success/10 rounded-full flex items-center justify-center">
        <svg class="w-10 h-10 text-success" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
    </div>

    <div>
      <h2 class="text-xl font-semibold text-secondary-50 mb-2">{{ t('auth.registerSuccess.title') }}</h2>
      <p class="text-secondary-300 mb-4">{{ t('auth.registerSuccess.subtitle') }}</p>
    </div>

    <div class="bg-info/10 border border-info/20 rounded-lg p-4">
      <div class="flex items-start space-x-3">
        <svg class="w-5 h-5 text-info flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
            clip-rule="evenodd"
          />
        </svg>
        <div class="text-sm">
          <p class="text-info font-medium mb-1">{{ t('auth.registerSuccess.verifyEmail.title') }}</p>
          <p class="text-secondary-300">{{ t('auth.registerSuccess.verifyEmail.message') }}</p>
          <p class="font-medium text-secondary-200 mt-1">
            {{ email }}
          </p>
          <p class="text-secondary-400 mt-2 text-xs">
            {{ t('auth.registerSuccess.verifyEmail.spamNotice') }}
          </p>
        </div>
      </div>
    </div>

    <div class="space-y-3">
      <RouterLink
        to="/auth/login"
        class="block w-full px-4 py-3 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-lg text-center transition-colors duration-200"
      >
        {{ t('auth.registerSuccess.login') }}
      </RouterLink>

      <button
        type="button"
        @click="resendEmail"
        :disabled="isResending || cooldown > 0"
        class="block w-full px-4 py-3 bg-secondary-700 hover:bg-secondary-600 disabled:bg-secondary-600 disabled:cursor-not-allowed text-secondary-200 font-medium rounded-lg transition-colors duration-200"
      >
        <span v-if="isResending">{{ t('auth.registerSuccess.resendLoading') }}</span>
        <span v-else-if="cooldown > 0">{{ t('auth.registerSuccess.resendCooldown', { cooldown }) }}</span>
        <span v-else>{{ t('auth.registerSuccess.resend') }}</span>
      </button>

      <p v-if="resendError" class="text-sm text-error text-center">{{ resendError }}</p>
    </div>

    <div class="text-center pt-4 border-t border-secondary-700">
      <p class="text-xs text-secondary-500">
        {{ t('auth.registerSuccess.supportText') }}
        <a
          href="mailto:support@onlyroll.com"
          class="text-primary-400 hover:text-primary-300 transition-colors"
        >
          {{ t('auth.registerSuccess.contactSupport') }}
        </a>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { authApi } from '@/services/api/authApi'

const { t } = useI18n()
const route = useRoute()

const email = ref((route.query.email as string) || '')

const isResending = ref(false)
const cooldown = ref(0)
const resendError = ref('')
let cooldownTimer: ReturnType<typeof setInterval> | null = null

const resendEmail = async () => {
  if (isResending.value || cooldown.value > 0) return

  isResending.value = true
  resendError.value = ''

  try {
    await authApi.resendVerification(email.value || undefined)
    startCooldown()
  } catch (error: unknown) {
    const err = error as { response?: { data?: { error?: string }; status?: number } }
    if (err.response?.status === 429) {
      resendError.value = t('auth.registerSuccess.rateLimitError')
    } else {
      resendError.value = err.response?.data?.error ?? t('auth.registerSuccess.resendError')
    }
  } finally {
    isResending.value = false
  }
}

const startCooldown = () => {
  cooldown.value = 120

  cooldownTimer = setInterval(() => {
    cooldown.value--
    if (cooldown.value <= 0 && cooldownTimer) {
      clearInterval(cooldownTimer)
      cooldownTimer = null
    }
  }, 1000)
}

onUnmounted(() => {
  if (cooldownTimer) {
    clearInterval(cooldownTimer)
  }
})
</script>
