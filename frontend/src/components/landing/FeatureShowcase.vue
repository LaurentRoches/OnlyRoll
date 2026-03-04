<template>
  <section id="features" ref="sectionRef" class="py-20 bg-secondary-50/5">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
      <div class="text-center mb-16">
        <h2
          class="text-3xl md:text-4xl font-bold text-secondary-50 mb-4 text-shadow-strong reveal-up"
          :class="{ revealed: isVisible }"
        >
          Tout ce qu'il faut pour jouer
        </h2>
        <p
          class="text-secondary-400 text-lg reveal-up"
          :class="{ revealed: isVisible }"
          style="transition-delay: 100ms"
        >
          Une boîte à outils complète pour vos aventures D&D 5e
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div
          class="md:col-span-2 reveal-left"
          :class="{ revealed: isVisible }"
          style="transition-delay: 150ms"
        >
          <div
            class="h-full bg-secondary-800 rounded-xl p-8 border border-secondary-700 hover:border-primary-500/50 transition-all duration-300 group hover:-translate-y-1 hover:shadow-purple-lg"
            style="min-height: 260px"
          >
            <div
              class="flex items-center justify-center w-16 h-16 rounded-xl mb-6 bg-primary-500/10 group-hover:scale-110 transition-transform duration-200"
            >
              <svg
                class="w-8 h-8 text-primary-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"
                />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-secondary-50 mb-3">Wiki D&D</h3>
            <p class="text-secondary-400 leading-relaxed">
              Toutes les données officielles de D&D 5e à portée de main ! Sorts, monstres,
              équipements — tout le SRD intégré directement dans votre session.
            </p>
          </div>
        </div>

        <div class="flex flex-col gap-6">
          <div class="reveal-up" :class="{ revealed: isVisible }" style="transition-delay: 250ms">
            <div
              class="bg-secondary-800 rounded-xl p-6 border border-secondary-700 hover:border-emerald-500/50 transition-all duration-300 group hover:-translate-y-1 hover:shadow-lg"
            >
              <div
                class="flex items-center justify-center w-12 h-12 rounded-lg mb-4 bg-emerald-500/10 group-hover:scale-110 transition-transform duration-200"
              >
                <svg
                  class="w-6 h-6 text-emerald-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                  />
                </svg>
              </div>
              <h3 class="text-base font-semibold text-secondary-50 mb-1">Feuille perso</h3>
              <p class="text-secondary-400 text-sm leading-relaxed">
                Feuilles de personnage personnalisées et automatisées depuis le SRD.
              </p>
            </div>
          </div>

          <div class="reveal-up" :class="{ revealed: isVisible }" style="transition-delay: 350ms">
            <div
              class="bg-secondary-800 rounded-xl p-6 border border-secondary-700 hover:border-cyan-500/50 transition-all duration-300 group hover:-translate-y-1 hover:shadow-lg"
            >
              <div
                class="flex items-center justify-center w-12 h-12 rounded-lg mb-4 bg-cyan-500/10 group-hover:scale-110 transition-transform duration-200"
              >
                <svg
                  class="w-6 h-6 text-cyan-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                  />
                </svg>
              </div>
              <h3 class="text-base font-semibold text-secondary-50 mb-1">Chat Temps Réel</h3>
              <p class="text-secondary-400 text-sm leading-relaxed">
                Résultats de dés et messages en temps réel avec vos compagnons.
              </p>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="flex flex-col gap-6">
          <div class="reveal-up" :class="{ revealed: isVisible }" style="transition-delay: 400ms">
            <div
              class="bg-secondary-800 rounded-xl p-6 border border-secondary-700 hover:border-amber-500/50 transition-all duration-300 group hover:-translate-y-1 hover:shadow-lg"
            >
              <div
                class="flex items-center justify-center w-12 h-12 rounded-lg mb-4 bg-amber-500/10 group-hover:scale-110 transition-transform duration-200"
              >
                <svg
                  class="w-6 h-6 text-amber-400"
                  fill="currentColor"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path
                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zM9.5 16.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5zm5 0c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"
                  />
                </svg>
              </div>
              <h3 class="text-base font-semibold text-secondary-50 mb-1">Lanceur de Dés</h3>
              <p class="text-secondary-400 text-sm leading-relaxed">
                Système avancé avec historique, avantage/désavantage et formules complexes.
              </p>
            </div>
          </div>

          <div class="reveal-up" :class="{ revealed: isVisible }" style="transition-delay: 500ms">
            <div
              class="bg-secondary-800 rounded-xl p-6 border border-secondary-700 hover:border-rose-500/50 transition-all duration-300 group hover:-translate-y-1 hover:shadow-lg"
            >
              <div
                class="flex items-center justify-center w-12 h-12 rounded-lg mb-4 bg-rose-500/10 group-hover:scale-110 transition-transform duration-200"
              >
                <svg
                  class="w-6 h-6 text-rose-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                  aria-hidden="true"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a4 4 0 11-8 0 4 4 0 018 0z"
                  />
                </svg>
              </div>
              <h3 class="text-base font-semibold text-secondary-50 mb-1">Multijoueur</h3>
              <p class="text-secondary-400 text-sm leading-relaxed">
                Créez vos parties et invitez vos amis facilement.
              </p>
            </div>
          </div>
        </div>

        <div
          class="md:col-span-2 reveal-up"
          :class="{ revealed: isVisible }"
          style="transition-delay: 300ms"
        >
          <div
            class="h-full bg-secondary-800 rounded-xl p-8 border border-secondary-700 hover:border-purple-500/50 transition-all duration-300 group hover:-translate-y-1 hover:shadow-lg"
            style="min-height: 260px"
          >
            <div
              class="flex items-center justify-center w-16 h-16 rounded-xl mb-6 bg-purple-500/10 group-hover:scale-110 transition-transform duration-200"
            >
              <svg
                class="w-8 h-8 text-purple-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                aria-hidden="true"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"
                />
              </svg>
            </div>
            <h3 class="text-xl font-bold text-secondary-50 mb-3">Table Virtuelle</h3>
            <p class="text-secondary-400 leading-relaxed">
              Une interface de jeu complète pour le MJ et les joueurs. Gérez vos sessions, partagez
              des informations et vivez l'aventure ensemble, même à distance.
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useScrollReveal } from '@/composables/useScrollReveal'

const sectionRef = ref<HTMLElement>()
const { isVisible } = useScrollReveal(sectionRef, { threshold: 0.1 })
</script>
