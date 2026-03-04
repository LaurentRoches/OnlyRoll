<template>
  <div class="text-center space-y-6">
    <div v-if="status === 'loading'" class="space-y-4">
      <div class="flex justify-center">
        <div
          class="w-16 h-16 border-4 border-primary-500 border-t-transparent rounded-full animate-spin"
        ></div>
      </div>
      <p class="text-secondary-300">Vérification de votre email...</p>
    </div>

    <div v-else-if="status === 'success'" class="space-y-6">
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
        <h2 class="text-xl font-semibold text-secondary-50 mb-2">Email vérifié !</h2>
        <p class="text-secondary-300">
          Votre adresse email a été confirmée. Vous pouvez maintenant vous connecter.
        </p>
      </div>
      <RouterLink
        to="/auth/login"
        class="block w-full px-4 py-3 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-lg text-center transition-colors duration-200"
      >
        Se connecter
      </RouterLink>
    </div>

    <div v-else class="space-y-6">
      <div class="flex justify-center">
        <div class="w-20 h-20 bg-error/10 rounded-full flex items-center justify-center">
          <svg class="w-10 h-10 text-error" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
              clip-rule="evenodd"
            />
          </svg>
        </div>
      </div>
      <div>
        <h2 class="text-xl font-semibold text-secondary-50 mb-2">Lien invalide</h2>
        <p class="text-secondary-300">{{ errorMessage }}</p>
      </div>
      <RouterLink
        to="/auth/login"
        class="block w-full px-4 py-3 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-lg text-center transition-colors duration-200"
      >
        Retour à la connexion
      </RouterLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { authApi } from '@/services/api/authApi'

const route = useRoute()
const status = ref<'loading' | 'success' | 'error'>('loading')
const errorMessage = ref('Ce lien de vérification est invalide ou a expiré.')

onMounted(async () => {
  const token = route.query.token as string

  if (!token) {
    status.value = 'error'
    return
  }

  try {
    await authApi.verifyEmail(token)
    status.value = 'success'
  } catch (error: unknown) {
    const err = error as { response?: { data?: { error?: string } } }
    errorMessage.value =
      err.response?.data?.error ?? 'Ce lien de vérification est invalide ou a expiré.'
    status.value = 'error'
  }
})
</script>
