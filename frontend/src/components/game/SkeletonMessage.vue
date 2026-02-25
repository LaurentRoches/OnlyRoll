<template>
  <div class="rounded-lg p-3 bg-secondary-700 animate-pulse">
    <!-- Header : avatar + pseudo + timestamp -->
    <div class="flex items-center justify-between mb-2">
      <div class="flex items-center gap-2">
        <div class="h-4 w-4 bg-secondary-600 rounded"></div>
        <div class="h-3 bg-secondary-600 rounded w-20"></div>
      </div>
      <div class="h-3 bg-secondary-600 rounded w-10"></div>
    </div>

    <!-- Contenu du message (2 lignes de largeur variable) -->
    <div class="space-y-1.5">
      <div class="h-3 bg-secondary-600 rounded w-full"></div>
      <div class="h-3 bg-secondary-600 rounded w-4/5"></div>
    </div>
  </div>
</template>
