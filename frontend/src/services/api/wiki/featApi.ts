import { get } from '../apiClient'
import { buildWikiQuery, wikiLocaleParam } from '@/utils/wikiQueryBuilder'
import type { PaginatedWikiResponse, FeatListItem, FeatDetail, WikiFilters } from '@/types/wiki'

export const featApi = {
  async getFeats(filters: WikiFilters = {}): Promise<PaginatedWikiResponse<FeatListItem>> {
    return get<PaginatedWikiResponse<FeatListItem>>(
      `/wiki/feats${buildWikiQuery(filters as Record<string, string | number | undefined>)}`
    )
  },

  async getFeat(id: number): Promise<FeatDetail> {
    return get<FeatDetail>(`/wiki/feats/${id}${wikiLocaleParam()}`)
  },
}
