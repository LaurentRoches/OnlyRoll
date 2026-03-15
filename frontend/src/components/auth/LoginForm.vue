<template>
  <form @submit.prevent="handleSubmit" class="space-y-6">
    <div class="text-center mb-6">
      <h2 class="text-xl font-semibold text-secondary-50 mb-1">{{ t('auth.login.title') }}</h2>
      <p class="text-sm text-secondary-400">{{ t('auth.login.subtitle') }}</p>
    </div>

    <div
      v-if="parsedError?.code === 'account_deleted'"
      class="bg-warning/10 border border-warning/20 rounded-lg p-4"
    >
      <div class="flex items-start space-x-3">
        <svg
          class="w-5 h-5 text-warning flex-shrink-0 mt-0.5"
          fill="currentColor"
          viewBox="0 0 20 20"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
            clip-rule="evenodd"
          />
        </svg>
        <div class="text-sm space-y-1">
          <p class="text-warning font-medium">{{ t('auth.login.accountDeleted.title') }}</p>
          <p class="text-secondary-300">{{ t('auth.login.accountDeleted.message') }}</p>
          <p class="text-secondary-400">
            {{ t('auth.login.accountDeleted.contactAdmin') }}
            <a
              :href="`mailto:${parsedError.adminEmail}`"
              class="text-primary-400 hover:text-primary-300 transition-colors underline"
            >
              {{ parsedError.adminEmail }}
            </a>
          </p>
        </div>
      </div>
    </div>

    <div
      v-else-if="parsedError?.code === 'account_not_verified'"
      class="bg-info/10 border border-info/20 rounded-lg p-4"
    >
      <div class="flex items-start space-x-3">
        <svg
          class="w-5 h-5 text-info flex-shrink-0 mt-0.5"
          fill="currentColor"
          viewBox="0 0 20 20"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
            clip-rule="evenodd"
          />
        </svg>
        <div class="text-sm space-y-1">
          <p class="text-info font-medium">{{ t('auth.login.accountNotVerified.title') }}</p>
          <p class="text-secondary-300">
            {{ t('auth.login.accountNotVerified.message') }}
          </p>
          <RouterLink
            :to="{ name: 'register-success', query: { email: parsedError.email } }"
            class="text-primary-400 hover:text-primary-300 transition-colors underline"
          >
            {{ t('auth.login.accountNotVerified.resendLink') }}
          </RouterLink>
        </div>
      </div>
    </div>

    <div
      v-else-if="error || validationErrors.length > 0"
      class="bg-error/10 border border-error/20 rounded-lg p-4"
    >
      <div class="flex items-start space-x-3">
        <svg
          class="w-5 h-5 text-error flex-shrink-0 mt-0.5"
          fill="currentColor"
          viewBox="0 0 20 20"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
            clip-rule="evenodd"
          />
        </svg>
        <div class="text-sm">
          <p v-if="error" class="text-error font-medium">{{ error }}</p>
          <ul v-if="validationErrors.length > 0" class="text-error space-y-1">
            <li v-for="err in validationErrors" :key="err.field">
              {{ err.message }}
            </li>
          </ul>
        </div>
      </div>
    </div>

    <div class="space-y-1">
      <label for="email" class="block text-sm font-medium text-secondary-200"> {{ t('auth.login.emailLabel') }} </label>
      <input
        id="email"
        name="email"
        v-model="form.email"
        type="email"
        autocomplete="email"
        required
        :disabled="isLoading"
        class="block w-full px-4 py-3 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 placeholder-secondary-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        :placeholder="t('auth.login.emailPlaceholder')"
      />
    </div>

    <div class="space-y-1">
      <label for="password" class="block text-sm font-medium text-secondary-200">
        {{ t('auth.login.passwordLabel') }}
      </label>
      <div class="relative">
        <input
          id="password"
          name="password"
          v-model="form.password"
          :type="showPassword ? 'text' : 'password'"
          autocomplete="current-password"
          required
          :disabled="isLoading"
          class="block w-full px-4 py-3 pr-12 bg-secondary-700 border border-secondary-600 rounded-lg text-secondary-50 placeholder-secondary-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          :placeholder="t('auth.login.passwordPlaceholder')"
        />
        <button
          type="button"
          @click="togglePasswordVisibility"
          :disabled="isLoading"
          :aria-label="showPassword ? t('auth.login.hidePassword') : t('auth.login.showPassword')"
          class="absolute inset-y-0 right-0 pr-3 flex items-center text-secondary-400 hover:text-secondary-300 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
        >
          <svg
            v-if="showPassword"
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"
            />
          </svg>
          <svg
            v-else
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
            />
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
            />
          </svg>
        </button>
      </div>
    </div>

    <div class="flex items-center justify-between">
      <div class="flex items-center">
        <input
          id="remember-me"
          v-model="form.rememberMe"
          type="checkbox"
          :disabled="isLoading"
          class="h-4 w-4 bg-secondary-700 border-secondary-600 rounded text-primary-500 focus:ring-primary-500 focus:ring-offset-0 disabled:opacity-50 disabled:cursor-not-allowed"
        />
        <label for="remember-me" class="ml-2 text-sm text-secondary-300">
          {{ t('auth.login.rememberMe') }}
        </label>
      </div>

      <RouterLink
        to="/auth/forgot-password"
        class="text-sm text-primary-400 hover:text-primary-300 transition-colors"
      >
        {{ t('auth.login.forgotPassword') }}
      </RouterLink>
    </div>

    <button
      type="submit"
      :disabled="isLoading || !isFormValid"
      class="w-full flex justify-center items-center px-4 py-3 bg-primary-500 hover:bg-primary-600 disabled:bg-secondary-600 disabled:cursor-not-allowed text-white font-medium rounded-lg focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-secondary-800 transition-colors duration-200"
    >
      <svg
        v-if="isLoading"
        class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
        fill="none"
        viewBox="0 0 24 24"
        aria-hidden="true"
      >
        <circle
          class="opacity-25"
          cx="12"
          cy="12"
          r="10"
          stroke="currentColor"
          stroke-width="4"
        ></circle>
        <path
          class="opacity-75"
          fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
        ></path>
      </svg>
      {{ isLoading ? t('auth.login.submitLoading') : t('auth.login.submit') }}
    </button>
  </form>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuth } from '@/composables/useAuth'
