<template>
  <UCard class="w-full h-full bg-white border border-neutral-200 shadow-lg flex flex-col justify-between" variant="subtle">
    <template #header>
      <h3 class="text-lg font-bold font-['Cinzel',serif] text-neutral-950 flex items-center gap-2">
        <UIcon name="i-lucide-shield-alert" class="text-neutral-500" />
        Commandement de Guerre
      </h3>
    </template>

    <div class="space-y-4 py-4 flex-1 flex flex-col justify-center">
      <!-- Matchmaking -->
      <UButton
        size="xl"
        block
        variant="cta"
        class="group hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 py-5 shadow-md hover:shadow-neutral-700/20 border-none! ring-0!"
        @click="$emit('matchmaking')"
      >
        <div class="flex flex-col items-center">
          <span class="flex items-center gap-2 text-lg font-bold">
            <UIcon name="i-lucide-swords" class="w-5 h-5 group-hover:animate-bounce" />
            Bataille Rapide
          </span>
          <span class="text-xs text-neutral-200 font-normal mt-1 normal-case tracking-wide">
            Affronter un joueur de votre force (Elo : {{ userElo }})
          </span>
        </div>
      </UButton>

      <!-- Challenge Friend -->
      <UButton
        size="xl"
        block
        class="group hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 py-5 bg-vert-500 text-white hover:bg-vert-600 border-none! ring-0! shadow-md"
        @click="$emit('challenge')"
      >
        <div class="flex flex-col items-center">
          <span class="flex items-center gap-2 text-lg font-bold text-white">
            <UIcon name="i-lucide-users" class="w-5 h-5 group-hover:rotate-12 transition-transform text-vert-200" />
            Défier un Ami
          </span>
          <span class="text-xs text-vert-100 font-normal mt-1 normal-case tracking-wide">
            Envoyer un traité de guerre à un membre du clan
          </span>
        </div>
      </UButton>

      <!-- Séparateur Bot -->
      <div class="flex items-center gap-2 pt-1">
        <div class="flex-1 h-px bg-neutral-200" />
        <span class="text-xs text-neutral-400 font-medium uppercase tracking-widest">Entraînement</span>
        <div class="flex-1 h-px bg-neutral-200" />
      </div>

      <!-- Bot Novice (Facile) -->
      <UButton
        size="xl"
        block
        class="group hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 py-4 bg-vert-200 text-vert-900 hover:bg-vert-300 border-none! ring-0! shadow-md w-full"
        @click="$emit('play-easy-bot')"
      >
        <div class="flex flex-col items-center">
          <span class="flex items-center gap-2 text-base font-bold text-vert-900">
            <UIcon name="i-lucide-shield" class="w-5 h-5 group-hover:animate-pulse text-vert-700" />
            Novice d'Yggdrasil
          </span>
          <span class="text-xs text-vert-700 font-normal mt-0.5 normal-case tracking-wide">
            Bot débutant — idéal pour apprendre (Elo : {{ easyBotElo }})
          </span>
        </div>
      </UButton>

      <!-- Bot Odin (Difficile) -->
      <UButton
        size="xl"
        block
        class="group hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 py-4 bg-vert-700 text-white hover:bg-vert-800 border-none! ring-0! shadow-md w-full"
        @click="$emit('play-bot')"
      >
        <div class="flex flex-col items-center">
          <span class="flex items-center gap-2 text-base font-bold text-white">
            <UIcon name="i-lucide-cpu" class="w-5 h-5 group-hover:rotate-12 transition-transform text-vert-200" />
            Odin — L'Œil du Corbeau
          </span>
          <span class="text-xs text-vert-300 font-normal mt-0.5 normal-case tracking-wide">
            Bot Minimax redoutable — pour guerriers aguerris (Elo : {{ botElo }})
          </span>
        </div>
      </UButton>
    </div>

    <template #footer>
      <div class="bg-neutral-100 border border-neutral-200/50 p-4 rounded-xl text-xs text-neutral-600 flex items-start gap-2.5">
        <UIcon name="i-lucide-scroll" class="w-5 h-5 text-neutral-600 mt-0.5 shrink-0" />
        <p class="leading-relaxed">
          Le Hnefatafl est asymétrique. Les attaquants (⚡) encerclent le château et tentent de terrasser le chef. Le Roi (👑) et sa garde (🛡️) doivent s'échapper vers des refuges.
        </p>
      </div>
    </template>
  </UCard>
</template>

<script setup lang="ts">
defineProps<{
  userElo: number
  botElo?: number
  easyBotElo?: number
}>()

defineEmits<{
  (e: 'matchmaking'): void
  (e: 'challenge'): void
  (e: 'play-bot'): void
  (e: 'play-easy-bot'): void
}>()
</script>
