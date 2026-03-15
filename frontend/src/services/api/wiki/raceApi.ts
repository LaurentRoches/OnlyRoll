import { get } from '../apiClient'
import { buildWikiQuery, wikiLocaleParam } from '@/utils/wikiQueryBuilder'
import type { PaginatedWikiResponse, RaceListItem, RaceDetail, SubraceDetail, RaceFilters } from '@/types/wiki'

export const raceApi = {
  async getRaces(filters: RaceFilters = {}): Promise<PaginatedWikiResponse<RaceListItem>> {
    return get<PaginatedWikiResponse<RaceListItem>>(`/wiki/races${buildWikiQuery(filters as Record<string, string | number | undefined>)}`)
  },

  async getRace(id: number): Promise<RaceDetail> {
    return get<RaceDetail>(`/wiki/races/${id}${wikiLocaleParam()}`)
  },

  async getSubrace(id: number): Promise<SubraceDetail> {
    return get<SubraceDetail>(`/wiki/races/subraces/${id}${wikiLocaleParam()}`)
  },
}
