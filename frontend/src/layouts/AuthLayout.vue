<template>
  <div
    class="min-h-screen bg-gradient-to-br from-primary-900 via-primary-800 to-secondary-900 flex items-center justify-center p-4"
  >
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center mb-4">
          <div class="w-12 h-12 flex items-center justify-center">
            <img style="width: 100%; height: 100%" src="/images/logo.png" alt="Logo OnlyRoll" />
          </div>
        </div>
        <h1 class="text-2xl font-bold text-secondary-50 mb-2 text-shadow-strong">OnlyRoll</h1>
        <p class="text-secondary-300 text-sm text-shadow-strong">
          {{ t(`auth.layout.pageTitle.${pageTitleKey}`) }}
        </p>
      </div>

      <div class="bg-secondary-800 rounded-xl p-8 shadow-2xl border border-secondary-700">
        <RouterView />
      </div>

      <div class="text-center mt-6 space-y-2">
        <div class="text-sm text-secondary-300 text-shadow-strong">
          <template v-if="$route.name === 'login'">
            {{ t('auth.layout.noAccount') }}
            <RouterLink
              to="/auth/register"
              class="text-primary-300 hover:text-primary-200 font-medium transition-colors text-shadow-strong"
            >
              {{ t('auth.layout.signUp') }}
            </RouterLink>
          </template>
          <template v-else>
            {{ t('auth.layout.hasAccount') }}
            <RouterLink
              to="/auth/login"
              class="text-primary-300 hover:text-primary-200 font-medium transition-colors text-shadow-strong"
            >
              {{ t('auth.layout.signIn') }}
            </RouterLink>
          </template>
        </div>

        <div class="text-xs text-secondary-400 text-shadow-strong">
          <RouterLink to="/" class="hover:text-secondary-300 transition-colors">
            {{ t('auth.layout.backToHome') }}
          </RouterLink>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const route = useRoute()

const pageTitleKey = computed(() => {
  switch (route.name) {
    case 'login':
      return 'login'
    case 'register':
      return 'register'
    default:
      return 'default'
  }
})
</script>
