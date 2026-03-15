import type { WikiFilters } from './common'

export interface ClassListItem {
  id: number
  name: string
  hitDie: number
  source: string
  isFavorited: boolean
}

export interface ClassFeature {
  name: string
  level: number
  description: string | null
}

export interface ClassTableGroup {
  title?: string
  colLabels: string[]
  rows?: unknown[][]
  rowsSpellProgression?: number[][]
}

export interface ClassDetail extends ClassListItem {
  savingThrows: string[] | null
  spellcastingAbility: string | null
  casterProgression: string | null
  sources: Array<{ code: string; name: string }>
  page: number | null
  features: ClassFeature[]
  subclasses: Array<{ id: number; name: string; shortName: string | null; source: string }>
  proficiencies?: {
    armor: string[]
    weapons: string[]
    tools: string[]
    skills: unknown[]
  } | null
  startingEquipment?: string[] | null
  multiclassing?: { requirements?: Record<string, number>; proficienciesGained?: unknown } | null
  classTableGroups?: ClassTableGroup[] | null
}

export interface SubclassDetail {
  id: number
  name: string
  shortName: string | null
  source: string
  page: number | null
  description: string | null
  subclassFeatures?: Array<{ name: string; level: number; description: string | null }>
}

export type ClassFilters = WikiFilters
