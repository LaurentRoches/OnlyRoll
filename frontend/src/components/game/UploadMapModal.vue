<script setup lang="ts">
import { ref, computed } from 'vue'
import { useMapStore } from '@/stores/mapStore'
import { XMarkIcon, ArrowUpTrayIcon, PhotoIcon } from '@heroicons/vue/24/outline'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const props = defineProps<{
  gameId: number
  show: boolean
}>()

const emit = defineEmits<{
  close: []
  success: []
}>()

const mapStore = useMapStore()

const mapName = ref('')
const mapDescription = ref('')
const selectedFile = ref<File | null>(null)
const imagePreview = ref<string | null>(null)
const gridSize = ref(50)
const gridType = ref<'square' | 'hex' | 'none'>('square')
const width = ref(20)
const height = ref(20)

const isUploading = ref(false)
const uploadError = ref<string | null>(null)

const isFormValid = computed(() => {
  return mapName.value.trim().length >= 3 && selectedFile.value !== null
})

/**
 * Gestion de la sélection de fichier
 */
function handleFileSelect(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]

  if (!file) return

  if (!file.type.startsWith('image/')) {
    uploadError.value = t('game.uploadMap.errors.invalidType')
    return
  }

  const maxSize = 10 * 1024 * 1024
  if (file.size > maxSize) {
    uploadError.value = t('game.uploadMap.errors.tooLarge')
    return
  }

  selectedFile.value = file
  uploadError.value = null

  const reader = new FileReader()
  reader.onload = (e) => {
    imagePreview.value = e.target?.result as string
  }
  reader.readAsDataURL(file)
}

function removeFile() {
  selectedFile.value = null
  imagePreview.value = null
}

/**
 * Upload de la carte
 */
async function handleUpload() {
  if (!isFormValid.value || !selectedFile.value) return

  isUploading.value = true
  uploadError.value = null

  try {
    const formData = new FormData()
    formData.append('image', selectedFile.value)
    formData.append('name', mapName.value.trim())
    formData.append('description', mapDescription.value.trim())
    formData.append('gridSize', gridSize.value.toString())
    formData.append('gridType', gridType.value)
    formData.append('width', width.value.toString())
    formData.append('height', height.value.toString())

    const apiUrl = import.meta.env.VITE_API_URL || 'http://localhost/api'
    const response = await fetch(`${apiUrl}/games/${props.gameId}/maps`, {
      method: 'POST',
      credentials: 'include',
      body: formData,
    })

    if (!response.ok) {
      const error = await response.json()
      throw new Error(error.error || t('game.uploadMap.errors.uploadFailed'))
    }

    const newMap = await response.json()

    mapStore.activeMap = newMap
    await mapStore.loadMapTokens(newMap.id)

    await mapStore.loadGameMaps(props.gameId)

    emit('success')
    emit('close')
    resetForm()
  } catch (error: unknown) {
    console.error('Erreur upload:', error)
    uploadError.value =
      error instanceof Error ? error.message : t('game.uploadMap.errors.uploadFailed')
  } finally {
    isUploading.value = false
  }
}

/**
 * Réinitialiser le formulaire
 */
function resetForm() {
  mapName.value = ''
  mapDescription.value = ''
  selectedFile.value = null
  imagePreview.value = null
  gridSize.value = 50
  gridType.value = 'square'
  width.value = 20
  height.value = 20
  uploadError.value = null
}

