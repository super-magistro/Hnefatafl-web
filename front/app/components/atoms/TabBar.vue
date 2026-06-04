// atoms/TabBar.vue
<template>
  <div class="flex p-1 rounded-xl bg-neutral-200/50 max-w-md border border-neutral-200">
    <UButton
      v-for="tab in tabs"
      :key="tab.key"
      :variant="activeTab === tab.key ? 'tabActive' : 'tabInactive'"
      class="flex-1 justify-center py-2 text-xs font-bold transition-all duration-200"
      @click="emit('update:activeTab', tab.key)"
    >
      {{ tab.label }} ({{ tab.count }})
    </UButton>
  </div>
</template>

<script setup lang="ts">
import { defineProps, defineEmits } from 'vue'

interface TabItem {
  key: 'active' | 'pending' | 'finished'
  label: string
  count: number
}

const props = defineProps<{ tabs: TabItem[]; activeTab: string }>()
const emit = defineEmits<{ (e: 'update:activeTab', value: string): void }>()
</script>
