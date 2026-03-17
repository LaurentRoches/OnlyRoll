<template>
  <div class="space-y-6">
    <div class="text-center">
      <h2 class="text-xl font-semibold text-secondary-50 mb-2">
        {{ t('auth.forgotPassword.title') }}
      </h2>
      <p class="text-secondary-400 text-sm">
        {{ t('auth.forgotPassword.subtitle') }}
      </p>
    </div>

    <div
      v-if="submitted"
      class="bg-success/10 border border-success/20 rounded-lg p-4 text-center space-y-3"
    >
      <svg class="w-8 h-8 text-success mx-auto" fill="currentColor" viewBox="0 0 20 20">
        <path
          fill-rule="evenodd"
          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
          clip-rule="evenodd"
        />
      </svg>
      <p class="text-success font-medium text-sm">{{ t('auth.forgotPassword.success.title') }}</p>
      <p class="text-secondary-300 text-sm">
        {{ t('auth.forgotPassword.success.message') }}
      </p>
      <RouterLink
        to="/auth/login"
        class="inline-block mt-2 text-sm text-primary-400 hover:text-primary-300 transition-colors"
      >
        {{ t('auth.forgotPassword.backToLogin') }}
      </RouterLink>
    </div>

    <form v-else @submit.prevent="submit" class="space-y-4">
      <div>
        <label for="email" class="block text-sm font-medium text-secondary-300 mb-1">{{
          t('auth.forgotPassword.emailLabel')
        }}</label>
        <input
          id="email"
          v-model="email"
          type="email"
          autocomplete="email"
          required
          :placeholder="t('auth.forgotPassword.emailPlaceholder')"
          class="w-full px-4 py-3 bg-secondary-800 border border-secondary-600 rounded-lg text-secondary-100 placeholder-secondary-500 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors"
        />
      </div>

      <p v-if="error" class="text-sm text-error">{{ error }}</p>

      <button
        type="submit"
        :disabled="isLoading"
        class="w-full px-4 py-3 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-800 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors duration-200"
      >
        <span v-if="isLoading">{{ t('auth.forgotPassword.submitLoading') }}</span>
        <span v-else>{{ t('auth.forgotPassword.submit') }}</span>
      </button>

      <div class="text-center">
        <RouterLink
          to="/auth/login"
          class="text-sm text-secondary-400 hover:text-secondary-300 transition-colors"
        >
          {{ t('auth.forgotPassword.backToLogin') }}
        </RouterLink>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { authApi } from '@/services/api/authApi'

const { t } = useI18n()
const email = ref('')
const isLoading = ref(false)
const submitted = ref(false)
const error = ref('')

const submit = async () => {
  if (!email.value || isLoading.value) return

  isLoading.value = true
  error.value = ''

  try {
    await authApi.forgotPassword(email.value)
    submitted.value = true
  } catch {
    error.value = t('auth.forgotPassword.error')
  } finally {
    isLoading.value = false
  }
}
</script>
