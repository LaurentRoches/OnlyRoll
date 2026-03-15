import type { WikiFilters } from './common'

export interface MonsterListItem {
  id: number
  name: string
  type: string | null
  typeSlug: string | null
  size: string | null
  sizeSlug: string | null
  cr: string | null
  source: string
  isFavorited: boolean
}

export interface MonsterDetail extends MonsterListItem {
  sources: Array<{ code: string; name: string }>
  alignment: string | null
  alignmentSlug: string | null
  armorClass: number | null
  armorDesc: string | null
  hitPointsAvg: number | null
  hitDiceFormula: string | null
  walkSpeed: number | null
  speeds: Record<string, number> | null
  str: number | null
  dex: number | null
  con: number | null
  int: number | null
  wis: number | null
  cha: number | null
  passivePerception: number | null
  xp: number | null
  page: number | null
  traits: Array<{ name: string; description: string | null }>
  actions: Array<{ name: string; description: string | null; isLegendary: boolean; isReaction: boolean; isBonus: boolean }>
  savingThrows: Array<{ ability: string; bonus: number }>
  skills: Array<{ skill: string; bonus: number }>
  senses: Array<{ type: string; range: number }>
  damageResistances: Array<{ damageType: string; type: string }>
  conditionImmunities: string[]
  languages: string[]
  environments: string[]
}

export interface MonsterFilters extends WikiFilters {
  type?: string
  size?: string
  cr?: string
}
