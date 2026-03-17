<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import type { ClassDetail, SubclassDetail, ClassTableGroup } from '@/types/wiki'
import { wikiApi } from '@/services/api/wikiApi'

const { t } = useI18n()

const props = withDefaults(
  defineProps<{
    cls: ClassDetail
    mode?: 'page' | 'inline'
  }>(),
  { mode: 'page' }
)

const activeTab = ref<'progression' | 'features' | 'info' | 'subclasses'>('progression')
const selectedSubclassId = ref<number | null>(null)
const subclassDetail = ref<SubclassDetail | null>(null)
const isLoadingSubclass = ref(false)

watch(
  () => props.cls,
  () => {
    activeTab.value = 'progression'
    selectedSubclassId.value = null
    subclassDetail.value = null
  }
)

function profBonus(level: number): string {
  return `+${Math.ceil(level / 4) + 1}`
}

const featuresByLevel = computed(() => {
  if (!props.cls.features) return {} as Record<number, typeof props.cls.features>
  return props.cls.features.reduce(
    (acc, f) => {
      ;(acc[f.level] ??= []).push(f)
      return acc
    },
    {} as Record<number, typeof props.cls.features>
  )
})

const levelsWithFeatures = computed(() =>
  [...new Set(props.cls.features?.map((f) => f.level) ?? [])].sort((a, b) => a - b)
)

function cleanColLabel(label: string): string {
  return label.replace(/\{@\w+\s([^|}]+)[^}]*\}/g, '$1').trim()
}

function tableGroupCellValue(group: ClassTableGroup, levelIndex: number, colIndex: number): string {
  if (group.rowsSpellProgression) {
    const val = group.rowsSpellProgression[levelIndex]?.[colIndex]
    return val !== undefined && val !== 0 ? String(val) : '—'
  }
  if (group.rows) {
    const cell = group.rows[levelIndex]?.[colIndex]
    if (cell === undefined || cell === null) return '—'
    if (typeof cell === 'object' && cell !== null && 'value' in cell)
      return String((cell as { value: unknown }).value)
    return String(cell) || '—'
  }
  return '—'
}

const proficiencyLines = computed(() => {
  const prof = props.cls.proficiencies
  if (!prof) return null
  const lines: { label: string; value: string }[] = []
  if (prof.armor?.length)
    lines.push({ label: t('wiki.classDetail.proficiencies.armor'), value: prof.armor.join(', ') })
  if (prof.weapons?.length)
    lines.push({
      label: t('wiki.classDetail.proficiencies.weapons'),
      value: prof.weapons.join(', '),
    })
  if (prof.tools?.length)
    lines.push({ label: t('wiki.classDetail.proficiencies.tools'), value: prof.tools.join(', ') })
  return lines.length ? lines : null
})

async function toggleSubclass(id: number) {
  if (selectedSubclassId.value === id) {
    selectedSubclassId.value = null
    subclassDetail.value = null
    return
  }
  selectedSubclassId.value = id
  subclassDetail.value = null
  isLoadingSubclass.value = true
  try {
    subclassDetail.value = await wikiApi.getSubclass(id)
  } finally {
    isLoadingSubclass.value = false
  }
}
</script>

