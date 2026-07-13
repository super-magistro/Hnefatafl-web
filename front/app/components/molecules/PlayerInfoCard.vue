<template>
  <UCard variant="compact" class="w-full max-w-[760px] shrink-0" :ui="{ body: 'flex items-center justify-between' }">
    <div class="flex items-center gap-3">
      <div 
        class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-lg"
        :class="isCurrentPlayer ? 'bg-primary-700 text-golden-grass-500 ring-1 ring-primary-800' : 'bg-oil-100 text-oil-950 ring-1 ring-oil-200'"
      >
        {{ email ? email.charAt(0).toUpperCase() : '?' }}
      </div>
      <div>
        <p class="font-bold text-oil-950 text-sm">
          {{ isCurrentPlayer ? 'Vous (' + (email ? email.split('@')[0] : 'Viking') + ')' : (email ? email.split('@')[0] : 'Opposant') }}
        </p>
        <p class="text-xs text-pine-cone-500">
          {{ elo }} Elo • {{ role === 'attacker' ? 'Attaque (Noirs)' : 'Défense (Blancs)' }}
        </p>
      </div>
    </div>

    <!-- Indicateur de tour ou de fin de match (seulement si showTurnIndicator est vrai) -->
    <div v-if="showTurnIndicator" class="text-right">
      <span v-if="gameStatus === 'FINISHED'" class="text-xs font-bold text-error-700 bg-error-50 border border-error-100 px-3 py-1.5 rounded-full uppercase tracking-wider flex items-center gap-1">
        <UIcon name="i-lucide-trophy" class="w-3.5 h-3.5" />
        Match terminé
      </span>
      <span v-else-if="isTurnActive" class="text-xs font-extrabold text-white bg-primary-700 px-3 py-1.5 rounded-full uppercase tracking-wider animate-pulse flex items-center gap-1.5 shadow-md">
        <UIcon :name="isCurrentPlayer ? 'i-lucide-swords' : 'i-lucide-hourglass'" class="w-3.5 h-3.5" />
        <span>{{ isCurrentPlayer ? 'À VOUS' : 'TOUR ADVERSE' }}</span>
      </span>
      <span v-else class="text-xs font-semibold text-pine-cone-600 bg-neutral-100 border border-neutral-200 px-3 py-1.5 rounded-full uppercase tracking-wider flex items-center gap-1.5">
        <UIcon :name="isCurrentPlayer ? 'i-lucide-hourglass' : 'i-lucide-swords'" class="w-3.5 h-3.5" />
        <span>{{ isCurrentPlayer ? 'Tour adverse' : 'À son tour' }}</span>
      </span>
    </div>
  </UCard>
</template>

<script setup lang="ts">
defineProps<{
  email?: string
  elo?: number
  role: 'attacker' | 'defender'
  isCurrentPlayer?: boolean
  showTurnIndicator?: boolean
  isTurnActive?: boolean
  gameStatus?: string
}>()
</script>
