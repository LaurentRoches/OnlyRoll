import { get } from '../apiClient'
import { buildWikiQuery, wikiLocaleParam } from '@/utils/wikiQueryBuilder'
import type { PaginatedWikiResponse, ItemListItem, ItemDetail, ItemFilters } from '@/types/wiki'

export const itemApi = {
  async getItems(filters: ItemFilters = {}): Promise<PaginatedWikiResponse<ItemListItem>> {
    return get<PaginatedWikiResponse<ItemListItem>>(`/wiki/items${buildWikiQuery(filters as Record<string, string | number | undefined>)}`)
  },

  async getItem(id: number): Promise<ItemDetail> {
    return get<ItemDetail>(`/wiki/items/${id}${wikiLocaleParam()}`)
  },
}
