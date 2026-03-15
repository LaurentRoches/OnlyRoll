import type { WikiFilters } from './common'

export interface FeatListItem {
  id: number
  name: string
  source: string
  prerequisite: string | null
  isFavorited: boolean
}

export interface FeatDetail extends FeatListItem {
  sources: Array<{ code: string; name: string }>
  description: string | null
  abilityModifiers: Array<{ ability: string; value: number }>
}

export type FeatFilters = WikiFilters
