import { get } from '../apiClient'
import { buildWikiQuery, wikiLocaleParam } from '@/utils/wikiQueryBuilder'
import type {
  PaginatedWikiResponse,
  MonsterListItem,
  MonsterDetail,
  MonsterFilters,
} from '@/types/wiki'

export const monsterApi = {
  async getMonsters(filters: MonsterFilters = {}): Promise<PaginatedWikiResponse<MonsterListItem>> {
    return get<PaginatedWikiResponse<MonsterListItem>>(
      `/wiki/monsters${buildWikiQuery(filters as Record<string, string | number | undefined>)}`
    )
  },

  async getMonster(id: number): Promise<MonsterDetail> {
    return get<MonsterDetail>(`/wiki/monsters/${id}${wikiLocaleParam()}`)
  },
}
