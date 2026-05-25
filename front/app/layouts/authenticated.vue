<template>
  <div class="flex h-screen overflow-hidden bg-spring-wood-50">
    <!-- Barre latérale Viking -->
    <OrganismsAppSidebar />

    <!-- Contenu principal -->
    <main class="flex-1 p-6 overflow-y-auto sm:p-10">
      <slot />
    </main>
  </div>
</template>

<script setup lang="ts">
// Ce layout est partagé par toutes les pages nécessitant une connexion.
// Il intègre la barre latérale Viking pour une navigation cohérente.
</script>
