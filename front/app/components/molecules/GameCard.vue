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
        <span :class="turnClass" class="text-xs font-semibold uppercase tracking-wider font-['Cinzel',serif]">
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
        <UButton variant="outline" color="neutral" icon="i-lucide-share-2" title="Copier le lien d'invitation" size="md" @click="copyLink" class="shrink-0" />
      </div>
    </template>
  </UCard>
</template>

<script setup lang="ts">
import { computed, defineProps } from 'vue'
import type { Game, User } from '../../game-types'
const props = defineProps<{ game: Game; currentUserId: string | null }>()

// Helper to fetch user data from the global userMap (same logic as in page)
const { getUserByIri } = useUserMap()

const attackerName = computed(() =>
  props.game.attacker ? getUserByIri(props.game.attacker).email.split('@')[0] : 'En attente...'
)
const defenderName = computed(() =>
  props.game.defender ? getUserByIri(props.game.defender).email.split('@')[0] : 'En attente...'
)
const attackerElo = computed(() => (props.game.attacker ? getUserByIri(props.game.attacker).elo : null))
const defenderElo = computed(() => (props.game.defender ? getUserByIri(props.game.defender).elo : null))

const isMyTurn = computed(() => {
  const movesCount = props.game.moves ? props.game.moves.length : 0
  const isAttackerTurn = movesCount % 2 === 0
  return isAttackerTurn ? props.game.attacker === props.currentUserId : props.game.defender === props.currentUserId
})

const turnLabel = computed(() => (isMyTurn.value ? "⚔️ À VOUS DE JOUER" : "🛡️ Attente de l'adversaire"))
const turnClass = computed(() => (isMyTurn.value ? 'text-primary-700 animate-pulse' : 'text-pine-cone-500'))

const cardClass = computed(() => (isMyTurn.value ? 'border-l-primary-600' : 'border-l-neutral-300'))

function copyLink() {
  const link = `${window.location.origin}/games/${props.game.id}`
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(link).catch(fallback)
  } else {
    fallback()
  }
  function fallback() {
    const textarea = document.createElement('textarea')
    textarea.value = link
    textarea.style.position = 'fixed'
    textarea.style.left = '-9999px'
    document.body.appendChild(textarea)
    textarea.select()
    document.execCommand('copy')
    document.body.removeChild(textarea)
  }
}
</script>
