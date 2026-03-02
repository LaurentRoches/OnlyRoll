<template>
  <div class="space-y-6">
    <!-- Token invalide (détecté au montage) -->
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
      <p class="text-secondary-300">Lien de réinitialisation invalide.</p>
      <RouterLink
        to="/auth/forgot-password"
        class="inline-block text-sm text-primary-400 hover:text-primary-300 transition-colors"
      >
        Faire une nouvelle demande
      </RouterLink>
    </div>

    <!-- Succès -->
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
      <h2 class="text-xl font-semibold text-secondary-50">Mot de passe réinitialisé !</h2>
      <p class="text-secondary-300">
        Vous pouvez maintenant vous connecter avec votre nouveau mot de passe.
      </p>
      <RouterLink
        to="/auth/login"
        class="block w-full px-4 py-3 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-lg text-center transition-colors duration-200"
      >
        Se connecter
      </RouterLink>
    </div>

    <!-- Formulaire -->
    <form v-else @submit.prevent="submit" class="space-y-5">
      <div class="text-center">
        <h2 class="text-xl font-semibold text-secondary-50 mb-1">Nouveau mot de passe</h2>
        <p class="text-secondary-400 text-sm">
          Choisissez un mot de passe d'au moins 8 caractères.
        </p>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-secondary-300 mb-1"
          >Nouveau mot de passe</label
        >
        <input
          id="password"
          v-model="password"
          type="password"
          autocomplete="new-password"
          required
          minlength="8"
          placeholder="••••••••"
          class="w-full px-4 py-3 bg-secondary-800 border border-secondary-600 rounded-lg text-secondary-100 placeholder-secondary-500 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors"
        />
      </div>

      <div>
        <label for="confirm" class="block text-sm font-medium text-secondary-300 mb-1"
          >Confirmer le mot de passe</label
        >
        <input
          id="confirm"
          v-model="confirm"
          type="password"
          autocomplete="new-password"
          required
          placeholder="••••••••"
          class="w-full px-4 py-3 bg-secondary-800 border border-secondary-600 rounded-lg text-secondary-100 placeholder-secondary-500 focus:outline-none focus:border-primary-500 focus:ring-1 focus:ring-primary-500 transition-colors"
          :class="{ 'border-error': confirm && password !== confirm }"
        />
        <p v-if="confirm && password !== confirm" class="text-xs text-error mt-1">
          Les mots de passe ne correspondent pas.
        </p>
      </div>

      <p v-if="error" class="text-sm text-error">{{ error }}</p>

      <button
        type="submit"
        :disabled="isLoading || (!!confirm && password !== confirm)"
        class="w-full px-4 py-3 bg-primary-500 hover:bg-primary-600 disabled:bg-primary-800 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors duration-200"
      >
        <span v-if="isLoading">Réinitialisation...</span>
        <span v-else>Réinitialiser le mot de passe</span>
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { authApi } from '@/services/api/authApi'

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
    error.value = e.response?.data?.error ?? 'Une erreur est survenue. Veuillez réessayer.'
  } finally {
    isLoading.value = false
  }
}
</script>
