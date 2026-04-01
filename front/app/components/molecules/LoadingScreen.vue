<script setup lang="ts">
import quotes from '~/assets/data/quotes.json'

// useState calcule la citation AVANT l'affichage de la page.
// 'viking-quote' est juste un nom unique pour que Nuxt s'en souvienne.
const randomQuote = useState('viking-quote', () => {
  const randomIndex = Math.floor(Math.random() * quotes.length)
  return quotes[randomIndex] || { text: '', author: '' }
})
</script>

<template>
  <div class="h-screen flex flex-col items-center justify-center bg-neutral-100 p-6 text-center">

    <UIcon name="i-lucide-loader-2" class="w-12 h-12 animate-spin text-primary-700 mb-8" />

    <div class="max-w-lg space-y-4 animate-[pulse_3s_ease-in-out_infinite]">
      <p class="font-['Cinzel',serif] text-2xl text-primary-900 italic leading-relaxed">
        "{{ randomQuote.text }}"
      </p>

      <p class="text-neutral-500 text-sm font-bold uppercase tracking-widest">
        — {{ randomQuote.author }}
      </p>
    </div>

  </div>
</template>