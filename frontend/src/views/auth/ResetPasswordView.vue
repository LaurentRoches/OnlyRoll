<template>
  <div class="space-y-6">
    <div v-if="!token" class="text-center space-y-4">
      <div class="w-16 h-16 bg-error/10 rounded-full flex items-center justify-center mx-auto">
        <svg class="w-8 h-8 text-error" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
      <p class="text-secondary-300">{{ t('auth.resetPassword.invalidLink') }}</p>
      <RouterLink
        to="/auth/forgot-password"
        class="inline-block text-sm text-primary-400 hover:text-primary-300 transition-colors"
      >
        {{ t('auth.resetPassword.newRequest') }}
      </RouterLink>
    </div>

    <div v-else-if="success" class="text-center space-y-4">
      <div class="w-20 h-20 bg-success/10 rounded-full flex items-center justify-center mx-auto">
        <svg class="w-10 h-10 text-success" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
            clip-rule="evenodd"
          />
        </svg>
      </div>
      <h2 class="text-xl font-semibold text-secondary-50">
        {{ t('auth.resetPassword.success.title') }}
      </h2>
      <p class="text-secondary-300">
        {{ t('auth.resetPassword.success.message') }}
      </p>
      <RouterLink
        to="/auth/login"
        class="block w-full px-4 py-3 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-lg text-center transition-colors duration-200"
      >
        {{ t('auth.resetPassword.success.login') }}
      </RouterLink>
    </div>

    <form v-else @submit.prevent="submit" class="space-y-5">
      <div class="text-center">
        <h2 class="text-xl font-semibold text-secondary-50 mb-1">
          {{ t('auth.resetPassword.title') }}
        </h2>
        <p class="text-secondary-400 text-sm">
          {{ t('auth.resetPassword.subtitle') }}
        </p>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-secondary-300 mb-1">{{
          t('auth.resetPassword.passwordLabel')
        }}</label>
        <input
          id="password"
          v-model="password"
          type="password"
          autocomplete="new-password"
          required
          minlength="8"
          :placeholder="t('auth.resetPassword.passwordPlaceholder')"
          class="w-full px-4 py-3 bg-secondary-800 border border-secondary-600 rounded-lg text-secondary-100 placeholder-secondary-500 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors"
        />
      </div>

      <div>
        <label for="confirm" class="block text-sm font-medium text-secondary-300 mb-1">{{
          t('auth.resetPassword.confirmLabel')
        }}</label>
        <input
          id="confirm"
          v-model="confirm"
          type="password"
          autocomplete="new-password"
          required
          :placeholder="t('auth.resetPassword.confirmPlaceholder')"
          class="w-full px-4 py-3 bg-secondary-800 border border-secondary-600 rounded-lg text-secondary-100 placeholder-secondary-500 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors"
          :class="{ 'border-error': confirm && password !== confirm }"
        />
        <p v-if="confirm && password !== confirm" class="text-xs text-error mt-1">
          {{ t('auth.resetPassword.passwordsMismatch') }}
        </p>
      </div>

      <p v-if="error" class="text-sm text-error">{{ error }}</p>

      <button
        type="submit"
        :disabled="isLoading || (!!confirm && password !== confirm)"
        class="w-full px-4 py-3 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-800 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors duration-200"
      >
        <span v-if="isLoading">{{ t('auth.resetPassword.submitLoading') }}</span>
        <span v-else>{{ t('auth.resetPassword.submit') }}</span>
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute } from 'vue-router'
import { authApi } from '@/services/api/authApi'

const { t } = useI18n()
const route = useRoute()
const token = ref('')
const password = ref('')
const confirm = ref('')
const isLoading = ref(false)
const success = ref(false)
const error = ref('')

onMounted(() => {
  token.value = (route.query.token as string) ?? ''
})

const submit = async () => {
  if (!password.value || password.value !== confirm.value || isLoading.value) return

  isLoading.value = true
  error.value = ''

  try {
    await authApi.resetPassword(token.value, password.value)
    success.value = true
  } catch (err: unknown) {
    const e = err as { response?: { data?: { error?: string } } }
    error.value = e.response?.data?.error ?? t('auth.resetPassword.error')
  } finally {
    isLoading.value = false
  }
}
</script>
