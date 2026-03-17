<script setup lang="ts">
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import type { WikiCategory } from '@/types/wiki'
import { CATEGORY_ICONS, CATEGORY_COLORS } from '@/constants/wiki'

const { t } = useI18n()

const props = defineProps<{
  category: WikiCategory
}>()

const router = useRouter()

function navigate() {
  router.push({ name: 'wiki.category', params: { category: props.category.slug } })
}
</script>

<template>
  <div
    class="relative bg-gradient-to-br from-secondary-900 via-secondary-900 border rounded-lg p-5 cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg"
    :class="CATEGORY_COLORS[category.slug] ?? 'border-secondary-700/50 hover:border-secondary-500'"
    @click="navigate"
  >
    <div class="flex items-center gap-3">
      <img
        :src="CATEGORY_ICONS[category.slug] ?? '/images/wiki/icons/wiki_icon.png'"
        :alt="category.name"
        class="w-12 h-12 object-contain flex-shrink-0"
      />
      <div>
        <h3 class="font-semibold text-secondary-100 capitalize">{{ category.name }}</h3>
        <p class="text-xs text-secondary-400 mt-0.5">{{ t('wiki.categoryCard.browse') }}</p>
      </div>
    </div>
  </div>
</template>
