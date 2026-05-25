<template>
  <div class="w-full aspect-square bg-oil-950 p-2 rounded-xl border border-oil-800 shadow-xl">
    <div
      class="grid h-full w-full gap-[1px] bg-oil-800 rounded-lg overflow-hidden"
      :style="`grid-template-columns: repeat(${boardSize}, minmax(0, 1fr)); grid-template-rows: repeat(${boardSize}, minmax(0, 1fr))`"
    >
      <div
        v-for="(cell, index) in cells"
        :key="index"
        class="relative aspect-square flex items-center justify-center select-none"
        :class="[
          cell.bgColorClass,
          cell.terrainVal === 1 ? '!bg-oil-800 border-[1px] border-warning-500/20' : '',
          cell.terrainVal === 2 ? '!bg-warning-200/40 border-[1px] border-warning-500/40' : ''
        ]"
      >
        <!-- Runes on empty throne -->
        <span 
          v-if="cell.terrainVal === 1 && cell.pieceVal === 0"
          class="text-warning-600/35 font-extrabold text-[8px] sm:text-[10px]"
        >
          ᛟ
        </span>

        <!-- Pieces -->
        <div
          v-if="cell.pieceVal !== 0"
          class="w-[78%] h-[78%] rounded-full flex items-center justify-center shadow-sm"
          :class="[
            cell.pieceVal === 1 ? 'bg-oil-900 border border-oil-950 text-white' : '',
            cell.pieceVal === 2 ? 'bg-spring-wood-50 border border-spring-wood-400 text-oil-950 shadow-inner' : '',
            cell.pieceVal === 3 ? 'bg-warning-500 border border-warning-600 text-warning-950 font-black ring-[1px] ring-warning-300 scale-105 shadow-md' : ''
          ]"
        >
          <span v-if="boardSize <= 11 && cell.pieceVal === 1" class="text-[6px] sm:text-[8px] opacity-75">⚡</span>
          <span v-if="boardSize <= 11 && cell.pieceVal === 2" class="text-[6px] sm:text-[8px] opacity-75">🛡️</span>
          <span v-if="boardSize <= 11 && cell.pieceVal === 3" class="text-[8px] sm:text-[10px]">👑</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = defineProps<{
  boardSize: number
  initialLayout: number[][]
  terrainLayout: number[][]
}>()

interface Cell {
  y: number
  x: number
  bgColorClass: string
  terrainVal: number
  pieceVal: number
}

const cells = computed<Cell[]>(() => {
  const list: Cell[] = []
  const size = props.boardSize
  
  // Guard against missing/empty layouts during initial load
  const hasLayouts = props.initialLayout && props.initialLayout.length === size &&
                     props.terrainLayout && props.terrainLayout.length === size

  for (let y = 0; y < size; y++) {
    for (let x = 0; x < size; x++) {
      const isEven = (y + x) % 2 === 0
      const terrainVal = hasLayouts ? props.terrainLayout[y][x] : 0
      const pieceVal = hasLayouts ? props.initialLayout[y][x] : 0
      
      list.push({
        y,
        x,
        bgColorClass: isEven ? 'bg-spring-wood-200' : 'bg-spring-wood-300',
        terrainVal,
        pieceVal
      })
    }
  }
  return list
})
</script>