<template>
  <div class="space-y-6 overflow-hidden">
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
      <div class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.classDetail.hitDie') }}</p>
        <p class="font-bold text-secondary-100">d{{ cls.hitDie }}</p>
      </div>
      <div class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.classDetail.savingThrows') }}</p>
        <p class="font-bold text-secondary-100 capitalize">
          {{ cls.savingThrows?.join(', ') ?? '—' }}
        </p>
      </div>
      <div class="bg-secondary-800 rounded-lg p-3 text-center">
        <p class="text-xs text-secondary-400 mb-1">{{ t('wiki.classDetail.spellcasting') }}</p>
        <p class="font-bold text-secondary-100 uppercase">{{ cls.spellcastingAbility ?? '—' }}</p>
      </div>
    </div>

    <div class="border-b border-secondary-700">
      <div class="flex gap-0 overflow-x-auto">
        <button
          class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors -mb-px"
          :class="
            activeTab === 'progression'
              ? 'border-primary-400 text-primary-300'
              : 'border-transparent text-secondary-400 hover:text-secondary-200'
          "
          @click="activeTab = 'progression'"
        >
          {{ t('wiki.classDetail.tabs.progression') }}
        </button>
        <button
          class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors -mb-px"
          :class="
            activeTab === 'features'
              ? 'border-primary-400 text-primary-300'
              : 'border-transparent text-secondary-400 hover:text-secondary-200'
          "
          @click="activeTab = 'features'"
        >
          {{ t('wiki.classDetail.tabs.features') }}
        </button>
        <button
          v-if="cls.proficiencies || cls.startingEquipment?.length || cls.multiclassing"
          class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors -mb-px"
          :class="
            activeTab === 'info'
              ? 'border-primary-400 text-primary-300'
              : 'border-transparent text-secondary-400 hover:text-secondary-200'
          "
          @click="activeTab = 'info'"
        >
          {{ t('wiki.classDetail.tabs.info') }}
        </button>
        <button
          v-if="cls.subclasses?.length"
          class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 transition-colors -mb-px"
          :class="
            activeTab === 'subclasses'
              ? 'border-primary-400 text-primary-300'
              : 'border-transparent text-secondary-400 hover:text-secondary-200'
          "
          @click="activeTab = 'subclasses'"
        >
          {{ t('wiki.classDetail.tabs.subclasses') }}
          <span class="text-xs opacity-60">({{ cls.subclasses.length }})</span>
        </button>
      </div>
    </div>

    <div
      v-if="activeTab === 'progression'"
      class="overflow-x-auto rounded-lg border border-secondary-700"
    >
      <table class="w-full text-sm min-w-max">
        <thead>
          <tr class="bg-secondary-800 border-b border-secondary-700">
            <th
              class="px-3 py-2.5 text-center text-xs font-medium text-secondary-400 uppercase tracking-wide w-12"
            >
              {{ t('wiki.classDetail.progressionTable.level') }}
            </th>
            <th
              class="px-3 py-2.5 text-center text-xs font-medium text-secondary-400 uppercase tracking-wide w-24"
            >
              {{ t('wiki.classDetail.progressionTable.proficiencyBonus') }}
            </th>
            <th
              class="px-3 py-2.5 text-left text-xs font-medium text-secondary-400 uppercase tracking-wide"
            >
              {{ t('wiki.classDetail.progressionTable.features') }}
            </th>
            <template v-if="cls.classTableGroups?.length">
              <template v-for="(group, gi) in cls.classTableGroups" :key="gi">
                <th
                  v-for="(label, ci) in group.colLabels"
                  :key="ci"
                  class="px-2 py-2.5 text-center text-xs font-medium text-secondary-400 uppercase tracking-wide whitespace-nowrap"
                >
                  {{ cleanColLabel(label) }}
                </th>
              </template>
            </template>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="lvl in 20"
            :key="lvl"
            class="border-b border-secondary-700/50 last:border-0"
            :class="lvl % 2 === 0 ? 'bg-secondary-800/40' : 'bg-secondary-700/10'"
          >
            <td class="px-3 py-2 font-mono text-secondary-300 text-center">{{ lvl }}</td>
            <td class="px-3 py-2 text-secondary-300 text-center font-medium">
              {{ profBonus(lvl) }}
            </td>
            <td class="px-3 py-2 text-secondary-400 text-sm">
              {{ featuresByLevel[lvl]?.map((f) => f.name).join(', ') || '—' }}
            </td>
            <template v-if="cls.classTableGroups?.length">
              <template v-for="(group, gi) in cls.classTableGroups" :key="gi">
                <td
                  v-for="(_, ci) in group.colLabels"
                  :key="ci"
                  class="px-2 py-2 text-center text-secondary-400 font-mono text-xs"
                >
                  {{ tableGroupCellValue(group, lvl - 1, ci) }}
                </td>
              </template>
            </template>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-else-if="activeTab === 'features'" class="space-y-6">
      <div v-if="!levelsWithFeatures.length" class="text-secondary-500 text-sm text-center py-6">
        {{ t('wiki.classDetail.noFeatures') }}
      </div>
      <div v-for="lvl in levelsWithFeatures" :key="lvl">
        <div class="flex items-center gap-3 mb-3">
          <span
            class="text-xs font-semibold px-2 py-0.5 bg-primary-900/60 text-primary-400 border border-primary-700/50 rounded"
            >{{ t('wiki.classDetail.levelN', { level: lvl }) }}</span
          >
          <div class="flex-1 h-px bg-secondary-700/60" />
        </div>
        <div class="space-y-4 pl-1">
          <div v-for="feature in featuresByLevel[lvl]" :key="feature.name + lvl">
            <p class="text-xs font-semibold text-secondary-200 uppercase tracking-wide mb-1">
              {{ feature.name }}
            </p>
            <p
              v-if="feature.description"
              class="text-secondary-400 text-sm whitespace-pre-line leading-relaxed"
            >
              {{ feature.description }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="activeTab === 'info'" class="space-y-5">
      <div v-if="proficiencyLines?.length">
        <h3 class="text-xs font-semibold text-secondary-400 uppercase tracking-wide mb-2">
          {{ t('wiki.classDetail.proficiencies.heading') }}
        </h3>
        <div class="bg-secondary-800 rounded-lg p-4 space-y-2">
          <div v-for="line in proficiencyLines" :key="line.label" class="flex gap-2 text-sm">
            <span class="text-secondary-500 w-20 flex-shrink-0">{{ line.label }}</span>
            <span class="text-secondary-200 capitalize">{{ line.value }}</span>
          </div>
        </div>
      </div>
      <div v-if="cls.startingEquipment?.length">
        <h3 class="text-xs font-semibold text-secondary-400 uppercase tracking-wide mb-2">
          {{ t('wiki.classDetail.startingEquipment') }}
        </h3>
        <div class="bg-secondary-800 rounded-lg p-4 space-y-1">
          <p v-for="(line, i) in cls.startingEquipment" :key="i" class="text-secondary-300 text-sm">
            {{ line }}
          </p>
        </div>
      </div>
      <div v-if="cls.multiclassing">
        <h3 class="text-xs font-semibold text-secondary-400 uppercase tracking-wide mb-2">
          {{ t('wiki.classDetail.multiclassing.heading') }}
        </h3>
        <div class="bg-secondary-800 rounded-lg p-4 space-y-2 text-sm">
          <div
            v-if="
              cls.multiclassing.requirements && Object.keys(cls.multiclassing.requirements).length
            "
          >
            <span class="text-secondary-500"
              >{{ t('wiki.classDetail.multiclassing.prerequisites') }}
            </span>
            <span class="text-secondary-200 uppercase">
              {{
                Object.entries(cls.multiclassing.requirements)
                  .map(([k, v]) => `${k} ${v}`)
                  .join(', ')
              }}
            </span>
          </div>
        </div>
      </div>
    </div>

    <div v-else-if="activeTab === 'subclasses'">
      <div class="flex overflow-x-auto gap-1 pb-2 border-b border-secondary-700 mb-4">
        <button
          v-for="sc in cls.subclasses"
          :key="sc.id"
          class="flex-shrink-0 px-3 py-1.5 rounded text-sm transition-colors border"
          :class="
            selectedSubclassId === sc.id
              ? 'bg-primary-900/50 text-primary-200 border-primary-500'
              : 'bg-secondary-800 text-secondary-400 border-secondary-600 hover:text-secondary-200 hover:border-secondary-400'
          "
          @click="toggleSubclass(sc.id)"
        >
          {{ sc.shortName ?? sc.name }}
          <span class="ml-1 text-xs opacity-50">{{ sc.source }}</span>
        </button>
      </div>

      <div v-if="selectedSubclassId !== null">
        <div
          v-if="isLoadingSubclass"
          class="bg-secondary-800 rounded-lg p-4 space-y-2 animate-pulse"
        >
          <div class="h-4 w-48 bg-secondary-700 rounded" />
          <div class="h-3 w-full bg-secondary-700 rounded" />
          <div class="h-3 w-4/5 bg-secondary-700 rounded" />
        </div>
        <div v-else-if="subclassDetail" class="space-y-5">
          <div class="bg-secondary-800 border border-secondary-600 rounded-lg p-5 space-y-3">
            <div class="flex items-start justify-between flex-wrap gap-2">
              <div>
                <h4 class="font-semibold text-secondary-100 text-base">
                  {{ subclassDetail.name }}
                </h4>
                <p
                  v-if="
                    subclassDetail.shortName && subclassDetail.shortName !== subclassDetail.name
                  "
                  class="text-xs text-secondary-400 mt-0.5"
                >
                  {{ subclassDetail.shortName }}
                </p>
              </div>
              <div class="text-right">
                <span
                  class="text-xs font-medium px-2 py-0.5 bg-secondary-700 text-secondary-300 rounded"
                  >{{ subclassDetail.source }}</span
                >
                <p v-if="subclassDetail.page" class="text-xs text-secondary-500 mt-1">
                  {{ t('wiki.classDetail.page') }} {{ subclassDetail.page }}
                </p>
              </div>
            </div>
            <div v-if="subclassDetail.description" class="border-t border-secondary-700 pt-3">
              <p class="text-secondary-300 text-sm italic leading-relaxed whitespace-pre-line">
                {{ subclassDetail.description }}
              </p>
            </div>
          </div>

          <div v-if="subclassDetail.subclassFeatures?.length" class="space-y-5">
            <template
              v-for="lvl in [...new Set(subclassDetail.subclassFeatures.map((f) => f.level))].sort(
                (a, b) => a - b
              )"
              :key="lvl"
            >
              <div>
                <div class="flex items-center gap-3 mb-3">
                  <span
                    class="text-xs font-semibold px-2 py-0.5 bg-primary-900/60 text-primary-400 border border-primary-700/50 rounded"
                    >{{ t('wiki.classDetail.levelN', { level: lvl }) }}</span
                  >
                  <div class="flex-1 h-px bg-secondary-700/60" />
                </div>
                <div class="space-y-4 pl-1">
                  <div
                    v-for="sf in subclassDetail.subclassFeatures!.filter((f) => f.level === lvl)"
                    :key="sf.name + lvl"
                  >
                    <p
                      class="text-xs font-semibold text-secondary-200 uppercase tracking-wide mb-1"
                    >
                      {{ sf.name }}
                    </p>
                    <p
                      v-if="sf.description"
                      class="text-secondary-400 text-sm whitespace-pre-line leading-relaxed"
                    >
                      {{ sf.description }}
                    </p>
                  </div>
                </div>
              </div>
            </template>
          </div>
        </div>
      </div>
      <div v-else class="text-sm text-secondary-500 text-center py-6">
        {{ t('wiki.classDetail.subclassSelectHint') }}
      </div>
    </div>
  </div>
</template>
