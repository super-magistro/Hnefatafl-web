// components/molecules/GameCard.vue
<template>
  <UCard :variant="'subtle'" :class="cardClass">
    <template #header>
      <div class="flex items-center justify-between">
        <span class="font-bold text-xs uppercase tracking-wider text-warning-600 bg-warning-50 px-2.5 py-1 rounded-full">
          {{ game.variant }}
        </span>
        <span class="text-xs text-pine-cone-500 flex items-center gap-1 font-semibold">
          <UIcon name="i-lucide-clock" class="w-3.5 h-3.5" />
          {{ game.timeControl }}
        </span>
      </div>
    </template>

    <div class="space-y-4">
      <!-- Players -->
      <div class="space-y-2">
        <div class="flex justify-between items-center text-sm">
          <span class="text-pine-cone-600">Attaquant (Noirs) :</span>
          <span class="font-bold text-neutral-950 flex items-center gap-1.5">
            {{ attackerName }}
            <span v-if="game.attacker" class="text-xs text-pine-cone-500 font-medium">({{ attackerElo }} Elo)</span>
          </span>
        </div>
        <div class="flex justify-between items-center text-sm">
          <span class="text-pine-cone-600">Défenseur (Blancs) :</span>
          <span class="font-bold text-neutral-950 flex items-center gap-1.5">
            {{ defenderName }}
            <span v-if="game.defender" class="text-xs text-pine-cone-500 font-medium">({{ defenderElo }} Elo)</span>
          </span>
        </div>
      </div>

      <USeparator />

      <!-- Turn indicator -->
      <div class="flex justify-between items-center">
        <span :class="turnClass" class="text-xs font-semibold uppercase tracking-wider font-['Cinzel',serif] flex items-center gap-1.5">
          <UIcon :name="isMyTurn ? 'i-lucide-swords' : 'i-lucide-shield'" class="w-3.5 h-3.5" />
          {{ turnLabel }}
        </span>
        <span class="text-xs text-pine-cone-500 font-medium">
          {{ game.moves ? game.moves.length : 0 }} coups
        </span>
      </div>
    </div>

    <template #footer>
      <div class="flex gap-2">
        <UButton :to="`/games/${game.id}`" variant="cta" class="flex-1 justify-center" icon="i-lucide-arrow-right" size="md" />
        <UButton
          :variant="isCopied ? 'invitationCopied' : 'invitation'"
          :icon="isCopied ? 'i-lucide-check' : 'i-lucide-share-2'"
          :title="isCopied ? 'Lien copié !' : 'Copier le lien d\'invitation'"
          size="md"
          @click="copyLink"
          class="shrink-0"
        />
      </div>
    </template>
  </UCard>
</template>

<script setup lang="ts">
import type { Game } from '@/game-types'
import { copyToClipboard } from '@/utils/clipboard'
const props = defineProps<{ game: Game; currentUserId: string | null }>()

const emit = defineEmits<{
  (e: 'copy-link', gameId: number): void
}>()

const isCopied = ref(false)

// Helper pour extraire l'ID utilisateur
const getUserId = (userOrIri: any): number | null => {
  if (!userOrIri) return null
  if (typeof userOrIri === 'number') return userOrIri
  if (typeof userOrIri === 'string') {
    if (userOrIri === '/me' || userOrIri.endsWith('/me') || userOrIri === '/api/me') {
      const { user } = useMe()
      return user.value?.id ? Number(user.value.id) : null
    }
    const match = userOrIri.match(/\/users\/(\d+)/)
    return match ? Number(match[1]) : null
  }
  if (userOrIri.id) return Number(userOrIri.id)
  if (userOrIri['@id']) {
    const match = userOrIri['@id'].match(/\/users\/(\d+)/)
    return match ? Number(match[1]) : null
  }
  return null
}

const currentUserIdVal = computed<number | null>(() => {
  return getUserId(props.currentUserId)
})

// Helper to fetch user data from the global userMap (same logic as in page)
const { getUserByIri } = useUserMap()

const attackerName = computed(() => {
  const id = getUserId(props.game.attacker)
  return id ? (getUserByIri(`/api/users/${id}`).email?.split('@')[0] ?? 'En attente...') : 'En attente...'
})
const defenderName = computed(() => {
  const id = getUserId(props.game.defender)
  return id ? (getUserByIri(`/api/users/${id}`).email?.split('@')[0] ?? 'En attente...') : 'En attente...'
})
const attackerElo = computed(() => {
  const id = getUserId(props.game.attacker)
  return id ? getUserByIri(`/api/users/${id}`).elo : null
})
const defenderElo = computed(() => {
  const id = getUserId(props.game.defender)
  return id ? getUserByIri(`/api/users/${id}`).elo : null
})

const isMyTurn = computed(() => {
  const movesCount = props.game.moves ? props.game.moves.length : 0
  const isAttackerTurn = movesCount % 2 === 0
  const attackerId = getUserId(props.game.attacker)
  const defenderId = getUserId(props.game.defender)
  return isAttackerTurn ? attackerId === currentUserIdVal.value : defenderId === currentUserIdVal.value
})

const turnLabel = computed(() => (isMyTurn.value ? "À VOUS DE JOUER" : "Attente de l'adversaire"))
const turnClass = computed(() => (isMyTurn.value ? 'text-primary-700 animate-pulse' : 'text-pine-cone-500'))

const cardClass = computed(() => (isMyTurn.value ? 'border-l-primary-600' : 'border-l-neutral-300'))

async function copyLink() {
  const link = `${window.location.origin}/games/${props.game.id}`
  const success = await copyToClipboard(link)
  if (success) {
    isCopied.value = true
    setTimeout(() => {
      isCopied.value = false
    }, 3000)
    emit('copy-link', props.game.id)
  }
}
</script>
