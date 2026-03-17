import { get } from '../apiClient'
import { buildWikiQuery, wikiLocaleParam } from '@/utils/wikiQueryBuilder'
import type {
  PaginatedWikiResponse,
  ClassListItem,
  ClassDetail,
  SubclassDetail,
  WikiFilters,
} from '@/types/wiki'

export const classApi = {
  async getClasses(filters: WikiFilters = {}): Promise<PaginatedWikiResponse<ClassListItem>> {
    return get<PaginatedWikiResponse<ClassListItem>>(
      `/wiki/classes${buildWikiQuery(filters as Record<string, string | number | undefined>)}`
    )
  },

  async getClass(id: number): Promise<ClassDetail> {
    return get<ClassDetail>(`/wiki/classes/${id}${wikiLocaleParam()}`)
  },

  async getSubclass(id: number): Promise<SubclassDetail> {
    return get<SubclassDetail>(`/wiki/classes/subclasses/${id}${wikiLocaleParam()}`)
  },
}
