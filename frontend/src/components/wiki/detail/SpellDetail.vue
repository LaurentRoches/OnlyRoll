<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import SpellDamageCalculator from '@/components/wiki/SpellDamageCalculator.vue'
import type { SpellDetail } from '@/types/wiki'
import { DAMAGE_TYPE_IMAGES, SCHOOL_IMAGES } from '@/constants/wiki'

const props = withDefaults(
  defineProps<{
    spell: SpellDetail
    mode?: 'page' | 'inline'
  }>(),
  {
    mode: 'page',
  }
)

const emit = defineEmits<{
  'select-spell': [id: number]
}>()

const { t } = useI18n()
const router = useRouter()

const bannerUrl = computed<string | null>(() => {
  const dt = props.spell.damageTypes?.[0]?.toLowerCase()
  if (dt && DAMAGE_TYPE_IMAGES[dt]) return DAMAGE_TYPE_IMAGES[dt]
  const school = props.spell.school?.toLowerCase()
  if (school && SCHOOL_IMAGES[school]) return SCHOOL_IMAGES[school]
  return null
})

const materialText = computed(() => {
  return props.spell.components?.find((c) => c.material)?.material ?? null
})

function schoolLabel(slug: string | null | undefined, fallback: string | null | undefined): string {
  return slug ? t('wiki.reference.spellSchool.' + slug) : (fallback ?? '')
}

const subtitle = computed(() => {
  const school = schoolLabel(props.spell.schoolSlug, props.spell.school)
  if (props.spell.level === 0) {
    return school
      ? t('wiki.spellDetail.spellSubtitle', { school })
      : t('wiki.spellDetail.spellSubtitleNoSchool')
  }
  return school
    ? t('wiki.spellDetail.spellSubtitleLevel', { level: props.spell.level, school })
    : t('wiki.spellDetail.spellSubtitleLevelNoSchool', { level: props.spell.level })
})

function similarSubtitle(s: {
  school?: string | null
  schoolSlug?: string | null
  level: number
}): string {
  const school = schoolLabel(s.schoolSlug, s.school)
  if (s.level === 0) {
    return school
      ? t('wiki.spellDetail.similarSubtitleLevelZero', { school })
      : t('wiki.spellDetail.spellSubtitleNoSchool')
  }
  return school
    ? t('wiki.spellDetail.similarSubtitleLevel', { level: s.level, school })
    : t('wiki.spellDetail.spellSubtitleLevelNoSchool', { level: s.level })
}

function handleSimilarClick(id: number) {
  if (props.mode === 'inline') {
    emit('select-spell', id)
  } else {
    router.push({ name: 'wiki.detail', params: { category: 'spells', id } })
  }
}
</script>

