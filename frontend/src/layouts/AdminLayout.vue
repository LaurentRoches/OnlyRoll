<template>
  <div class="admin-layout min-h-screen bg-secondary-900">
    <nav aria-label="Liens d'accès rapide" class="absolute top-0 left-0 z-50">
      <a
        href="#main-nav"
        class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:z-50 focus:px-4 focus:py-3 focus:bg-primary-600 focus:text-white focus:font-semibold focus:no-underline focus:outline-2 focus:outline-primary-400"
      >
        Aller à la navigation
      </a>
      <a
        href="#main-content"
        class="sr-only focus:not-sr-only focus:absolute focus:top-0 focus:left-0 focus:z-50 focus:px-4 focus:py-3 focus:bg-primary-600 focus:text-white focus:font-semibold focus:no-underline focus:outline-2 focus:outline-primary-400"
      >
        Aller au contenu principal
      </a>
    </nav>

    <header role="banner" class="bg-secondary-800 border-b border-secondary-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <div class="flex items-center justify-between">
          <div class="flex items-center space-x-3 sm:space-x-4">
            <button
              type="button"
              class="lg:hidden p-1.5 rounded-lg text-secondary-400 hover:text-secondary-200 hover:bg-secondary-700 transition-colors"
              :aria-expanded="sidebarOpen"
              aria-label="Ouvrir la navigation"
              @click="sidebarOpen = !sidebarOpen"
            >
              <svg
                class="w-5 h-5"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"
                />
              </svg>
            </button>

            <RouterLink
              to="/dashboard"
              class="text-secondary-400 hover:text-secondary-200 transition-colors"
              aria-label="Retour au tableau de bord utilisateur"
            >
              <svg
                class="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 19l-7-7m0 0l7-7m-7 7h18"
                />
              </svg>
            </RouterLink>
            <div>
              <h1 class="sr-only">Administration OnlyRoll</h1>
              <span class="text-xl font-bold text-secondary-50" aria-hidden="true">
                Administration
              </span>
            </div>
          </div>

          <div class="flex items-center space-x-3 sm:space-x-4">
            <span class="hidden sm:inline text-secondary-400 text-sm">
              Connecté en tant que
              <strong class="text-secondary-200">{{ currentUser?.pseudo }}</strong>
            </span>
            <strong class="hidden sm:inline text-secondary-200 text-sm">{{
              currentUser?.pseudo
            }}</strong>
            <button
              type="button"
              class="px-3 py-1.5 bg-secondary-700 hover:bg-secondary-600 text-secondary-200 rounded-lg transition-colors text-sm"
              @click="handleLogout"
            >
              Déconnexion
            </button>
          </div>
        </div>
      </div>
    </header>

    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-30 bg-black/50 lg:hidden"
      aria-hidden="true"
      @click="sidebarOpen = false"
    />

    <div class="flex">
      <nav
        id="main-nav"
        role="navigation"
        aria-label="Navigation administration"
        class="fixed lg:static inset-y-0 left-0 z-40 w-64 bg-secondary-800 border-r border-secondary-700 min-h-screen transition-transform duration-300 ease-in-out"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
      >
        <div class="flex items-center justify-between px-4 pt-4 pb-2 lg:hidden">
          <span class="text-sm font-medium text-secondary-300">Navigation</span>
          <button
            type="button"
            class="p-1.5 rounded-lg text-secondary-400 hover:text-secondary-200 hover:bg-secondary-700 transition-colors"
            aria-label="Fermer la navigation"
            @click="sidebarOpen = false"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <ul role="list" class="py-4">
          <li v-for="item in navigationItems" :key="item.to">
            <RouterLink
              :to="item.to"
              class="flex items-center px-4 py-3 text-secondary-300 hover:bg-secondary-700 hover:text-secondary-100 transition-colors focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500"
              :aria-current="isCurrentRoute(item.to) ? 'page' : undefined"
              :class="{ 'bg-secondary-700 text-secondary-100': isCurrentRoute(item.to) }"
            >
              <component :is="item.icon" class="w-5 h-5 mr-3" aria-hidden="true" />
              <span>{{ item.label }}</span>
            </RouterLink>
          </li>
        </ul>
      </nav>

      <main id="main-content" role="main" tabindex="-1" class="flex-1 min-w-0">
        <nav
          v-if="breadcrumbs.length > 1"
          aria-label="Fil d'Ariane"
          class="bg-secondary-850 border-b border-secondary-700 px-4 sm:px-6 py-3"
        >
          <ol role="list" class="flex items-center space-x-2 text-sm">
            <li v-for="(crumb, index) in breadcrumbs" :key="crumb.path" class="flex items-center">
              <svg
                v-if="index > 0"
                class="w-4 h-4 text-secondary-500 mx-2"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
              <RouterLink
                v-if="index < breadcrumbs.length - 1"
                :to="crumb.path"
                class="text-secondary-400 hover:text-secondary-200 transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 rounded px-1"
              >
                {{ crumb.name }}
              </RouterLink>
              <span v-else aria-current="page" class="text-secondary-200 font-medium px-1">
                {{ crumb.name }}
              </span>
            </li>
          </ol>
        </nav>

        <div class="p-3 sm:p-6">
          <router-view />
        </div>
      </main>
    </div>

    <div role="status" aria-live="polite" aria-atomic="true" class="sr-only">
      {{ announcement }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch, h, type FunctionalComponent } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const DashboardIcon: FunctionalComponent = () =>
  h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', {
      'stroke-linecap': 'round',
      'stroke-linejoin': 'round',
      'stroke-width': '2',
      d: 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
    }),
  ])

const UsersIcon: FunctionalComponent = () =>
  h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', {
      'stroke-linecap': 'round',
      'stroke-linejoin': 'round',
      'stroke-width': '2',
      d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z',
    }),
  ])

const AuditIcon: FunctionalComponent = () =>
  h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
    h('path', {
      'stroke-linecap': 'round',
      'stroke-linejoin': 'round',
      'stroke-width': '2',
      d: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    }),
  ])

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()

const announcement = ref('')
const sidebarOpen = ref(false)

const currentUser = computed(() => authStore.user)

const navigationItems = [
  { to: '/admin', label: 'Tableau de bord', icon: DashboardIcon },
  { to: '/admin/users', label: 'Utilisateurs', icon: UsersIcon },
  { to: '/admin/audit-logs', label: "Logs d'audit", icon: AuditIcon },
]

const breadcrumbsMap: Record<string, string> = {
  '/admin': 'Tableau de bord',
  '/admin/users': 'Utilisateurs',
  '/admin/audit-logs': "Logs d'audit",
}

const breadcrumbs = computed(() => {
  const crumbs = [{ path: '/admin', name: 'Administration' }]
  const currentPath = route.path

  if (currentPath !== '/admin') {
    const name = breadcrumbsMap[currentPath] || route.meta.title || 'Page'
    crumbs.push({ path: currentPath, name: String(name) })
  }

  return crumbs
})

const isCurrentRoute = (path: string): boolean => {
  return route.path === path
}

const handleLogout = async () => {
  announcement.value = 'Déconnexion en cours...'
  await authStore.logout()
  router.push({ name: 'home' })
}

watch(
  () => route.path,
  () => {
    sidebarOpen.value = false
    const pageName = breadcrumbsMap[route.path] || 'Page'
    announcement.value = `Navigation vers ${pageName}`
    setTimeout(() => {
      announcement.value = ''
    }, 1000)
  }
)
</script>

<style scoped>
.bg-secondary-850 {
  background-color: #1e2330;
}
</style>
