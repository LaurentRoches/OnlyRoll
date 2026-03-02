<template>
  <div class="min-h-screen bg-gradient-to-br from-primary-900 via-primary-800 to-secondary-900">
    <!-- Navigation -->
    <nav class="relative z-50 bg-secondary-800 backdrop-blur-sm border-b border-secondary-700">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Logo -->
          <RouterLink
            to="/"
            class="flex items-center space-x-3 hover:opacity-80 transition-opacity"
          >
            <div class="w-10 h-10 flex items-center justify-center">
              <img style="width: 100%; height: 100%" src="/images/logo.png" alt="Logo OnlyRoll" />
            </div>
            <span class="text-xl font-bold text-secondary-50">OnlyRoll</span>
          </RouterLink>

          <!-- Navigation menu -->
          <div class="hidden md:flex items-center space-x-8">
            <RouterLink
              to="/games"
              class="text-secondary-300 hover:text-secondary-50 transition-colors"
            >
              Parties
            </RouterLink>
            <a
              href="#features"
              class="text-secondary-300 hover:text-secondary-50 transition-colors"
            >
              Fonctionnalités
            </a>
            <a href="#about" class="text-secondary-300 hover:text-secondary-50 transition-colors">
              À propos
            </a>
            <a href="#contact" class="text-secondary-300 hover:text-secondary-50 transition-colors">
              Contact
            </a>
          </div>

          <!-- Auth buttons -->
          <div class="flex items-center space-x-2 sm:space-x-4">
            <template v-if="isAuthenticated">
              <UserProfileBadge class="hidden sm:flex" />
              <RouterLink
                to="/dashboard"
                class="px-3 py-2 sm:px-4 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors text-sm sm:text-base"
              >
                Dashboard
              </RouterLink>
            </template>
            <template v-else>
              <RouterLink
                to="/auth/login"
                class="hidden sm:inline-flex px-4 py-2 text-secondary-300 hover:text-secondary-50 transition-colors"
              >
                Connexion
              </RouterLink>
              <RouterLink
                to="/auth/register"
                class="px-3 py-2 sm:px-4 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors text-sm sm:text-base whitespace-nowrap"
              >
                Inscription
              </RouterLink>
            </template>
          </div>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative py-20 lg:py-32 overflow-hidden">
      <!-- Background Image avec overlay -->
      <div class="absolute inset-0 z-0">
        <img
          src="/images/hero_banner_onlyroll.jpg"
          alt=""
          class="w-full h-full object-cover opacity-40"
          aria-hidden="true"
        />
        <div
          class="absolute inset-0 bg-gradient-to-b from-primary-900/40 via-primary-800/35 to-secondary-900/40"
        ></div>
      </div>

      <!-- Particules flottantes (z-index intermédiaire) -->
      <HeroParticles class="z-[1]" />

      <!-- Contenu hero avec animations d'entrée staggerées -->
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <h1
          class="text-4xl md:text-6xl font-bold text-secondary-50 mb-6 text-shadow-stronger reveal-up"
          :class="{ revealed: heroReady }"
        >
          OnlyRoll
        </h1>
        <p
          class="text-xl md:text-2xl text-secondary-200 mb-4 text-shadow-stronger reveal-up"
          :class="{ revealed: heroReady }"
          style="transition-delay: 200ms"
        >
          One site to Roll them all
        </p>
        <p
          class="text-lg text-secondary-400 mb-12 max-w-3xl mx-auto text-shadow-strong reveal-up"
          :class="{ revealed: heroReady }"
          style="transition-delay: 400ms"
        >
          Votre prochaine aventure commence ici.<br />
          Lancez les dés, explorez les donjons,<br />
          le tout depuis votre navigateur.
        </p>

        <!-- Boutons CTA -->
        <div
          class="flex flex-col sm:flex-row gap-4 justify-center items-center reveal-up"
          :class="{ revealed: heroReady }"
          style="transition-delay: 600ms"
        >
          <template v-if="isAuthenticated">
            <RouterLink
              to="/dashboard"
              class="px-8 py-4 bg-primary-500 hover:bg-primary-600 text-white text-lg font-semibold rounded-lg shadow-purple transition-all duration-200 hover:shadow-purple-lg transform hover:-translate-y-1"
            >
              Mon Dashboard
            </RouterLink>
            <RouterLink
              to="/games"
              class="px-8 py-4 bg-secondary-700 hover:bg-secondary-600 text-secondary-50 text-lg font-semibold rounded-lg border border-secondary-600 transition-colors"
            >
              Voir les parties
            </RouterLink>
          </template>
          <template v-else>
            <RouterLink
              to="/auth/register"
              class="px-8 py-4 bg-primary-500 hover:bg-primary-600 text-white text-lg font-semibold rounded-lg shadow-purple transition-all duration-200 hover:shadow-purple-lg transform hover:-translate-y-1"
            >
              Inscription
            </RouterLink>
            <RouterLink
              to="/auth/login"
              class="px-8 py-4 bg-secondary-700 hover:bg-secondary-600 text-secondary-50 text-lg font-semibold rounded-lg border border-secondary-600 transition-colors"
            >
              Connexion
            </RouterLink>
          </template>
        </div>

        <!-- Lien discret vers les parties pour les visiteurs -->
        <div
          v-if="!isAuthenticated"
          class="mt-6 reveal-up"
          :class="{ revealed: heroReady }"
          style="transition-delay: 750ms"
        >
          <RouterLink
            to="/games"
            class="inline-flex items-center gap-2 text-secondary-400 hover:text-secondary-200 transition-colors text-sm"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"
              />
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            Parcourir les parties sans inscription
          </RouterLink>
        </div>
      </div>

      <!-- Scroll indicator -->
      <div
        class="absolute bottom-8 left-1/2 -translate-x-1/2 text-secondary-500 animate-bounce-gentle reveal-up"
        :class="{ revealed: heroReady }"
        style="transition-delay: 800ms"
        aria-label="Défiler vers le bas"
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
            d="M19 9l-7 7-7-7"
          />
        </svg>
      </div>
    </section>

    <!-- Fonctionnalités (layout asymétrique) -->
    <FeatureShowcase />

    <!-- Stats / Social Proof -->
    <StatsCounter />

    <!-- Aperçu in-game avec tilt 3D -->
    <GamePreview />

    <!-- Comment ça marche -->
    <HowItWorks />

    <!-- CTA final immersif -->
    <FinalCTA />

    <!-- Footer -->
    <footer class="bg-secondary-900 border-t border-secondary-800 py-12">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid md:grid-cols-4 gap-8">
          <!-- Logo et description -->
          <div class="md:col-span-2">
            <div class="flex items-center space-x-3 mb-4">
              <div class="w-8 h-8 rounded-lg flex items-center justify-center">
                <img class="w-full h-full" src="/images/logo.png" alt="Logo OnlyRoll" />
              </div>
              <span class="text-xl font-bold text-secondary-50">OnlyRoll</span>
            </div>
            <p class="text-secondary-400 text-sm max-w-md">
              La table virtuelle moderne pour D&D 5e
            </p>
          </div>

          <!-- Réseaux sociaux -->
          <div>
            <h3 class="text-secondary-200 font-semibold mb-4">Réseaux sociaux</h3>
            <ul class="space-y-2 text-sm text-secondary-400">
              <li><a href="#" class="hover:text-secondary-300 transition-colors">GitHub</a></li>
              <li><a href="#" class="hover:text-secondary-300 transition-colors">Discord</a></li>
            </ul>
          </div>

          <!-- Support -->
          <div>
            <h3 class="text-secondary-200 font-semibold mb-4">Support</h3>
            <ul class="space-y-2 text-sm text-secondary-400">
              <li><a href="#" class="hover:text-secondary-300 transition-colors">FAQ</a></li>
              <li><a href="#" class="hover:text-secondary-300 transition-colors">Contact</a></li>
            </ul>
          </div>
        </div>

        <!-- Copyright -->
        <div class="border-t border-secondary-800 mt-8 pt-8 text-center">
          <p class="text-secondary-500 text-sm">
            © 2025 OnlyRoll • Tous droits réservés
            <span class="mx-2">🇫🇷</span>
            Français
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuth } from '@/composables/useAuth'
import UserProfileBadge from '@/components/common/UserProfileBadge.vue'
import HeroParticles from '@/components/landing/HeroParticles.vue'
import FeatureShowcase from '@/components/landing/FeatureShowcase.vue'
import StatsCounter from '@/components/landing/StatsCounter.vue'
import GamePreview from '@/components/landing/GamePreview.vue'
import HowItWorks from '@/components/landing/HowItWorks.vue'
import FinalCTA from '@/components/landing/FinalCTA.vue'

const { isAuthenticated } = useAuth()

// Animations d'entrée du hero déclenchées après le montage (stagger CSS)
const heroReady = ref(false)
onMounted(() => {
  setTimeout(() => {
    heroReady.value = true
  }, 50)
})
</script>
