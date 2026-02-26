<template>
  <nav class="bg-secondary-800 backdrop-blur-sm border-b border-secondary-700 relative z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="flex justify-between items-center h-16">
        <!-- Logo -->
        <RouterLink to="/dashboard" class="flex items-center space-x-3">
          <div
            class="w-8 h-8 bg-gradient-to-br from-primary-500 to-primary-400 rounded-lg flex items-center justify-center"
          >
            <img style="width: 100%; height: 100%" src="/images/logo.png" />
          </div>
          <span class="text-xl font-bold text-secondary-50">OnlyRoll</span>
        </RouterLink>

        <!-- Menu de navigation — desktop -->
        <div class="hidden md:flex items-center space-x-1">
          <RouterLink
            to="/dashboard"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="[
              route.path === '/dashboard'
                ? 'bg-primary-500 text-white'
                : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
            ]"
          >
            Dashboard
          </RouterLink>
          <RouterLink
            to="/games"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="[
              route.path.startsWith('/games')
                ? 'bg-primary-500 text-white'
                : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
            ]"
          >
            Parties
          </RouterLink>
          <RouterLink
            v-if="isAdmin"
            to="/admin"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="[
              route.path.startsWith('/admin')
                ? 'bg-primary-500 text-white'
                : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
            ]"
          >
            Admin
          </RouterLink>
        </div>

        <!-- Menu utilisateur — desktop -->
        <div class="hidden md:flex items-center space-x-4">
          <UserProfileBadge />
          <button
            @click="handleLogout"
            class="px-4 py-2 text-sm text-secondary-300 hover:text-secondary-50 transition-colors"
          >
            Déconnexion
          </button>
        </div>

        <!-- Burger button — mobile uniquement -->
        <button
          class="md:hidden p-2 rounded-lg text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700 transition-colors"
          :aria-expanded="isMobileMenuOpen"
          aria-label="Menu de navigation"
          @click="isMobileMenuOpen = !isMobileMenuOpen"
        >
          <svg
            v-if="!isMobileMenuOpen"
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
              d="M4 6h16M4 12h16M4 18h16"
            />
          </svg>
          <svg
            v-else
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
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>
    </div>

    <!-- Menu mobile déroulant -->
    <div v-if="isMobileMenuOpen" class="md:hidden border-t border-secondary-700 bg-secondary-800">
      <div class="px-4 py-3 space-y-1">
        <RouterLink
          to="/dashboard"
          class="block px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="[
            route.path === '/dashboard'
              ? 'bg-primary-500 text-white'
              : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
          ]"
        >
          Dashboard
        </RouterLink>
        <RouterLink
          to="/games"
          class="block px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="[
            route.path.startsWith('/games')
              ? 'bg-primary-500 text-white'
              : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
          ]"
        >
          Parties
        </RouterLink>
        <RouterLink
          v-if="isAdmin"
          to="/admin"
          class="block px-4 py-2 rounded-lg text-sm font-medium transition-colors"
          :class="[
            route.path.startsWith('/admin')
              ? 'bg-primary-500 text-white'
              : 'text-secondary-300 hover:text-secondary-50 hover:bg-secondary-700',
          ]"
        >
          Admin
        </RouterLink>
      </div>
      <div class="border-t border-secondary-700 px-4 py-3 flex items-center justify-between">
        <UserProfileBadge />
        <button
          @click="handleLogout"
          class="px-4 py-2 text-sm text-secondary-300 hover:text-secondary-50 transition-colors"
        >
          Déconnexion
        </button>
      </div>
    </div>
  </nav>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useAuth } from '@/composables/useAuth'
import { useAuthStore } from '@/stores/auth'
import UserProfileBadge from '@/components/common/UserProfileBadge.vue'

const route = useRoute()
const { logout } = useAuth()
const authStore = useAuthStore()

const isAdmin = computed(() => authStore.isAdmin)
const isMobileMenuOpen = ref(false)

watch(
  () => route.path,
  () => {
    isMobileMenuOpen.value = false
  }
)

const handleLogout = async () => {
  await logout()
}
</script>
