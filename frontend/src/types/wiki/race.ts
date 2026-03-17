import type { WikiFilters } from './common'

export interface RaceListItem {
  id: number
  name: string
  size: string | null
  sizeSlug: string | null
  walkSpeed: number | null
  source: string
  isFavorited: boolean
}

export interface RaceTrait {
  name: string
  description: string | null
}

export interface RaceDetail extends RaceListItem {
  abilityModifiers: Record<string, number> | null
  description: string | null
  sources: Array<{ code: string; name: string }>
  page: number | null
  traits: RaceTrait[]
  subraces: Array<{ id: number; name: string; source: string }>
  speeds: Array<{ type: string; value: number }>
}

export interface SubraceDetail {
  id: number
  name: string
  source: string
  abilityModifiers: Record<string, number> | null
  description: string | null
}

export interface RaceFilters extends WikiFilters {
  size?: string
  speed_type?: string
  vision?: string
}
