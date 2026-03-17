import type { WikiFilters } from './common'

export interface ItemListItem {
  id: number
  name: string
  category: string | null
  categorySlug: string | null
  rarity: string | null
  raritySlug: string | null
  isMagical: boolean
  source: string
  isFavorited: boolean
}

export interface ItemDetail extends ItemListItem {
  requiresAttunement: boolean
  attunementText: string | null
  weight: string | null
  valueGp: string | null
  description: string | null
  sources: Array<{ code: string; name: string }>
  page: number | null
  weaponProperties: string[]
  weaponDamages: Array<{ dice: string; damageType: string; versatile: string | null }>
  weapon: { category: string; rangeNormal: number | null; rangeLong: number | null } | null
  armor: {
    type: string
    ac: number
    maxDex: number | null
    strReq: number | null
    stealthDisadv: boolean
  } | null
}

export interface ItemFilters extends WikiFilters {
  category?: string
  rarity?: string
}
