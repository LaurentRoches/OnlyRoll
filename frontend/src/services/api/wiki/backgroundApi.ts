import { get } from '../apiClient'
import { buildWikiQuery, wikiLocaleParam } from '@/utils/wikiQueryBuilder'
import type {
  PaginatedWikiResponse,
  BackgroundListItem,
  BackgroundDetail,
  WikiFilters,
} from '@/types/wiki'

export const backgroundApi = {
  async getBackgrounds(
    filters: WikiFilters = {}
  ): Promise<PaginatedWikiResponse<BackgroundListItem>> {
    return get<PaginatedWikiResponse<BackgroundListItem>>(
      `/wiki/backgrounds${buildWikiQuery(filters as Record<string, string | number | undefined>)}`
    )
  },

  async getBackground(id: number): Promise<BackgroundDetail> {
    return get<BackgroundDetail>(`/wiki/backgrounds/${id}${wikiLocaleParam()}`)
  },
}
