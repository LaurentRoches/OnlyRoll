<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRoute, useRouter } from 'vue-router'
import { useWikiListStore } from '@/stores/wiki/useWikiListStore'
import { useWikiDetailStore } from '@/stores/wiki/useWikiDetailStore'
import { useWikiFilters } from '@/composables/useWikiFilters'
import { useBreakpoint } from '@/composables/useBreakpoint'
import { useInfiniteScroll } from '@/composables/useInfiniteScroll'
import WikiSpellCard from '@/components/wiki/WikiSpellCard.vue'
import WikiItemCard from '@/components/wiki/WikiItemCard.vue'
import WikiFilters from '@/components/wiki/WikiFilters.vue'
import SkeletonWikiCard from '@/components/wiki/SkeletonWikiCard.vue'
import DashboardNav from '@/components/dashboard/DashboardNav.vue'
import SpellDetail from '@/components/wiki/detail/SpellDetail.vue'
import RaceDetail from '@/components/wiki/detail/RaceDetail.vue'
import ClassDetail from '@/components/wiki/detail/ClassDetail.vue'
import ItemDetail from '@/components/wiki/detail/ItemDetail.vue'
import MonsterDetail from '@/components/wiki/detail/MonsterDetail.vue'
import BackgroundDetail from '@/components/wiki/detail/BackgroundDetail.vue'
import FeatDetail from '@/components/wiki/detail/FeatDetail.vue'
import { FunnelIcon, ArrowLeftIcon, InboxIcon } from '@heroicons/vue/24/outline'
import type {
  SpellListItem,
  SpellDetail as SpellDetailType,
  RaceDetail as RaceDetailType,
  ClassDetail as ClassDetailType,
  ItemDetail as ItemDetailType,
  MonsterDetail as MonsterDetailType,
  BackgroundDetail as BackgroundDetailType,
  FeatDetail as FeatDetailType,
} from '@/types/wiki'
import type { WikiCategorySlug } from '@/constants/wiki'

const props = defineProps<{
  category: string
}>()

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const wikiStore = useWikiListStore()
const wikiDetailStore = useWikiDetailStore()
const { isMobile, isDesktop } = useBreakpoint()

const showFilters = ref(false)
const sentinel = ref<HTMLElement | null>(null)
const listContainer = ref<HTMLElement | null>(null)
const selectedItemId = ref<number | null>(null)

type CategoryParam = WikiCategorySlug | 'favorites'

const { filters } = useWikiFilters(
  () => props.category,
  (cat, f) => wikiStore.fetchItems(cat as CategoryParam, f)
)

onMounted(() => {
  if (route.query.search) filters.value.search = String(route.query.search)
  wikiStore.fetchItems(props.category as CategoryParam, filters.value)
})

const { isLoading: isScrollLoading } = useInfiniteScroll(sentinel, () => wikiStore.appendItems(), {
  disabled: computed(() => !wikiStore.hasMore),
  root: listContainer,
})

watch(selectedItemId, (id) => {
  if (id !== null && !isMobile.value) {
    wikiDetailStore.fetchItem(props.category as WikiCategorySlug, id)
  }
})

watch(
  () => props.category,
  () => {
    selectedItemId.value = null
  }
)

function goBack() {
  router.push({ name: 'wiki.home' })
}

function isSpell(item: unknown): item is SpellListItem {
  return props.category === 'spells'
}

function selectItem(id: number) {
  selectedItemId.value = id
}

function handleSelectSimilar(id: number) {
  selectedItemId.value = id
}

const spell = computed(() =>
  props.category === 'spells' ? (wikiDetailStore.currentItem as SpellDetailType | null) : null
)
const race = computed(() =>
  props.category === 'races' ? (wikiDetailStore.currentItem as RaceDetailType | null) : null
)
const cls = computed(() =>
  props.category === 'classes' ? (wikiDetailStore.currentItem as ClassDetailType | null) : null
)
const itm = computed(() =>
  props.category === 'items' ? (wikiDetailStore.currentItem as ItemDetailType | null) : null
)
const monster = computed(() =>
  props.category === 'monsters' ? (wikiDetailStore.currentItem as MonsterDetailType | null) : null
)
const background = computed(() =>
  props.category === 'backgrounds'
    ? (wikiDetailStore.currentItem as BackgroundDetailType | null)
    : null
)
const feat = computed(() =>
  props.category === 'feats' ? (wikiDetailStore.currentItem as FeatDetailType | null) : null
)

const detailItem = computed(() => wikiDetailStore.currentItem as Record<string, unknown> | null)
</script>

