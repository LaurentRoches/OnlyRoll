import type { WikiFilters } from './common'

export interface BackgroundListItem {
  id: number
  name: string
  source: string
  page: number | null
  isFavorited: boolean
}

export interface BackgroundDetail extends BackgroundListItem {
  sources: Array<{ code: string; name: string }>
  description: string | null
  featureName: string | null
  featureDescription: string | null
  skills: string[]
  equipment: Array<{ name: string; qty: number }>
}

export type BackgroundFilters = WikiFilters
