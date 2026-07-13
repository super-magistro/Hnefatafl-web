<template>
  <UCard variant="subtle" class="shadow-sm flex flex-col h-auto lg:h-full flex-grow lg:flex-1 min-h-0 overflow-hidden" :ui="{ body: 'flex-1 flex flex-col min-h-0 p-4 overflow-hidden', footer: 'p-4 pt-2 shrink-0' }">
    <template #header>
      <h3 class="text-base font-bold font-['Cinzel',serif] text-oil-950 flex items-center gap-2">
        <UIcon name="i-lucide-scroll" class="text-golden-grass-600" />
        Rapport de Guerre
      </h3>
    </template>

    <div class="flex flex-col flex-1 min-h-0 space-y-4 overflow-hidden">
      <div class="flex justify-between items-center text-sm shrink-0">
        <span class="text-pine-cone-600">Statut :</span>
        <span class="font-bold uppercase tracking-wider text-xs" :class="game.status === 'PLAYING' ? 'text-primary-700' : 'text-error-600'">
          {{ game.status === 'PLAYING' ? 'En cours' : game.status === 'PENDING' ? 'En attente' : 'Terminée' }}
        </span>
      </div>

      <!-- Affichage du gagnant si fini -->
      <div v-if="game.status === 'FINISHED'" class="bg-primary-50 border border-primary-200 p-3 rounded-xl text-center space-y-1.5 shrink-0">
        <UIcon name="i-lucide-crown" class="w-8 h-8 text-golden-grass-500 mx-auto animate-bounce" />
        <h4 class="font-bold font-['Cinzel',serif] text-primary-900 text-sm">Victoire de</h4>
        <p class="text-base font-extrabold text-primary-950">
          {{ winnerName }}
        </p>
      </div>

      <div class="flex justify-between items-center text-sm shrink-0">
        <span class="text-pine-cone-600">Cadence :</span>
        <span class="font-bold text-oil-950 text-xs">{{ game.timeControl }}</span>
      </div>

      <!-- Liste des coups -->
      <div class="space-y-1 flex-1 min-h-0 flex flex-col overflow-hidden">
        <span class="text-xs font-bold text-pine-cone-600 uppercase tracking-wider shrink-0">Journal de combat :</span>
        <div class="h-[180px] lg:h-full flex-1 min-h-0 overflow-y-auto border border-neutral-200 rounded-xl bg-white p-3 space-y-1.5 text-sm font-medium">
          <div v-if="!game.moves || game.moves.length === 0" class="text-xs text-pine-cone-400 text-center py-8">
            Aucun mouvement n'a encore été tenté. Que la bataille commence !
          </div>
          <div 
            v-for="(move, idx) in game.moves" 
            :key="idx"
            class="flex justify-between items-center py-1 px-2 rounded hover:bg-neutral-50"
          >
            <span class="text-xs text-pine-cone-400 font-bold">#{{ idx + 1 }}</span>
            <span class="font-bold text-oil-900 text-xs">{{ getMoveLabel(move) }}</span>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="space-y-2">
        <UButton
            v-if="game.status === 'PLAYING' || game.status === 'PENDING'"
            block
            :variant="isLinkCopied ? 'invitationCopied' : 'invitation'"
            :icon="isLinkCopied ? 'i-lucide-check' : 'i-lucide-share-2'"
            @click="$emit('copyLink')"
        >
          {{ isLinkCopied ? 'Lien copié !' : "Copier le lien d'invitation" }}
        </UButton>
        <div class="flex gap-2">
          <UButton
              v-if="game.status === 'PLAYING'"
              variant="resign"
              class="flex-1"
              icon="i-lucide-flag"
              :loading="isActionLoading"
              @click="$emit('resign')"
          >
            Abandonner
          </UButton>
          <UButton
              to="/games"
              variant="secondary"
              class="flex-1"
              icon="i-lucide-arrow-left"
          >
            Retour
          </UButton>
        </div>
      </div>
    </template>
  </UCard>
</template>

<script setup lang="ts">
const props = defineProps<{
  game: any
  winnerName: string
  isLinkCopied: boolean
  isActionLoading: boolean
}>()

defineEmits<{
  (e: 'copyLink'): void
  (e: 'resign'): void
}>()

const getMoveLabel = (move: any) => {
  if (!move) return ''
  if (typeof move === 'string') return move
  if (move.notation) {
    // Traduire le message si possible ou le retourner
    return move.notation.replace('Move from', 'Déplacement de').replace('to', 'vers')
  }
  if (move.from && move.to) {
    return `Déplacement de [${move.from.join(',')}] vers [${move.to.join(',')}]`
  }
  return 'Coup joué'
}
</script>