<template>
  <div
    :class="
      isMobile
        ? 'min-h-screen bg-secondary-900'
        : 'h-screen flex flex-col bg-secondary-900 overflow-hidden'
    "
  >
    <DashboardNav />

    <div
      class="bg-secondary-800 border-b border-secondary-700 px-4 py-3 flex-shrink-0 sticky top-0 z-20"
    >
      <div class="max-w-full flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
          <button
            class="text-secondary-400 hover:text-secondary-200 transition-colors"
            @click="goBack"
          >
            <ArrowLeftIcon class="w-5 h-5" />
          </button>
          <h1 class="text-xl font-semibold text-secondary-100">
            {{ t('wiki.categories.' + category) }}
          </h1>
          <span v-if="wikiStore.pagination.total" class="text-sm text-secondary-400">
            ({{ wikiStore.pagination.total }})
          </span>
        </div>
        <button
          v-if="!isDesktop && category !== 'favorites'"
          class="flex items-center gap-1.5 px-3 py-1.5 text-sm bg-secondary-700 hover:bg-secondary-600 text-secondary-300 rounded transition-colors"
          @click="showFilters = !showFilters"
        >
          <FunnelIcon class="w-4 h-4" />
          {{ t('wiki.categoryView.filtersButton') }}
        </button>
      </div>
    </div>

    <div
      v-if="showFilters && !isDesktop && category !== 'favorites'"
      class="bg-secondary-800 border-b border-secondary-700 px-4 py-4 flex-shrink-0"
    >
      <WikiFilters v-model="filters" :category="category" />
    </div>

    <div :class="isMobile ? 'px-4 py-4' : 'flex flex-1 overflow-hidden'">
      <aside
        v-if="isDesktop && category !== 'favorites'"
        class="flex-[1] min-w-0 overflow-y-auto border-r border-secondary-700 p-4"
      >
        <h2 class="text-xs font-semibold text-secondary-400 uppercase tracking-wider mb-3">
          {{ t('wiki.categoryView.filtersHeading') }}
        </h2>
        <WikiFilters v-model="filters" :category="category" />
      </aside>

      <div
        ref="listContainer"
        :class="
          isMobile
            ? ''
            : isDesktop
              ? 'flex-[3] min-w-0 overflow-y-auto p-4'
              : 'flex-1 min-w-0 overflow-y-auto p-4'
        "
      >
        <div
          v-if="wikiStore.isLoading && !wikiStore.items.length"
          class="grid grid-cols-1 gap-3"
          :class="isMobile ? 'sm:grid-cols-2' : ''"
        >
          <SkeletonWikiCard v-for="n in 12" :key="n" />
        </div>

        <div
          v-else-if="!wikiStore.items.length && !wikiStore.isLoading"
          class="flex flex-col items-center justify-center py-16 text-secondary-400"
        >
          <InboxIcon class="w-12 h-12 mb-3 opacity-40" />
          <p>{{ t('wiki.categoryView.emptyState') }}</p>
        </div>

        <template v-else>
          <div class="grid grid-cols-1 gap-3" :class="isMobile ? 'sm:grid-cols-2' : ''">
            <template v-for="item in wikiStore.items" :key="(item as { id: number }).id">
              <WikiSpellCard
                v-if="isSpell(item)"
                :spell="item"
                :mode="isMobile ? 'navigate' : 'select'"
                :is-selected="(item as { id: number }).id === selectedItemId"
                @select="selectItem"
              />
              <WikiItemCard
                v-else
                :item="
                  item as {
                    id: number
                    name: string
                    source: string
                    isFavorited?: boolean
                    [key: string]: unknown
                  }
                "
                :category="
                  category !== 'favorites'
                    ? category
                    : ((item as { srdTable?: string }).srdTable ?? 'spell') + 's'
                "
                :srd-table="
                  category === 'favorites' ? (item as { srdTable?: string }).srdTable : undefined
                "
                :mode="isMobile ? 'navigate' : 'select'"
                :is-selected="(item as { id: number }).id === selectedItemId"
                @select="selectItem"
              />
            </template>
          </div>

          <div ref="sentinel" class="h-2 mt-4" aria-hidden="true" />

          <div v-if="isScrollLoading" class="grid grid-cols-1 gap-3 mt-3">
            <SkeletonWikiCard v-for="n in 3" :key="n" />
          </div>
        </template>
      </div>

      <div
        v-if="!isMobile"
        class="overflow-y-auto border-l border-secondary-700 p-4"
        :class="isDesktop ? 'flex-[3] min-w-0' : 'w-1/2 flex-shrink-0'"
      >
        <div
          v-if="!selectedItemId"
          class="h-full flex flex-col items-center justify-center text-secondary-500"
        >
          <InboxIcon class="w-16 h-16 mb-4 opacity-30" />
          <p class="text-sm text-center">{{ t('wiki.categoryView.selectItemHint') }}</p>
        </div>

        <div v-else-if="wikiDetailStore.isLoading" class="space-y-4 animate-pulse">
          <div class="h-32 bg-secondary-800 rounded-lg"></div>
          <div class="h-4 bg-secondary-700 rounded w-3/4"></div>
          <div class="h-4 bg-secondary-700 rounded w-full"></div>
          <div class="h-4 bg-secondary-700 rounded w-2/3"></div>
        </div>

        <div v-else-if="wikiDetailStore.error" class="text-red-400 text-sm">
          {{ wikiDetailStore.error }}
        </div>

        <Transition v-else-if="detailItem" name="fade" mode="out-in">
          <div :key="selectedItemId" class="space-y-6">
            <SpellDetail
              v-if="spell"
              :spell="spell"
              mode="inline"
              @select-spell="handleSelectSimilar"
            />
            <RaceDetail v-else-if="race" :race="race" mode="inline" />
            <ClassDetail v-else-if="cls" :cls="cls" mode="inline" />
            <ItemDetail v-else-if="itm" :item="itm" mode="inline" />
            <MonsterDetail v-else-if="monster" :monster="monster" mode="inline" />
            <BackgroundDetail v-else-if="background" :background="background" mode="inline" />
            <FeatDetail v-else-if="feat" :feat="feat" mode="inline" />

            <div class="text-xs text-secondary-500 border-t border-secondary-700 pt-4">
              {{ t('wiki.categoryView.source') }} {{ detailItem?.source ?? '—'
              }}<span v-if="detailItem?.page">
                · {{ t('wiki.categoryView.page') }} {{ detailItem.page }}</span
              >
            </div>
          </div>
        </Transition>
      </div>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
