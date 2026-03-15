export const WIKI_CATEGORY_SLUGS = [
  'spells',
  'races',
  'classes',
  'backgrounds',
  'feats',
  'items',
  'monsters',
] as const

export type WikiCategorySlug = typeof WIKI_CATEGORY_SLUGS[number]
