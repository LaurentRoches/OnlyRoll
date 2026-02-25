<template>
  <div
    class="bg-secondary-800 rounded-lg overflow-hidden border border-secondary-700 animate-pulse"
  >
    <!-- Header image placeholder -->
    <div class="h-32 bg-secondary-700 relative">
      <!-- Badge status -->
      <div class="absolute top-3 right-3">
        <div class="h-5 w-20 bg-secondary-600 rounded-full"></div>
      </div>
    </div>

    <!-- Contenu -->
    <div class="p-4">
      <!-- Titre -->
      <div class="h-5 bg-secondary-700 rounded w-3/4 mb-2"></div>

      <!-- Maître du jeu -->
      <div class="h-3 bg-secondary-700 rounded w-1/2 mb-2"></div>

      <!-- Niveau campagne -->
      <div class="h-3 bg-secondary-700 rounded w-2/5 mb-5"></div>

      <!-- Footer : joueurs + bouton -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="h-5 w-5 bg-secondary-700 rounded-full"></div>
          <div class="h-3 bg-secondary-700 rounded w-28"></div>
        </div>
        <div class="h-8 w-16 bg-secondary-700 rounded-md"></div>
      </div>
    </div>
  </div>
</template>
