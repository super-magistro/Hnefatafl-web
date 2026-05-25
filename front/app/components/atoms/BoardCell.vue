<template>
  <div
    class="relative aspect-square flex items-center justify-center select-none"
    :class="[cell.bgColorClass, terrainClass]"
  >
    <!-- Rune on empty throne -->
    <span
      v-if="cell.terrainVal === 1 && cell.pieceVal === 0"
      class="text-warning-600/35 font-extrabold text-[8px] sm:text-[10px]"
    >
      ᛟ
    </span>

    <!-- Piece -->
    <div
      v-if="cell.pieceVal !== 0"
      class="w-[78%] h-[78%] rounded-full flex items-center justify-center shadow-sm"
      :class="pieceClass"
    >
      <span v-if="boardSize <= 11 && cell.pieceVal === 1" class="text-[6px] sm:text-[8px] opacity-75">⚡</span>
      <span v-if="boardSize <= 11 && cell.pieceVal === 2" class="text-[6px] sm:text-[8px] opacity-75">🛡️</span>
      <span v-if="boardSize <= 11 && cell.pieceVal === 3" class="text-[8px] sm:text-[10px]">👑</span>
    </div>
  </div>
</template>

<script setup lang="ts">
interface Cell {
  bgColorClass: string;
  terrainVal: number;
  pieceVal: number;
}

const props = defineProps<{
  cell: Cell;
  boardSize: number;
}>();

const terrainClass = computed(() => {
  if (props.cell.terrainVal === 1) return '!bg-oil-800 border-[1px] border-warning-500/20';
  if (props.cell.terrainVal === 2) return '!bg-warning-200/40 border-[1px] border-warning-500/40';
  return '';
});

const pieceClass = computed(() => {
  const val = props.cell.pieceVal;
  return [
    val === 1 ? 'bg-oil-900 border border-oil-950 text-white' : '',
    val === 2 ? 'bg-spring-wood-50 border border-spring-wood-400 text-oil-950 shadow-inner' : '',
    val === 3 ? 'bg-warning-500 border border-warning-600 text-warning-950 font-black ring-[1px] ring-warning-300 scale-105 shadow-md' : ''
  ];
});
</script>
