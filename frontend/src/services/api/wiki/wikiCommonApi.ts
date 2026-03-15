import { get, post, delete as del } from '../apiClient'
import { wikiLocaleParam } from '@/utils/wikiQueryBuilder'
import type { WikiCategory, WikiFavoritesGrouped, WikiSearchResult, FavoriteItem } from '@/types/wiki'
import i18n from '@/i18n'

export const wikiCommonApi = {
  async getCategories(): Promise<WikiCategory[]> {
    return get<WikiCategory[]>(`/wiki/categories${wikiLocaleParam()}`)
  },

  async search(q: string, category?: string): Promise<WikiSearchResult[]> {
    const params = new URLSearchParams({ q, locale: i18n.global.locale.value })
    if (category) params.append('category', category)
    const result = await get<{ results: WikiSearchResult[] }>(`/wiki/search?${params}`)
    return result.results
  },

  async getFavorites(): Promise<WikiFavoritesGrouped> {
    return get<WikiFavoritesGrouped>('/wiki/favorites')
  },

  async getFavoriteItems(): Promise<FavoriteItem[]> {
    const result = await get<{ items: FavoriteItem[] }>('/wiki/favorites/items')
    return result.items
  },

  async addFavorite(srdTable: string, srdId: number): Promise<void> {
    await post('/wiki/favorites', { srdTable, srdId })
  },

  async removeFavorite(srdTable: string, srdId: number): Promise<void> {
    await del(`/wiki/favorites/${srdTable}/${srdId}`)
  },
}
