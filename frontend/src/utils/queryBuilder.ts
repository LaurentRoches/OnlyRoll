/**
 * Builds a URL query string from a filters object,
 * skipping undefined, null and empty string values.
 */
export function buildQuery(filters: Record<string, string | number | undefined | null>): string {
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(filters)) {
    if (value !== undefined && value !== '' && value !== null) {
      params.append(key, String(value))
    }
  }
  const q = params.toString()
  return q ? `?${q}` : ''
}
