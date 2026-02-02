<template>
  <form @submit.prevent="handleSubmit" novalidate class="password-change-form space-y-6">
    <h3 id="password-form-heading" class="text-lg font-semibold text-secondary-50">
      Changer le mot de passe
    </h3>

    <!-- Mot de passe actuel -->
    <div class="form-group">
      <label :for="currentPasswordId" class="form-label">
        Mot de passe actuel
        <span class="text-red-500" aria-hidden="true">*</span>
        <span class="sr-only">(obligatoire)</span>
      </label>
      <div class="relative">
        <input
          :id="currentPasswordId"
          v-model="form.currentPassword"
          :type="showCurrentPassword ? 'text' : 'password'"
          autocomplete="current-password"
          :aria-describedby="errors.currentPassword ? currentPasswordErrorId : undefined"
          :aria-invalid="errors.currentPassword ? 'true' : 'false'"
          :aria-required="true"
          class="form-input pr-10"
          :class="{ 'form-input--error': errors.currentPassword }"
          @blur="validateField('currentPassword')"
        />
        <button
          type="button"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-200 focus:outline-none focus:text-secondary-200"
          :aria-label="
            showCurrentPassword
              ? 'Masquer le mot de passe actuel'
              : 'Afficher le mot de passe actuel'
          "
          @click="showCurrentPassword = !showCurrentPassword"
        >
          <svg
            v-if="showCurrentPassword"
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
              d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
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
      <p
        v-if="errors.currentPassword"
        :id="currentPasswordErrorId"
        class="form-error"
        role="alert"
        aria-live="assertive"
      >
        {{ errors.currentPassword }}
      </p>
    </div>

    <!-- Nouveau mot de passe -->
    <div class="form-group">
      <div class="flex items-center justify-between mb-1">
        <label :for="newPasswordId" class="form-label !mb-0">
          Nouveau mot de passe
          <span class="text-red-500" aria-hidden="true">*</span>
          <span class="sr-only">(obligatoire)</span>
        </label>
        <button
          type="button"
          @click="handleGeneratePassword"
          :disabled="isSubmitting || isGenerating"
          class="inline-flex items-center px-2 py-1 text-xs font-medium text-primary-400 hover:text-primary-300 disabled:text-secondary-500 disabled:cursor-not-allowed transition-colors"
        >
          <svg
            v-if="isGenerating"
            class="animate-spin -ml-0.5 mr-1.5 h-3 w-3"
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
          <svg
            v-else
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
              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"
            />
          </svg>
          {{ isGenerating ? 'Génération...' : 'Générer' }}
        </button>
      </div>
      <div class="relative">
        <input
          :id="newPasswordId"
          v-model="form.newPassword"
          :type="showNewPassword ? 'text' : 'password'"
          autocomplete="new-password"
          :aria-describedby="
            [newPasswordHelpId, errors.newPassword ? newPasswordErrorId : '']
              .filter(Boolean)
              .join(' ')
          "
          :aria-invalid="errors.newPassword ? 'true' : 'false'"
          :aria-required="true"
          class="form-input pr-10"
          :class="{ 'form-input--error': errors.newPassword }"
          @input="updatePasswordStrength"
          @blur="validateField('newPassword')"
        />
        <button
          type="button"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-200 focus:outline-none focus:text-secondary-200"
          :aria-label="
            showNewPassword ? 'Masquer le nouveau mot de passe' : 'Afficher le nouveau mot de passe'
          "
          @click="showNewPassword = !showNewPassword"
        >
          <svg
            v-if="showNewPassword"
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
              d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
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

      <!-- Indicateur de force du mot de passe -->
      <div class="mt-2 space-y-2">
        <div class="flex items-center space-x-2">
          <div class="flex-1 h-2 bg-secondary-700 rounded-full overflow-hidden">
            <div
              class="h-full transition-all duration-300"
              :class="strengthBarClass"
              :style="{ width: `${(passwordStrength.score / 4) * 100}%` }"
              role="progressbar"
              :aria-valuenow="passwordStrength.score"
              aria-valuemin="0"
              aria-valuemax="4"
              :aria-label="`Force du mot de passe: ${strengthLabel}`"
            />
          </div>
          <span class="text-xs" :class="strengthTextClass">{{ strengthLabel }}</span>
        </div>

        <!-- Liste des exigences -->
        <ul :id="newPasswordHelpId" class="text-xs text-secondary-400 space-y-1" role="list">
          <li
            v-for="(feedback, index) in passwordStrength.feedback"
            :key="index"
            class="flex items-center"
          >
            <svg
              class="w-4 h-4 mr-1 text-red-400"
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
            {{ feedback }}
          </li>
          <li v-if="passwordStrength.isValid" class="flex items-center text-green-400">
            <svg
              class="w-4 h-4 mr-1"
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
            Mot de passe conforme aux exigences
          </li>
        </ul>
      </div>

      <p
        v-if="errors.newPassword"
        :id="newPasswordErrorId"
        class="form-error"
        role="alert"
        aria-live="assertive"
      >
        {{ errors.newPassword }}
      </p>
    </div>

    <!-- Confirmation du mot de passe -->
    <div class="form-group">
      <label :for="confirmPasswordId" class="form-label">
        Confirmer le nouveau mot de passe
        <span class="text-red-500" aria-hidden="true">*</span>
        <span class="sr-only">(obligatoire)</span>
      </label>
      <div class="relative">
        <input
          :id="confirmPasswordId"
          v-model="form.confirmPassword"
          :type="showConfirmPassword ? 'text' : 'password'"
          autocomplete="new-password"
          :aria-describedby="errors.confirmPassword ? confirmPasswordErrorId : undefined"
          :aria-invalid="errors.confirmPassword ? 'true' : 'false'"
          :aria-required="true"
          class="form-input pr-10"
          :class="{ 'form-input--error': errors.confirmPassword }"
          @blur="validateField('confirmPassword')"
        />
        <button
          type="button"
          class="absolute right-3 top-1/2 -translate-y-1/2 text-secondary-400 hover:text-secondary-200 focus:outline-none focus:text-secondary-200"
          :aria-label="showConfirmPassword ? 'Masquer la confirmation' : 'Afficher la confirmation'"
          @click="showConfirmPassword = !showConfirmPassword"
        >
          <svg
            v-if="showConfirmPassword"
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
              d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
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
      <p
        v-if="errors.confirmPassword"
        :id="confirmPasswordErrorId"
        class="form-error"
        role="alert"
        aria-live="assertive"
      >
        {{ errors.confirmPassword }}
      </p>
    </div>

    <!-- Message d'erreur global -->
    <div
      v-if="globalError"
      role="alert"
      aria-live="assertive"
      class="p-4 bg-red-500/10 border border-red-500/50 rounded-lg text-red-400"
    >
      {{ globalError }}
    </div>

    <!-- Message de succès -->
    <div
      v-if="successMessage"
      role="status"
      aria-live="polite"
      class="p-4 bg-green-500/10 border border-green-500/50 rounded-lg text-green-400"
    >
      {{ successMessage }}
    </div>

    <!-- Bouton submit -->
    <div class="flex justify-end">
      <button
        type="submit"
        :disabled="isSubmitting || !isFormValid"
        class="px-6 py-2 bg-primary-600 hover:bg-primary-500 text-white font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-secondary-900 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        <span v-if="isSubmitting" class="flex items-center">
          <span
            class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2"
            aria-hidden="true"
          ></span>
          <span>Modification en cours...</span>
        </span>
        <span v-else>Changer le mot de passe</span>
      </button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, reactive, computed, useId } from 'vue'
