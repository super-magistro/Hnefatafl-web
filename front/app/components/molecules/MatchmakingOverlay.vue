<template>
  <div 
    class="h-full flex flex-col items-center justify-center p-8 border-2 border-dashed border-warning-500/30 rounded-2xl bg-warning-500/5 text-center min-h-[360px] shadow-inner animate-pulse-slow"
  >
    <AtomsVegvisir size="lg" class="mb-6" />

    <h3 class="text-xl font-bold font-['Cinzel',serif] text-neutral-900 mb-2">
      Entrée dans la taverne de combat...
    </h3>
    <p class="text-sm text-pine-cone-600 max-w-xs mb-4">
      Nous recherchons un joueur digne d'affronter vos troupes dans l'arène.
    </p>

    <div class="bg-neutral-200/80 border border-neutral-300 px-4 py-2 rounded-full text-xs font-bold text-neutral-950 inline-block mb-6 shadow-sm">
      Portée Elo : {{ userElo - eloRange }} à {{ userElo + eloRange }}
    </div>

    <div class="text-xs text-pine-cone-400 font-semibold mb-6">
      Rapprochement en cours... ({{ elapsed }}s)
    </div>

    <UButton
      variant="outline"
      color="error"
      icon="i-lucide-x"
      @click="$emit('cancel')"
      class="shadow-sm"
    >
      Quitter la taverne
    </UButton>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  userElo: number
  eloRange: number
  elapsed: number
}>()

defineEmits<{
  (e: 'cancel'): void
}>()
</script>
