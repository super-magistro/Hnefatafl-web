<script setup lang="ts">
import { onMounted } from 'vue'

const isCollapsed = useState('sidebarCollapsed', () => false)

onMounted(() => {
  // Masque la sidebar par défaut sur les petits écrans (tablettes et mobiles)
  if (window.innerWidth < 1024) {
    isCollapsed.value = true
  }
})
</script>

<template>
  <div class="flex h-screen overflow-hidden bg-neutral-100 relative">
    <!-- Conteneur Sidebar réactif avec largeur animée (Absolute en overlay sur mobile/tablette, Relative dans le flux sur desktop) -->
    <div 
      class="absolute lg:relative flex shrink-0 h-full transition-all duration-300 z-40" 
      :class="isCollapsed ? 'w-0' : 'w-64 shadow-2xl lg:shadow-none bg-vert-800'"
    >
      <!-- Sous-conteneur overflow-hidden pour cacher la sidebar lors du repliage -->
      <div class="w-full h-full overflow-hidden">
        <OrganismsAppSidebar 
          v-show="!isCollapsed" 
          class="w-64 h-full"
        />
      </div>
      
      <!-- Bouton Toggle Onglet (collé au bord droit du conteneur de la sidebar, hors de l'overflow-hidden) -->
      <UButton
        :icon="isCollapsed ? 'i-lucide-chevron-right' : 'i-lucide-chevron-left'"
        variant="outline"
        color="neutral"
        class="absolute top-1/2 left-full -translate-y-1/2 z-50 bg-white hover:bg-neutral-100 shadow-md border border-neutral-300 rounded-r-xl rounded-l-none h-12 w-6 flex items-center justify-center p-0 cursor-pointer"
        @click="isCollapsed = !isCollapsed"
      />
    </div>

    <!-- Contenu Principal -->
    <main class="flex-1 overflow-y-auto">
      <slot />
    </main>
  </div>
</template>
