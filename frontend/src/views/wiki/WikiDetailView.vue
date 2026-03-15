<script setup lang="ts">
import { onMounted, computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { useWikiDetailStore } from '@/stores/wiki/useWikiDetailStore'
import { useBreakpoint } from '@/composables/useBreakpoint'
import WikiFavoriteButton from '@/components/wiki/WikiFavoriteButton.vue'
import DashboardNav from '@/components/dashboard/DashboardNav.vue'
import SpellDetail from '@/components/wiki/detail/SpellDetail.vue'
import RaceDetail from '@/components/wiki/detail/RaceDetail.vue'
import ClassDetail from '@/components/wiki/detail/ClassDetail.vue'
import ItemDetail from '@/components/wiki/detail/ItemDetail.vue'
import MonsterDetail from '@/components/wiki/detail/MonsterDetail.vue'
import BackgroundDetail from '@/components/wiki/detail/BackgroundDetail.vue'
import FeatDetail from '@/components/wiki/detail/FeatDetail.vue'
import { ArrowLeftIcon } from '@heroicons/vue/24/outline'
import type {
  SpellDetail as SpellDetailType,
  RaceDetail as RaceDetailType,
  ClassDetail as ClassDetailType,
  ItemDetail as ItemDetailType,
  MonsterDetail as MonsterDetailType,
  BackgroundDetail as BackgroundDetailType,
  FeatDetail as FeatDetailType
} from '@/types/wiki'
import type { WikiCategorySlug } from '@/constants/wiki'

const props = defineProps<{
  category: string
  id: string
}>()

const { t } = useI18n()
const router = useRouter()
const wikiStore = useWikiDetailStore()
const { isMobile } = useBreakpoint()
const scrollContainer = ref<HTMLElement | null>(null)
const srdTable = computed(() => props.category.replace(/s$/, ''))

onMounted(() => {
  wikiStore.fetchItem(props.category as WikiCategorySlug, parseInt(props.id))
})

watch(() => props.id, (newId) => {
  wikiStore.fetchItem(props.category as WikiCategorySlug, parseInt(newId))
  scrollContainer.value?.scrollTo({ top: 0 })
})

const item = computed(() => wikiStore.currentItem as Record<string, unknown> | null)
const spell = computed(() => props.category === 'spells' ? (wikiStore.currentItem as SpellDetailType | null) : null)
const race = computed(() => props.category === 'races' ? (wikiStore.currentItem as RaceDetailType | null) : null)
const cls = computed(() => props.category === 'classes' ? (wikiStore.currentItem as ClassDetailType | null) : null)
const itm = computed(() => props.category === 'items' ? (wikiStore.currentItem as ItemDetailType | null) : null)
const monster = computed(() => props.category === 'monsters' ? (wikiStore.currentItem as MonsterDetailType | null) : null)
const background = computed(() => props.category === 'backgrounds' ? (wikiStore.currentItem as BackgroundDetailType | null) : null)
const feat = computed(() => props.category === 'feats' ? (wikiStore.currentItem as FeatDetailType | null) : null)

function goBack() {
  router.push({ name: 'wiki.category', params: { category: props.category } })
}
</script>

<template>
  <div :class="isMobile ? 'min-h-screen bg-secondary-900' : 'h-screen flex flex-col bg-secondary-900 overflow-hidden'">
    <DashboardNav />
    <div
      :class="[
        'bg-secondary-800 border-b border-secondary-700 px-4 py-4 z-20',
        isMobile ? 'sticky top-16' : 'flex-shrink-0'
      ]"
    >
      <div class="max-w-4xl mx-auto flex items-center justify-between gap-4">
        <div class="flex items-center gap-3 min-w-0">
          <button class="text-secondary-400 hover:text-secondary-200 flex-shrink-0" @click="goBack">
            <ArrowLeftIcon class="w-5 h-5" />
          </button>
          <div class="min-w-0">
            <p class="text-xs text-secondary-400">{{ t('wiki.categories.' + category) }}</p>
            <h1 class="text-lg font-semibold text-secondary-100 truncate">{{ (item?.name as string) ?? '...' }}</h1>
          </div>
        </div>
        <WikiFavoriteButton v-if="item" :srd-table="srdTable" :srd-id="parseInt(id)" />
      </div>
    </div>

    <div ref="scrollContainer" :class="isMobile ? '' : 'flex-1 overflow-y-auto'">
      <div v-if="wikiStore.isLoading" class="max-w-4xl mx-auto px-4 py-6 space-y-4">
        <div class="h-8 w-48 bg-secondary-800 rounded animate-pulse" />
        <div class="h-4 w-full bg-secondary-800 rounded animate-pulse" />
        <div class="h-4 w-3/4 bg-secondary-800 rounded animate-pulse" />
      </div>

      <div v-else-if="wikiStore.error" class="max-w-4xl mx-auto px-4 py-6 text-red-400">
        {{ wikiStore.error }}
      </div>

      <div v-else-if="item" class="max-w-4xl mx-auto px-4 py-6 space-y-6">
        <SpellDetail v-if="spell" :spell="spell" />
        <RaceDetail v-else-if="race" :race="race" />
        <ClassDetail v-else-if="cls" :cls="cls" />
        <ItemDetail v-else-if="itm" :item="itm" />
        <MonsterDetail v-else-if="monster" :monster="monster" />
        <BackgroundDetail v-else-if="background" :background="background" />
        <FeatDetail v-else-if="feat" :feat="feat" />

        <div class="text-xs text-secondary-500 border-t border-secondary-700 pt-4">
          {{ t('wiki.categoryView.source') }} {{ (item?.source as string) ?? '—' }}<span v-if="item?.page"> · {{ t('wiki.categoryView.page') }} {{ item.page }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
