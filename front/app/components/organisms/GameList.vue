// organisms/GameList.vue
<template>
  <div>
    <slot name="empty" v-if="games.length === 0" />
    <div v-else class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
      <GameCard
        v-for="game in games"
        :key="game.id"
        :game="game"
        :currentUserId="currentUserId"
        @join="emit('join', game)"
        @copy-link="emit('copy-link', $event)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Game } from '@/game-types'
import GameCard from '@/components/molecules/GameCard.vue'

const props = defineProps<{ games: Game[]; currentUserId: string | null }>()
const emit = defineEmits<{ (e: 'join', game: Game): void; (e: 'copy-link', gameId: number): void }>()
</script>
