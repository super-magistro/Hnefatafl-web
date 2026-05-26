<template>
  <UCard class="w-full h-full bg-gradient-to-br from-neutral-100 to-neutral-50 border border-neutral-200 shadow-lg">
    <template #header>
      <div class="flex items-center justify-between">
        <UButton 
          icon="i-lucide-chevron-left" 
          variant="outline" 
          color="neutral" 
          @click="$emit('prev')" 
          :disabled="disableNav"
          class="hover:bg-neutral-200"
        />
        <div class="text-center">
          <h2 class="text-2xl font-bold font-['Cinzel',serif] text-neutral-950 tracking-wider">
            {{ board.name }}
          </h2>
          <p class="text-xs text-primary-700 font-bold uppercase tracking-widest mt-1">
            Plateau de {{ board.boardSize }}x{{ board.boardSize }}
          </p>
        </div>
        <UButton 
          icon="i-lucide-chevron-right" 
          variant="outline" 
          color="neutral" 
          @click="$emit('next')" 
          :disabled="disableNav"
          class="hover:bg-neutral-200"
        />
      </div>
    </template>

    <div class="space-y-6">
      <div class="flex justify-center">
        <MoleculesBoardPreview
          class="max-w-[340px]"
          :board-size="board.boardSize"
          :initial-layout="board.initialLayout"
          :terrain-layout="board.terrainLayout"
        />
      </div>

      <div class="space-y-4">
        <p class="text-sm italic text-pine-cone-600 leading-relaxed text-center px-4">
          "{{ board.description }}"
        </p>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
          <div class="bg-neutral-200/40 border border-neutral-200 p-2.5 rounded-xl text-center">
            <span class="block text-[10px] font-bold text-pine-cone-500 uppercase tracking-wider">Victoire</span>
            <span class="text-xs font-black text-neutral-900 flex items-center justify-center gap-1 mt-1">
              <UIcon name="i-lucide-crown" class="text-warning-500 w-3.5 h-3.5" />
              {{ board.rulesSummary?.winCondition }}
            </span>
          </div>
          <div class="bg-neutral-200/40 border border-neutral-200 p-2.5 rounded-xl text-center">
            <span class="block text-[10px] font-bold text-pine-cone-500 uppercase tracking-wider">Capture Roi</span>
            <span class="text-xs font-black text-neutral-900 flex items-center justify-center gap-1 mt-1">
              <UIcon name="i-lucide-swords" class="text-warning-500 w-3.5 h-3.5" />
              {{ board.rulesSummary?.kingCapture }}
            </span>
          </div>
          <div class="bg-neutral-200/40 border border-neutral-200 p-2.5 rounded-xl text-center">
            <span class="block text-[10px] font-bold text-pine-cone-500 uppercase tracking-wider">Armement Roi</span>
            <span class="text-xs font-black text-neutral-900 flex items-center justify-center gap-1 mt-1">
              <UIcon name="i-lucide-shield" class="text-warning-500 w-3.5 h-3.5" />
              {{ board.rulesSummary?.kingWeapon }}
            </span>
          </div>
          <div class="bg-neutral-200/40 border border-neutral-200 p-2.5 rounded-xl text-center">
            <span class="block text-[10px] font-bold text-pine-cone-500 uppercase tracking-wider">Hostilité Trône</span>
            <span class="text-xs font-black text-neutral-900 flex items-center justify-center gap-1 mt-1">
              <UIcon name="i-lucide-alert-triangle" class="text-warning-500 w-3.5 h-3.5" />
              {{ board.rulesSummary?.throneHostility }}
            </span>
          </div>
        </div>
      </div>
    </div>
  </UCard>
</template>

<script setup lang="ts">
defineProps<{
  board: {
    name: string
    boardSize: number
    initialLayout: number[][]
    terrainLayout: number[][]
    description: string
    rulesSummary: {
      winCondition: string
      kingCapture: string
      kingWeapon: string
      throneHostility: string
    }
  }
  disableNav: boolean
}>()

defineEmits<{
  (e: 'prev'): void
  (e: 'next'): void
}>()
</script>