<template>
  <div class="space-y-6">
    <!-- Hero Banner avec titre overlaid + stats flottantes -->
    <div v-if="bannerUrl" class="relative" :class="mode === 'page' ? '-mx-4 mb-20' : 'mb-10'">
      <div
        class="relative w-full overflow-visible"
        :class="mode === 'page' ? 'h-72 sm:h-96' : 'h-36 rounded-lg overflow-hidden'"
        :style="{ minHeight: '25vh' }"
      >
        <img :src="bannerUrl" :alt="spell.name" class="w-full h-full object-cover" />
        <div
          class="absolute inset-0 bg-gradient-to-b from-transparent via-secondary-900/40 to-secondary-900"
        />

        <!-- Titre overlaid en haut à gauche -->
        <div class="absolute top-4 left-4 sm:left-6 z-10">
          <h2
            class="font-bold text-secondary-100 drop-shadow-lg"
            :class="mode === 'inline' ? 'text-xl' : 'text-2xl sm:text-3xl'"
          >
            {{ spell.name }}
          </h2>
          <p class="text-sm text-secondary-300 mt-1 drop-shadow">
            {{ subtitle }}
          </p>
        </div>
      </div>

      <!-- Grille stats flottante qui chevauche le bas du banner -->
      <div
        class="absolute left-4 right-4 sm:left-6 sm:right-6 z-10"
        :class="mode === 'page' ? '-bottom-16' : '-bottom-8'"
      >
        <div class="bg-secondary-800/80 backdrop-blur-sm rounded-xl p-4 grid grid-cols-2 gap-4">
          <div class="text-center">
            <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.spellDetail.level') }}</p>
            <p class="font-bold text-primary-400">
              {{ spell.level === 0 ? t('wiki.spellDetail.cantrip') : spell.level }}
            </p>
          </div>
          <div class="text-center">
            <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.spellDetail.damage') }}</p>
            <p class="font-bold text-primary-400">{{ spell.damageFormula ?? '—' }}</p>
          </div>
          <div class="text-center">
            <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.spellDetail.range') }}</p>
            <p class="font-bold text-primary-400">
              {{ spell.rangeDistance ? spell.rangeDistance + ' ft' : (spell.rangeType ?? '—') }}
            </p>
          </div>
          <div class="text-center">
            <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.spellDetail.duration') }}</p>
            <p class="font-bold text-primary-400">{{ spell.duration ?? '—' }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Fallback sans banner : titre + stats normaux -->
    <template v-if="!bannerUrl">
      <div>
        <h2
          class="font-bold text-secondary-100"
          :class="mode === 'inline' ? 'text-xl' : 'text-2xl sm:text-3xl'"
        >
          {{ spell.name }}
        </h2>
        <p class="text-sm text-secondary-400 mt-1">{{ subtitle }}</p>
      </div>
      <div class="grid grid-cols-2 gap-3">
        <div class="bg-secondary-800/80 rounded-xl p-3 text-center">
          <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.spellDetail.level') }}</p>
          <p class="font-bold text-primary-400">
            {{ spell.level === 0 ? t('wiki.spellDetail.cantrip') : spell.level }}
          </p>
        </div>
        <div class="bg-secondary-800/80 rounded-xl p-3 text-center">
          <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.spellDetail.damage') }}</p>
          <p class="font-bold text-primary-400">{{ spell.damageFormula ?? '—' }}</p>
        </div>
        <div class="bg-secondary-800/80 rounded-xl p-3 text-center">
          <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.spellDetail.range') }}</p>
          <p class="font-bold text-primary-400">
            {{ spell.rangeDistance ? spell.rangeDistance + ' ft' : (spell.rangeType ?? '—') }}
          </p>
        </div>
        <div class="bg-secondary-800/80 rounded-xl p-3 text-center">
          <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.spellDetail.duration') }}</p>
          <p class="font-bold text-primary-400">{{ spell.duration ?? '—' }}</p>
        </div>
      </div>
    </template>

    <!-- Composants -->
    <div v-if="spell.components?.length">
      <h3 class="text-xl font-bold text-secondary-100 mb-4">
        {{ t('wiki.spellDetail.components') }}
      </h3>
      <div class="bg-secondary-800 border border-secondary-700 rounded-xl p-5">
        <div class="flex items-start gap-6 justify-center">
          <div
            v-for="comp in spell.components"
            :key="comp.type"
            class="flex flex-col items-center gap-2"
          >
            <div
              class="w-14 h-14 rounded-full bg-secondary-800 border-2 flex items-center justify-center text-secondary-100 font-bold text-lg"
              :class="{
                'border-emerald-500': comp.type === 'V',
                'border-blue-500': comp.type === 'S',
                'border-amber-500': comp.type === 'M',
              }"
            >
              {{ comp.type }}
            </div>
            <span class="text-xs text-secondary-400">
              {{
                comp.type === 'V'
                  ? t('wiki.spellDetail.verbal')
                  : comp.type === 'S'
                    ? t('wiki.spellDetail.somatic')
                    : t('wiki.spellDetail.material')
              }}
            </span>
          </div>
        </div>
        <p v-if="materialText" class="mt-4 text-sm text-secondary-400">
          {{ materialText }}
        </p>
      </div>
    </div>

    <!-- Description -->
    <div v-if="spell.description">
      <h3 class="text-xl font-bold text-secondary-100 mb-4">
        {{ t('wiki.spellDetail.description') }}
      </h3>
      <div
        class="bg-secondary-800 rounded-lg p-4 border-l-4 border-primary-500 text-secondary-200 text-sm leading-relaxed whitespace-pre-line"
      >
        {{ spell.description }}
      </div>
    </div>

    <!-- Calculateur de dégâts -->
    <div v-if="spell.damageFormula">
      <h3 class="text-xl font-bold text-secondary-100 mb-4">
        {{ t('wiki.spellDetail.damageCalculator') }}
      </h3>
      <SpellDamageCalculator
        :level="spell.level"
        :damage-formula="spell.damageFormula"
        :upcast-dice-per-level="spell.upcastDicePerLevel"
        :upcast-dice-faces="spell.upcastDiceFaces"
        :damage-type="spell.damageTypes?.[0] ?? null"
      />
    </div>

    <!-- Classes -->
    <div v-if="spell.classes?.length">
      <h3 class="text-xl font-bold text-secondary-100 mb-4">{{ t('wiki.spellDetail.classes') }}</h3>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="cls in spell.classes"
          :key="cls.id"
          class="px-4 py-1.5 bg-primary-600 text-white rounded-full text-sm font-medium"
        >
          {{ cls.name }}
        </span>
      </div>
    </div>

    <!-- Sources -->
    <div v-if="spell.sources?.length" class="text-xs text-secondary-500">
      {{ t('wiki.spellDetail.sources') }} {{ spell.sources.map((s) => s.code).join(', ') }}
    </div>

    <!-- Sorts similaires -->
    <div v-if="spell.similarSpells?.length">
      <h3 class="text-xl font-bold text-secondary-100 mb-4">
        {{ t('wiki.spellDetail.similarSpells') }}
      </h3>
      <div class="space-y-2">
        <div
          v-for="s in spell.similarSpells"
          :key="s.id"
          class="p-3 bg-secondary-800 border border-secondary-700 border-l-4 border-l-primary-500 rounded-lg cursor-pointer hover:border-secondary-500 hover:border-l-primary-400 transition-colors"
          @click="handleSimilarClick(s.id)"
        >
          <p class="text-sm font-semibold text-secondary-100">{{ s.name }}</p>
          <p class="text-xs text-secondary-400 mt-0.5">{{ similarSubtitle(s) }}</p>
        </div>
      </div>
    </div>
  </div>
</template>
