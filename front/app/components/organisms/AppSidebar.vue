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
            :name="user?.email ? user.email.split('@')[0] : 'Chargement...'"
            :description="user?.elo ? `${user.elo} Elo` : '...'"
            class="px-2"
            :avatar="{
              alt: user?.email ? user.email.charAt(0).toUpperCase() : '?',
              size: 'md',
              class: 'bg-primary-800 border-none text-warning-500 ring-1 ring-primary-700 font-bold uppercase'
            }"
            :ui="{
              name: 'text-sm font-bold text-white',
              description: 'text-xs text-neutral-300'
            }"
        />

        <UButton
            icon="i-lucide-log-out"
            label="Déconnexion"
            variant="sidebarButton"
            @click="handleLogout"
        />

      </div>
    </template>

  </USidebar>
</template>

<script setup lang="ts">
import { computed, onMounted } from 'vue'
import type { NavigationMenuItem } from '@nuxt/ui'

const { logout } = useAuth()
const { user, fetchMe } = useMe()
const router = useRouter()
const route = useRoute()

onMounted(async () => {
  if (!user.value) {
    await fetchMe()
  }
})

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
