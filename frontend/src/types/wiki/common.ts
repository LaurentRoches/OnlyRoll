export interface PaginatedWikiResponse<T> {
  data: T[]
  total: number
  page: number
  limit: number
  totalPages: number
}

export interface WikiFilters {
  search?: string
  source?: string
  page?: number
  limit?: number
}

export interface WikiCategory {
  slug: string
  icon: string | null
  name: string
  sortOrder: number
}

export interface WikiFavoriteEntry {
  srdTable: string
  srdId: number
  createdAt: string
}

export interface WikiFavoritesGrouped {
  favorites: Record<string, WikiFavoriteEntry[]>
}

export interface WikiSearchResult {
  id: number
  name: string
  category: string
  meta: string | null
}

export interface FavoriteItem {
  id: number
  name: string
  srdTable: string
  source: string
  level?: number
  school?: string | null
  size?: string | null
  hitDie?: number
  rarity?: string | null
  cr?: string | null
  type?: string | null
  prerequisite?: string | null
}
