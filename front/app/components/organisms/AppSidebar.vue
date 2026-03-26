<template>
  <USidebar collapsible="none" class="h-full">

    <template #header>
      <div class="flex items-center gap-3">
        <AtomsIconTafl class="w-10 h-10" />
        <div class="flex flex-col">
          <span class="font-bold font-['Cinzel',serif] text-white">HNEFATAFL</span>
          <span class="text-xs font-semibold text-golden-grass-500">Online</span>
        </div>
      </div>
    </template>

    <template #default>
      <UNavigationMenu
          :items="navItems"
          orientation="vertical"
      />
    </template>

    <template #footer>
      <div class="flex flex-col gap-4">

        <UUser
            name="Ragnar_Loth"
            description="Jarl"
            class="px-2"
            :avatar="{
              alt: 'R',
              size: 'md',
              class: 'bg-vert-900 text-golden-grass-500 ring-1 ring-vert-700 font-bold'
            }"
            :ui="{
              name: 'text-sm font-bold text-white',
              description: 'text-xs text-spring-wood-300'
            }"
        />

        <UButton
            icon="i-lucide-log-out"
            label="Déconnexion"
            variant="ghost"
            color="neutral"
            class="justify-start text-spring-wood-200 hover:text-white hover:bg-vert-700/50 p-2"
            @click="handleLogout"
        />

      </div>
    </template>

  </USidebar>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { NavigationMenuItem } from '@nuxt/ui'

const { logout } = useAuth()
const router = useRouter()
const route = useRoute()

const navItems = computed<NavigationMenuItem[]>(() => [
  {
    label: 'Bataille',
    icon: 'i-lucide-swords',
    to: '/games',
    active: route.path.startsWith('/games')
  },
  {
    label: 'Règles',
    icon: 'i-lucide-book-open',
    to: '/rules',
    active: route.path.startsWith('/rules')
  },
  {
    label: 'Paramètres',
    icon: 'i-lucide-settings',
    to: '/settings',
    active: route.path.startsWith('/settings')
  }
])

const handleLogout = () => {
  logout()
  router.push('/login')
}
</script>