import {
  useFormValidation,
  usePasswordVisibility,
  validators,
} from '@/composables/useFormValidation'
import type { LoginCredentials } from '@/types/auth'
import { logger } from '@/utils/logger'

const { t } = useI18n()
const { login, isLoading, error, clearError } = useAuth()
const { validationErrors, validateFields, clearErrors } = useFormValidation()
const { showPassword, togglePasswordVisibility } = usePasswordVisibility()

const form = ref<LoginCredentials & { rememberMe: boolean }>({
  email: '',
  password: '',
  rememberMe: false,
})

const isFormValid = computed(() => {
  return (
    form.value.email.length > 0 &&
    form.value.password.length > 0 &&
    validators.isEmail(form.value.email)
  )
})

const parsedError = computed<{
  code: string
  message: string
  adminEmail: string
  email: string
} | null>(() => {
  if (!error.value) return null
  try {
    return JSON.parse(error.value)
  } catch {
    return null
  }
})

const validateForm = (): boolean => {
  return validateFields([
    {
      field: 'email',
      value: form.value.email,
      rules: [
        { validator: validators.required, message: t('auth.login.validation.emailRequired') },
        { validator: validators.isEmail, message: t('auth.login.validation.emailInvalid') },
      ],
    },
    {
      field: 'password',
      value: form.value.password,
      rules: [
        { validator: validators.required, message: t('auth.login.validation.passwordRequired') },
        {
          validator: validators.minLength(3),
          message: t('auth.login.validation.passwordMinLength'),
        },
      ],
    },
  ])
}

const handleSubmit = async () => {
  clearError()
  clearErrors()

  if (!validateForm()) {
    return
  }

  try {
    await login({
      email: form.value.email,
      password: form.value.password,
      rememberMe: form.value.rememberMe,
    })
  } catch (err) {
    logger.error('Erreur de connexion:', err)
  }
}
</script>
