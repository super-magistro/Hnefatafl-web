<template>
  <UCard class="w-full h-full bg-white border border-neutral-200 shadow-lg flex flex-col justify-between" variant="subtle">
    <template #header>
      <h3 class="text-lg font-bold font-['Cinzel',serif] text-neutral-950 flex items-center gap-2">
        <UIcon name="i-lucide-shield-alert" class="text-warning-500" />
        Commandement de Guerre
      </h3>
    </template>

    <div class="space-y-6 py-6 flex-1 flex flex-col justify-center">
      <!-- Matchmaking -->
      <UButton
        size="xl"
        block
        variant="cta"
        class="group hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 py-5 shadow-md hover:shadow-primary-700/20"
        @click="$emit('matchmaking')"
      >
        <div class="flex flex-col items-center">
          <span class="flex items-center gap-2 text-lg font-bold">
            <UIcon name="i-lucide-swords" class="w-5 h-5 group-hover:animate-bounce" />
            Bataille Rapide
          </span>
          <span class="text-xs text-primary-200 font-normal mt-1 normal-case tracking-wide">
            Affronter un Jarl de votre force (Elo : {{ userElo }})
          </span>
        </div>
      </UButton>

      <!-- Challenge Friend -->
      <UButton
        size="xl"
        block
        class="group hover:scale-[1.02] active:scale-[0.98] transition-all duration-200 py-5 bg-neutral-800 text-white hover:bg-neutral-900 border border-neutral-700 shadow-md"
        @click="$emit('challenge')"
      >
        <div class="flex flex-col items-center">
          <span class="flex items-center gap-2 text-lg font-bold text-warning-400">
            <UIcon name="i-lucide-users" class="w-5 h-5 group-hover:rotate-12 transition-transform" />
            Défier un Ami
          </span>
          <span class="text-xs text-neutral-300 font-normal mt-1 normal-case tracking-wide">
            Envoyer un traité de guerre à un membre du clan
          </span>
        </div>
      </UButton>

      <!-- Play against Bot (Disabled) -->
      <div class="relative group">
        <UButton
          size="xl"
          block
          disabled
          class="py-5 opacity-40 bg-neutral-100 text-neutral-500 border border-neutral-200 cursor-not-allowed w-full"
        >
          <div class="flex flex-col items-center">
            <span class="flex items-center gap-2 text-lg font-bold">
              <UIcon name="i-lucide-cpu" class="w-5 h-5" />
              Entraînement d'Odin
            </span>
            <span class="text-xs font-normal mt-1 normal-case tracking-wide">
              Affronter l'IA (Prochainement)
            </span>
          </div>
        </UButton>
        <div 
          @click="$emit('bot-warning')"
          class="absolute inset-0 cursor-pointer"
        />
      </div>
    </div>

    <template #footer>
      <div class="bg-spring-wood-100 border border-neutral-200/50 p-4 rounded-xl text-xs text-pine-cone-600 flex items-start gap-2.5">
        <UIcon name="i-lucide-scroll" class="w-5 h-5 text-warning-600 mt-0.5 shrink-0" />
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
}>()

defineEmits<{
  (e: 'matchmaking'): void
  (e: 'challenge'): void
  (e: 'bot-warning'): void
}>()
</script>
