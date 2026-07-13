<template>
  <div class="flex-1 min-h-0 flex items-center justify-center w-full py-1">
    <div 
      class="game-board-container aspect-square bg-neutral-100 p-2 rounded-xl border border-neutral-400 shadow-lg overflow-hidden flex items-center justify-center max-w-full max-h-full"
      :style="`max-width: ${maxSize}px; max-height: ${maxSize}px;`"
    >
      <div 
        class="grid h-full w-full gap-px bg-neutral-300 rounded-lg overflow-hidden"
        :style="`grid-template-columns: repeat(${boardSize}, minmax(0, 1fr)); grid-template-rows: repeat(${boardSize}, minmax(0, 1fr))`"
      >
        <!-- Rendu de chaque case -->
        <div
          v-for="idx in boardSize * boardSize"
          :key="idx"
          @click="onCellClick(Math.floor((idx - 1) / boardSize), (idx - 1) % boardSize)"
          class="relative aspect-square transition-all duration-200 select-none flex items-center justify-center"
          :class="[
            interactive ? 'cursor-pointer' : '',
            // Alternance de couleur du plateau
            (Math.floor((idx - 1) / boardSize) + ((idx - 1) % boardSize)) % 2 === 0
               ? 'bg-neutral-100 hover:bg-neutral-200'
               : 'bg-neutral-200 hover:bg-neutral-300',
            
            // Style spécial Trône (Centre)
            terrainLayout && terrainLayout[Math.floor((idx - 1) / boardSize)]?.[(idx - 1) % boardSize] === 1
              ? 'bg-oil-800! border border-warning-500/20'
              : '',
            
            // Style spécial Coins (Échappatoire)
            terrainLayout && terrainLayout[Math.floor((idx - 1) / boardSize)]?.[(idx - 1) % boardSize] === 2
              ? 'bg-warning-200/40! border border-warning-500/40'
              : ''
          ]"
        >
          <!-- Repère visuel pour le trône (Runes/Croix) -->
          <span 
            v-if="terrainLayout && terrainLayout[Math.floor((idx - 1) / boardSize)]?.[(idx - 1) % boardSize] === 1 && boardState && boardState[Math.floor((idx - 1) / boardSize)]?.[(idx - 1) % boardSize] === 0"
            class="text-golden-grass-500/30 font-bold text-lg select-none"
          >
            ᛟ
          </span>

          <!-- Pions -->
          <div
            v-if="boardState && boardState[Math.floor((idx - 1) / boardSize)]?.[(idx - 1) % boardSize] !== 0"
            class="w-4/5 h-4/5 rounded-full flex items-center justify-center font-bold shadow-md transform transition-all duration-300"
            :class="[
              // Attaquant (1) - Noir / Or Viking
              boardState[Math.floor((idx - 1) / boardSize)]?.[(idx - 1) % boardSize] === 1
                ? 'bg-oil-900 border-2 border-oil-950 text-white'
                : '',
              
              // Défenseur (2) - Beige / Cuir
              boardState[Math.floor((idx - 1) / boardSize)]?.[(idx - 1) % boardSize] === 2
                ? 'bg-neutral-50 border-2 border-neutral-400 text-oil-950 shadow-inner'
                : '',
              
              // Le Roi (3) - Trône / Or étincelant
              boardState[Math.floor((idx - 1) / boardSize)]?.[(idx - 1) % boardSize] === 3
                ? 'bg-golden-grass-500 border-4 border-golden-grass-600 text-golden-grass-950 ring-2 ring-golden-grass-300 font-extrabold shadow-xl'
                : '',
              
              // Sélectionné (Glow et zoom)
              interactive && selectedCell && selectedCell[0] === Math.floor((idx - 1) / boardSize) && selectedCell[1] === (idx - 1) % boardSize
                ? 'scale-110 ring-4! ring-primary-500! shadow-[0_0_15px_#22c55e] animate-pulse z-10'
                : (interactive ? 'hover:scale-105' : '')
            ]"
          >
            <!-- Symbole sur le bouclier (UIcon vectoriels monochromes) -->
            <UIcon v-if="boardState[Math.floor((idx - 1) / boardSize)]?.[(idx - 1) % boardSize] === 1" name="i-lucide-swords" class="w-[55%] h-[55%] text-white" />
            <UIcon v-if="boardState[Math.floor((idx - 1) / boardSize)]?.[(idx - 1) % boardSize] === 2" name="i-lucide-shield" class="w-[55%] h-[55%] text-neutral-600" />
            <UIcon v-if="boardState[Math.floor((idx - 1) / boardSize)]?.[(idx - 1) % boardSize] === 3" name="i-lucide-crown" class="w-[60%] h-[60%] text-golden-grass-950" />
          </div>

          <div
            v-if="interactive && validMoves.some((m: [number, number]) => m[0] === Math.floor((idx - 1) / boardSize) && m[1] === (idx - 1) % boardSize)"
            class="absolute w-4 h-4 rounded-full bg-primary-500/60 ring-2 ring-white/30 z-10"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">

const props = withDefaults(
  defineProps<{
    boardSize: number
    boardState: number[][]
    terrainLayout: number[][]
    validMoves?: [number, number][]
    selectedCell?: [number, number] | null
    interactive?: boolean
    maxSize?: number
  }>(),
  {
    validMoves: () => [],
    selectedCell: null,
    interactive: false,
    maxSize: 760
  }
)


const emit = defineEmits<{
  (e: 'cellClick', y: number, x: number): void
}>()

const marginSize = computed(() => props.interactive ? '330px' : '150px')

const onCellClick = (y: number, x: number) => {
  if (!props.interactive) return
  emit('cellClick', y, x)
}
</script>

<style scoped>
.game-board-container {
  width: min(90vw, 500px);
  height: min(90vw, 500px);
}

@media (min-width: 1024px) {
  .game-board-container {
    width: min(90vw, calc(100vh - v-bind(marginSize)));
    height: min(90vw, calc(100vh - v-bind(marginSize)));
  }
}
</style>
