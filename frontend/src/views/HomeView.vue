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
            <div class="w-10 h-10 bg-gradient-to-br rounded-lg flex items-center justify-center">
              <img style="width: 100%; height: 100%" src="/images/logo.png" alt="Logo OnlyRoll" />
            </div>
            <span class="text-xl font-bold text-secondary-50">OnlyRoll</span>
          </RouterLink>

          <!-- Navigation menu -->
          <div class="hidden md:flex items-center space-x-8">
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
          <div class="flex items-center space-x-4">
            <template v-if="isAuthenticated">
              <UserProfileBadge />
              <RouterLink
                to="/dashboard"
                class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
              >
                Dashboard
              </RouterLink>
            </template>
            <template v-else>
              <RouterLink
                to="/auth/login"
                class="px-4 py-2 text-secondary-300 hover:text-secondary-50 transition-colors"
              >
                Connexion
              </RouterLink>
              <RouterLink
                to="/auth/register"
                class="px-4 py-2 bg-primary-500 hover:bg-primary-600 text-white rounded-lg font-medium transition-colors"
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
      <!-- Background Image avec overlay - optimisé pour visibilité tout en maintenant contraste WCAG -->
      <div class="absolute inset-0 z-0">
        <img
          src="/images/hero_banner_onlyroll.jpg"
          alt=""
          class="w-full h-full object-cover opacity-40"
          aria-hidden="true"
        />
        <!-- Overlay gradient pour garantir le contraste du texte -->
        <div
          class="absolute inset-0 bg-gradient-to-b from-primary-900/40 via-primary-800/35 to-secondary-900/40"
        ></div>
      </div>

      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <!-- Titre principal -->
        <h1 class="text-4xl md:text-6xl font-bold text-secondary-50 mb-6 text-shadow-stronger">
          OnlyRoll
        </h1>
        <p class="text-xl md:text-2xl text-secondary-200 mb-4 text-shadow-stronger">
          One site to Roll them all
        </p>
        <p class="text-lg text-secondary-400 mb-12 max-w-3xl mx-auto text-shadow-strong">
          Jouez à distance avec vos amis !<br />
          Interface simple, données SRD intégrées,<br />
          aucune installation requise.
        </p>

        <!-- Boutons d'action -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center">
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
        </div>
      </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-secondary-50/5">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-center text-secondary-50 mb-16 text-shadow-strong">
          Fonctionnalités
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
          <!-- Wiki D&D -->
          <FeatureCard
            title="Wiki D&D"
            description="Toutes les données officielles de D&D 5e à portée de main !"
            icon="book"
            color="primary"
          />

          <!-- Feuille de personnage -->
          <FeatureCard
            title="Feuille perso"
            description="Des feuilles de personnage personnalisées et automatisées depuis le SRD"
            icon="user"
            color="accent-emerald"
          />

          <!-- Chat temps réel -->
          <FeatureCard
            title="Chat Temps Réel"
            description="Vos résultats de dés et vos meilleurs vannes en temps réel"
            icon="chat"
            color="accent-cyan"
          />

          <!-- Lanceur de dés -->
          <FeatureCard
            title="Lanceur de Dés"
            description="Système de dés avancé avec historique et formules complexes"
            icon="dice"
            color="accent-amber"
          />

          <!-- Table virtuelle -->
          <FeatureCard
            title="Table Virtuelle"
            description="Interface de jeu complète pour MJ et joueurs"
            icon="table"
            color="accent-purple"
          />

          <!-- Multijoueur -->
          <FeatureCard
            title="Multijoueur"
            description="Créez vos parties et invitez vos amis facilement"
            icon="users"
            color="accent-rose"
          />
        </div>
      </div>
    </section>

    <!-- Demo Section -->
    <section class="py-20">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold text-secondary-50 mb-8 text-shadow-strong">Interface</h2>
        <p class="text-lg text-secondary-400 mb-12 text-shadow-strong">Aperçu interface</p>

        <!-- Placeholder pour screenshot/demo -->
        <div class="bg-secondary-800 rounded-xl p-8 border border-secondary-700 max-w-4xl mx-auto">
          <div class="aspect-video bg-secondary-700 rounded-lg flex items-center justify-center">
            <div class="text-center">
              <svg
                class="w-16 h-16 text-secondary-500 mx-auto mb-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                />
              </svg>
              <p class="text-secondary-400 text-lg">Aperçu interface</p>
            </div>
          </div>
        </div>

        <div class="mt-8">
          <RouterLink
            to="/auth/register"
            class="inline-flex items-center px-6 py-3 bg-primary-500 hover:bg-primary-600 text-white font-medium rounded-lg transition-colors"
          >
            Essayer maintenant
            <svg
              class="ml-2 w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M17 8l4 4m0 0l-4 4m4-4H3"
              />
            </svg>
          </RouterLink>
        </div>
      </div>
    </section>

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
            © 2024 OnlyRoll • Tous droits réservés
            <span class="mx-2">🇫🇷</span>
            Français
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { useAuth } from '@/composables/useAuth'
import FeatureCard from '@/components/common/FeatureCard.vue'
import UserProfileBadge from '@/components/common/UserProfileBadge.vue'

const { isAuthenticated } = useAuth()
</script>
