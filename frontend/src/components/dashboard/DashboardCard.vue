<template>
  <div
    class="relative bg-secondary-800 rounded-xl p-6 border border-secondary-700 hover:border-secondary-600 cursor-pointer transition-all duration-200 hover:shadow-lg group"
    :class="{ 'opacity-75': comingSoon }"
    @click="handleClick"
  >
    <div
      v-if="comingSoon"
      class="absolute top-3 right-3 px-2 py-1 bg-warning/20 text-warning text-xs font-medium rounded-full border border-warning/30"
    >
      {{ t('common.dashboardCard.comingSoon') }}
    </div>

    <div class="flex items-center justify-center w-16 h-16 mb-4 group-hover:scale-110 transition-transform duration-200">
      <img :src="icon" :alt="title" class="w-16 h-16 object-contain" />
    </div>

    <h3 class="text-lg font-semibold text-secondary-50 mb-2">
      {{ title }}
    </h3>
    <p class="text-secondary-400 text-sm leading-relaxed">
      {{ description }}
    </p>

    <div class="flex justify-end mt-4">
      <svg
        class="w-5 h-5 text-secondary-500 group-hover:text-primary-400 group-hover:translate-x-1 transition-all duration-200"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M17 8l4 4m0 0l-4 4m4-4H3"
        />
      </svg>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

interface Props {
  title: string
  description: string
  icon: string
  comingSoon?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  comingSoon: false,
})

const emit = defineEmits<{
  click: []
}>()

const handleClick = () => {
  if (!props.comingSoon) {
    emit('click')
  }
}
</script>