import { profileApi } from '@/services/api/profileApi'
import { usePasswordGenerator } from '@/composables/usePasswordGenerator'

const emit = defineEmits<{
  success: []
}>()

const { generate: generatePassword, isGenerating } = usePasswordGenerator()

const currentPasswordId = useId()
const currentPasswordErrorId = useId()
const newPasswordId = useId()
const newPasswordErrorId = useId()
const newPasswordHelpId = useId()
const confirmPasswordId = useId()
const confirmPasswordErrorId = useId()

const form = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
})

const errors = reactive<Record<string, string>>({})
const globalError = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const isSubmitting = ref(false)

const showCurrentPassword = ref(false)
const showNewPassword = ref(false)
const showConfirmPassword = ref(false)

interface PasswordStrength {
  score: number
  feedback: string[]
  isValid: boolean
}

const passwordStrength = reactive<PasswordStrength>({
  score: 0,
  feedback: [],
  isValid: false,
})

/**
 * Valide la force du mot de passe selon les critères NIST/OWASP
 */
const validatePassword = (password: string): PasswordStrength => {
  const feedback: string[] = []
  let score = 0

  if (password.length >= 12) {
    score++
  } else {
    feedback.push('Au moins 12 caractères requis')
  }

  if (/[a-z]/.test(password)) {
    score++
  } else {
    feedback.push('Au moins une minuscule requise')
  }

  if (/[A-Z]/.test(password)) {
    score++
  } else {
    feedback.push('Au moins une majuscule requise')
  }

  if (/\d/.test(password)) {
    score++
  } else {
    feedback.push('Au moins un chiffre requis')
  }

  if (/[!@#$%&*()_+\-=\[\]{}|;:,.<>?]/.test(password)) {
    score++
  } else {
    feedback.push('Au moins un caractère spécial requis (!@#$%&*()_+-=[]{}|;:,.<>?)')
  }

  return {
    score: Math.min(score, 4),
    feedback,
    isValid: score >= 5,
  }
}

const updatePasswordStrength = () => {
  const result = validatePassword(form.newPassword)
  passwordStrength.score = result.score
  passwordStrength.feedback = result.feedback
  passwordStrength.isValid = result.isValid
}

const strengthBarClass = computed(() => {
  if (passwordStrength.score <= 1) return 'bg-red-500'
  if (passwordStrength.score <= 2) return 'bg-yellow-500'
  if (passwordStrength.score <= 3) return 'bg-blue-500'
  return 'bg-green-500'
})

const strengthTextClass = computed(() => {
  if (passwordStrength.score <= 1) return 'text-red-400'
  if (passwordStrength.score <= 2) return 'text-yellow-400'
  if (passwordStrength.score <= 3) return 'text-blue-400'
  return 'text-green-400'
})

const strengthLabel = computed(() => {
  if (form.newPassword.length === 0) return ''
  if (passwordStrength.score <= 1) return 'Faible'
  if (passwordStrength.score <= 2) return 'Moyen'
  if (passwordStrength.score <= 3) return 'Bon'
  return 'Fort'
})

const isFormValid = computed(() => {
  return (
    form.currentPassword.length > 0 &&
    passwordStrength.isValid &&
    form.newPassword === form.confirmPassword
  )
})

const validateField = (field: string) => {
  delete errors[field]

  switch (field) {
    case 'currentPassword':
      if (!form.currentPassword) {
        errors.currentPassword = 'Le mot de passe actuel est requis'
      }
      break

    case 'newPassword':
      if (!form.newPassword) {
        errors.newPassword = 'Le nouveau mot de passe est requis'
      } else if (!passwordStrength.isValid) {
        errors.newPassword = 'Le mot de passe ne respecte pas les exigences de sécurité'
      }
      break

    case 'confirmPassword':
      if (!form.confirmPassword) {
        errors.confirmPassword = 'La confirmation du mot de passe est requise'
      } else if (form.confirmPassword !== form.newPassword) {
        errors.confirmPassword = 'Les mots de passe ne correspondent pas'
      }
      break
  }
}

const validateAllFields = (): boolean => {
  validateField('currentPassword')
  validateField('newPassword')
  validateField('confirmPassword')
  return Object.keys(errors).length === 0
}

/**
 * Génère un mot de passe sécurisé et le remplit dans les champs
 */
const handleGeneratePassword = async () => {
  const password = await generatePassword({
    length: 16,
    includeLowercase: true,
    includeUppercase: true,
    includeDigits: true,
    includeSpecial: true,
    excludeAmbiguous: false,
  })

  if (password) {
    form.newPassword = password
    form.confirmPassword = password
    showNewPassword.value = true
    showConfirmPassword.value = true
    updatePasswordStrength()
  }
}

const handleSubmit = async () => {
  globalError.value = null
  successMessage.value = null

  if (!validateAllFields()) {
    return
  }

  isSubmitting.value = true

  try {
    await profileApi.changePassword({
      currentPassword: form.currentPassword,
      newPassword: form.newPassword,
      confirmPassword: form.confirmPassword,
    })

    successMessage.value = 'Votre mot de passe a été modifié avec succès.'

    form.currentPassword = ''
    form.newPassword = ''
    form.confirmPassword = ''
    passwordStrength.score = 0
    passwordStrength.feedback = []
    passwordStrength.isValid = false

    emit('success')

    setTimeout(() => {
      successMessage.value = null
    }, 5000)
  } catch (err: unknown) {
    if (err && typeof err === 'object' && 'response' in err) {
      const response = (
        err as { response?: { data?: { error?: string; violations?: Record<string, string> } } }
      ).response
      if (response?.data?.violations) {
        Object.assign(errors, response.data.violations)
      } else if (response?.data?.error) {
        globalError.value = response.data.error
      } else {
        globalError.value = 'Une erreur est survenue lors de la modification du mot de passe.'
      }
    } else {
      globalError.value = err instanceof Error ? err.message : 'Une erreur est survenue.'
    }
  } finally {
    isSubmitting.value = false
  }
}
</script>

<style scoped lang="postcss">
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
