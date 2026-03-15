import { buildQuery } from './queryBuilder'
import i18n from '@/i18n'

/**
 * Builds a URL query string for wiki API calls,
 * automatically injecting the current i18n locale.
 */
export function buildWikiQuery(filters: Record<string, string | number | undefined | null> = {}): string {
  return buildQuery({
    ...filters,
    locale: i18n.global.locale.value,
  })
}

/**
 * Returns the current locale query param string for simple GET calls.
 * Example: "?locale=fr" or "?locale=en"
 */
export function wikiLocaleParam(): string {
  return `?locale=${i18n.global.locale.value}`
}
