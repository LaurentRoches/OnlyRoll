import type { WikiFilters } from './common'

export interface SpellListItem {
  id: number
  name: string
  level: number
  school: string | null
  schoolSlug: string | null
  castingTime: string | null
  range: string
  isConcentration: boolean
  isRitual: boolean
  isFavorited: boolean
}

export interface SpellComponent {
  type: 'V' | 'S' | 'M'
  material: string | null
  cost: number | null
  consumed: boolean
}

export interface SpellDetail extends SpellListItem {
  rangeType: string | null
  rangeDistance: number | null
  duration: string | null
  description: string | null
  damageFormula: string | null
  upcastDicePerLevel: number | null
  upcastDiceFaces: number | null
  scalingLevelDice: Record<string, unknown> | null
  sources: Array<{ code: string; name: string }>
  page: number | null
  components: SpellComponent[]
  classes: Array<{ id: number; name: string }>
  damageTypes: string[]
  similarSpells: SpellListItem[]
  wikiContent: string | null
}

export interface SpellFilters extends WikiFilters {
  level?: number | string
  school?: string
  class?: string
  damage_type?: string
  components?: string
}
