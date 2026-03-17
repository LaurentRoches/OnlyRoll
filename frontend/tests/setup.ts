/**
 * Vitest global setup
 * Sets the locale to 'fr' before i18n module is loaded,
 * so all tests run with French translations by default.
 */
localStorage.setItem('locale', 'fr')
