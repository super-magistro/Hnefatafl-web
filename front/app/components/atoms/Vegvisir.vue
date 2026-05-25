<template>
  <div class="relative flex items-center justify-center" :class="[sizeClass]">
    <svg 
      class="w-full h-full text-warning-500 animate-spin-slow" 
      viewBox="0 0 100 100" 
      fill="none" 
      stroke="currentColor" 
      stroke-width="1.5"
    >
      <circle cx="50" cy="50" r="45" stroke-dasharray="6 4" />
      <circle cx="50" cy="50" r="35" stroke-dasharray="1 5" />
      <path d="M 50 10 L 50 90 M 10 50 L 90 50 M 22 22 L 78 78 M 22 78 L 78 22" />
      <circle cx="50" cy="50" r="8" fill="currentColor" />
    </svg>
    <span class="absolute text-warning-500 font-extrabold text-xl animate-pulse">ᛟ</span>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(defineProps<{
  size?: 'sm' | 'md' | 'lg'
}>(), {
  size: 'md'
})

const sizeClass = computed(() => {
  switch (props.size) {
    case 'sm': return 'w-12 h-12'
    case 'lg': return 'w-32 h-32'
    case 'md':
    default: return 'w-24 h-24'
  }
})
</script>

<style scoped>
.animate-spin-slow {
  animation: spin 10s linear infinite;
}
@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}
</style>