function close() {
  emit('close')
  resetForm()
}
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-sm"
        @click.self="close"
      >
        <div
          class="bg-secondary-800 rounded-xl shadow-2xl border border-secondary-700 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto"
        >
          <div class="flex items-center justify-between p-6 border-b border-secondary-700">
            <div>
              <h2 class="text-2xl font-bold text-white">📍 {{ t('game.uploadMap.title') }}</h2>
              <p class="text-secondary-400 text-sm mt-1">{{ t('game.uploadMap.subtitle') }}</p>
            </div>
            <button @click="close" class="text-secondary-400 hover:text-white transition-colors">
              <XMarkIcon class="w-6 h-6" />
            </button>
          </div>

          <form @submit.prevent="handleUpload" class="p-6 space-y-6">
            <div class="space-y-2">
              <label class="block text-sm font-medium text-secondary-300">
                {{ t('game.uploadMap.image.label') }}
              </label>

              <div
                v-if="!imagePreview"
                class="border-2 border-dashed border-secondary-600 rounded-lg p-8 text-center hover:border-primary-500 transition-colors cursor-pointer"
              >
                <input
                  type="file"
                  accept="image/*"
                  class="hidden"
                  id="map-file-input"
                  @change="handleFileSelect"
                />
                <label for="map-file-input" class="cursor-pointer">
                  <ArrowUpTrayIcon class="w-12 h-12 mx-auto text-secondary-400 mb-3" />
                  <p class="text-secondary-300 font-medium mb-1">
                    {{ t('game.uploadMap.image.selectPrompt') }}
                  </p>
                  <p class="text-secondary-500 text-sm">{{ t('game.uploadMap.image.formats') }}</p>
                </label>
              </div>

              <div v-else class="relative">
                <img
                  :src="imagePreview"
                  :alt="t('game.uploadMap.image.preview')"
                  class="w-full h-64 object-contain bg-secondary-900 rounded-lg"
                />
                <button
                  type="button"
                  @click="removeFile"
                  class="absolute top-2 right-2 bg-red-600 hover:bg-red-700 text-white p-2 rounded-full transition-colors"
                >
                  <XMarkIcon class="w-5 h-5" />
                </button>
                <p class="text-secondary-400 text-sm mt-2">
                  {{ selectedFile?.name }}
                </p>
              </div>
            </div>

            <div class="space-y-2">
              <label for="map-name" class="block text-sm font-medium text-secondary-300">
                {{ t('game.uploadMap.name.label') }}
              </label>
              <input
                id="map-name"
                v-model="mapName"
                type="text"
                required
                minlength="3"
                maxlength="250"
                :placeholder="t('game.uploadMap.name.placeholder')"
                class="w-full px-4 py-2 bg-secondary-900 border border-secondary-600 rounded-lg text-white placeholder-secondary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
              />
            </div>

            <div class="space-y-2">
              <label for="map-description" class="block text-sm font-medium text-secondary-300">
                {{ t('game.uploadMap.description.label') }}
              </label>
              <textarea
                id="map-description"
                v-model="mapDescription"
                rows="3"
                :placeholder="t('game.uploadMap.description.placeholder')"
                class="w-full px-4 py-2 bg-secondary-900 border border-secondary-600 rounded-lg text-white placeholder-secondary-500 focus:outline-none focus:ring-2 focus:ring-primary-500"
              ></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-2">
                <label for="grid-type" class="block text-sm font-medium text-secondary-300">
                  {{ t('game.uploadMap.grid.type.label') }}
                </label>
                <select
                  id="grid-type"
                  v-model="gridType"
                  class="w-full px-4 py-2 bg-secondary-900 border border-secondary-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                >
                  <option value="square">{{ t('game.uploadMap.grid.type.square') }}</option>
                  <option value="hex">{{ t('game.uploadMap.grid.type.hex') }}</option>
                  <option value="none">{{ t('game.uploadMap.grid.type.none') }}</option>
                </select>
              </div>

              <div class="space-y-2">
                <label for="grid-size" class="block text-sm font-medium text-secondary-300">
                  {{ t('game.uploadMap.grid.size') }}
                </label>
                <input
                  id="grid-size"
                  v-model.number="gridSize"
                  type="number"
                  min="10"
                  max="200"
                  class="w-full px-4 py-2 bg-secondary-900 border border-secondary-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
              </div>

              <div class="space-y-2">
                <label for="grid-width" class="block text-sm font-medium text-secondary-300">
                  {{ t('game.uploadMap.grid.width') }}
                </label>
                <input
                  id="grid-width"
                  v-model.number="width"
                  type="number"
                  min="5"
                  max="100"
                  class="w-full px-4 py-2 bg-secondary-900 border border-secondary-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
              </div>

              <div class="space-y-2">
                <label for="grid-height" class="block text-sm font-medium text-secondary-300">
                  {{ t('game.uploadMap.grid.height') }}
                </label>
                <input
                  id="grid-height"
                  v-model.number="height"
                  type="number"
                  min="5"
                  max="100"
                  class="w-full px-4 py-2 bg-secondary-900 border border-secondary-600 rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-primary-500"
                />
              </div>
            </div>

            <div
              v-if="uploadError"
              class="bg-red-900/20 border border-red-700 text-red-400 px-4 py-3 rounded-lg"
            >
              {{ uploadError }}
            </div>

            <div class="flex gap-3 pt-4">
              <button
                type="button"
                @click="close"
                class="flex-1 px-6 py-3 bg-secondary-700 hover:bg-secondary-600 text-white font-medium rounded-lg transition-colors"
              >
                {{ t('game.uploadMap.cancel') }}
              </button>
              <button
                type="submit"
                :disabled="!isFormValid || isUploading"
                class="flex-1 px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <PhotoIcon v-if="!isUploading" class="w-5 h-5" />
                <svg
                  v-else
                  class="animate-spin h-5 w-5"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
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
                {{ isUploading ? t('game.uploadMap.submit.uploading') : t('game.uploadMap.submit.upload') }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active > div,
.modal-leave-active > div {
  transition: transform 0.3s ease;
}

.modal-enter-from > div,
.modal-leave-to > div {
  transform: scale(0.9);
}
</style>
