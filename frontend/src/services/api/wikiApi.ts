/**
 * Aggregated wiki API — combines all entity-specific API modules.
 * Import individual modules from services/api/wiki/ for tree-shaking.
 */
import { spellApi } from './wiki/spellApi'
import { raceApi } from './wiki/raceApi'
import { classApi } from './wiki/classApi'
import { itemApi } from './wiki/itemApi'
import { monsterApi } from './wiki/monsterApi'
import { backgroundApi } from './wiki/backgroundApi'
import { featApi } from './wiki/featApi'
import { wikiCommonApi } from './wiki/wikiCommonApi'

export { spellApi, raceApi, classApi, itemApi, monsterApi, backgroundApi, featApi, wikiCommonApi }

export const wikiApi = {
  ...wikiCommonApi,
  ...spellApi,
  ...raceApi,
  ...classApi,
  ...itemApi,
  ...monsterApi,
  ...backgroundApi,
  ...featApi,
}
