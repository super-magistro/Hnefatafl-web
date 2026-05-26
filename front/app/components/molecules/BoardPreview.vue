<template>
  <div class="w-full aspect-square bg-neutral-100 p-2 rounded-xl border border-neutral-400 shadow-lg">
    <div class="grid h-full w-full gap-[1px] bg-neutral-300 rounded-lg overflow-hidden" :style="`grid-template-columns: repeat(${boardSize}, minmax(0, 1fr)); grid-template-rows: repeat(${boardSize}, minmax(0, 1fr))`">
      <BoardCell
        v-for="(cell, index) in cells"
        :key="index"
        :cell="cell"
        :boardSize="boardSize"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import BoardCell from '@/components/atoms/BoardCell.vue'

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
        bgColorClass: isEven ? 'bg-neutral-100' : 'bg-neutral-200',
        terrainVal,
        pieceVal
      })
    }
  }
  return list
})
</script>
