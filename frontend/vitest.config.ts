import { fileURLToPath } from 'node:url'
import { mergeConfig, defineConfig, configDefaults } from 'vitest/config'
import viteConfig from './vite.config'

export default mergeConfig(
  viteConfig,
  defineConfig({
    test: {
      environment: 'jsdom',
      exclude: [...configDefaults.exclude, 'e2e/**'],
      root: fileURLToPath(new URL('./', import.meta.url)),
      coverage: {
        provider: 'v8',
        reporter: ['text', 'json', 'html', 'lcov'],
        exclude: [
          'node_modules/',
          'tests/',
          'e2e/',
          'dist/',
          '**/*.spec.ts',
          '**/*.test.ts',
          '**/types/**',
          'vite.config.ts',
          'vitest.config.ts',
          'playwright.config.ts',
          'tailwind.config.js',
          'postcss.config.js',
          'eslint.config.ts',
          '.prettierrc.json',
          'src/main.ts',
          'src/**/*.d.ts',
          'env.d.ts',
          // Exclure les composants Vue (présentation)
          'src/**/*.vue',
          'src/App.vue',
          // Exclure uniquement fichiers non-critiques
          'src/router/**', // Navigation - configuration routage
          'src/composables/useKonamiCode.ts', // Easter egg
        ],
        all: true,
        include: ['src/**/*.ts'],
        thresholds: {
          lines: 80,
          functions: 80,
          branches: 80,
          statements: 80,
        },
      },
    },
  }),
)
