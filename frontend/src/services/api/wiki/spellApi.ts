import { get } from '../apiClient'
import { buildWikiQuery, wikiLocaleParam } from '@/utils/wikiQueryBuilder'
import type { PaginatedWikiResponse, SpellListItem, SpellDetail, SpellFilters } from '@/types/wiki'

export const spellApi = {
  async getSpells(filters: SpellFilters = {}): Promise<PaginatedWikiResponse<SpellListItem>> {
    return get<PaginatedWikiResponse<SpellListItem>>(
      `/wiki/spells${buildWikiQuery(filters as Record<string, string | number | undefined>)}`
    )
  },

  async getSpell(id: number): Promise<SpellDetail> {
    return get<SpellDetail>(`/wiki/spells/${id}${wikiLocaleParam()}`)
  },
}
