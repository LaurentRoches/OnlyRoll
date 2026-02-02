<template>
  <div class="avatar-uploader">
    <!-- Avatar actuel -->
    <div class="flex items-center space-x-4">
      <div class="relative">
        <!-- Image avatar ou placeholder -->
        <div
          class="w-24 h-24 rounded-full overflow-hidden bg-secondary-700 flex items-center justify-center"
          role="img"
          :aria-label="avatar ? `Avatar de ${pseudo}` : 'Aucun avatar défini'"
        >
          <img
            v-if="avatar"
            :src="avatarUrl"
            :alt="`Avatar de ${pseudo}`"
            class="w-full h-full object-cover"
          />
          <svg
            v-else
            class="w-12 h-12 text-secondary-500"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
            />
          </svg>
        </div>

        <!-- Badge de chargement -->
        <div
          v-if="isUploading"
          class="absolute inset-0 bg-black/50 rounded-full flex items-center justify-center"
          role="status"
          aria-label="Upload en cours"
        >
          <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex flex-col space-y-2">
        <!-- Input file caché -->
        <input
          ref="fileInputRef"
          :id="inputId"
          type="file"
          accept="image/jpeg,image/png,image/gif,image/webp"
          class="sr-only"
          :aria-describedby="helpTextId"
          @change="handleFileSelect"
        />

        <!-- Bouton upload -->
        <button
          type="button"
          class="px-4 py-2 bg-primary-600 hover:bg-primary-500 text-white rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-secondary-900"
          :disabled="isUploading"
          @click="triggerFileInput"
        >
          <span v-if="avatar">Changer l'avatar</span>
          <span v-else>Ajouter un avatar</span>
        </button>

        <!-- Bouton supprimer -->
        <button
          v-if="avatar"
          type="button"
          class="px-4 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 focus:ring-offset-secondary-900"
          :disabled="isUploading"
          @click="handleDelete"
        >
          Supprimer l'avatar
        </button>
      </div>
    </div>

    <!-- Aide et contraintes -->
    <p :id="helpTextId" class="mt-3 text-sm text-secondary-400">
      Formats acceptés : JPEG, PNG, GIF, WebP. Taille maximale : 2 Mo.
    </p>

    <!-- Message d'erreur - RGAA 11.10, 11.11 -->
    <div
      v-if="error"
      role="alert"
      aria-live="assertive"
      class="mt-2 p-3 bg-red-500/10 border border-red-500/50 rounded-lg text-red-400 text-sm"
    >
      {{ error }}
    </div>

    <!-- Message de succès -->
    <div
      v-if="successMessage"
      role="status"
      aria-live="polite"
      class="mt-2 p-3 bg-green-500/10 border border-green-500/50 rounded-lg text-green-400 text-sm"
    >
      {{ successMessage }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, useId } from 'vue'
import { profileApi } from '@/services/api/profileApi'

interface Props {
  avatar: string | null
  pseudo: string
}

const props = defineProps<Props>()

const emit = defineEmits<{
  'update:avatar': [avatar: string | null]
  uploaded: [avatar: string]
  deleted: []
}>()

const inputId = useId()
const helpTextId = useId()
const fileInputRef = ref<HTMLInputElement | null>(null)
const isUploading = ref(false)
const error = ref<string | null>(null)
const successMessage = ref<string | null>(null)

const avatarUrl = computed((): string | undefined => {
  if (!props.avatar) return undefined
  if (props.avatar.startsWith('http')) return props.avatar
  const apiUrl = import.meta.env.VITE_API_URL || ''
  const baseUrl = apiUrl.replace(/\/api$/, '')
  if (props.avatar.startsWith('/uploads/')) {
    return `${baseUrl}${props.avatar}`
  }
  return `${baseUrl}/uploads/avatars/${props.avatar}`
})

const triggerFileInput = () => {
  fileInputRef.value?.click()
}

const clearMessages = () => {
  error.value = null
  successMessage.value = null
}

const handleFileSelect = async (event: Event) => {
  const target = event.target as HTMLInputElement
  const file = target.files?.[0]

  if (!file) return

  clearMessages()

  const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']
  if (!allowedTypes.includes(file.type)) {
    error.value = 'Type de fichier non autorisé. Formats acceptés : JPEG, PNG, GIF, WebP.'
    return
  }

  const maxSize = 2 * 1024 * 1024
  if (file.size > maxSize) {
    error.value = 'Le fichier est trop volumineux. Taille maximale : 2 Mo.'
    return
  }

  isUploading.value = true

  try {
    const response = await profileApi.uploadAvatar(file)
    emit('update:avatar', response.avatar)
    emit('uploaded', response.avatar)
    successMessage.value = 'Avatar mis à jour avec succès.'

    setTimeout(() => {
      successMessage.value = null
    }, 3000)
  } catch (err) {
    error.value = err instanceof Error ? err.message : "Erreur lors de l'upload de l'avatar."
  } finally {
    isUploading.value = false
    if (fileInputRef.value) {
      fileInputRef.value.value = ''
    }
  }
}

const handleDelete = async () => {
  clearMessages()
  isUploading.value = true

  try {
    await profileApi.deleteAvatar()
    emit('update:avatar', null)
    emit('deleted')
    successMessage.value = 'Avatar supprimé avec succès.'

    setTimeout(() => {
      successMessage.value = null
    }, 3000)
  } catch (err) {
    error.value = err instanceof Error ? err.message : "Erreur lors de la suppression de l'avatar."
  } finally {
    isUploading.value = false
  }
}
</script>
