<template>
  <div class="flex min-h-screen bg-parchment">

    <div class="relative hidden w-1/2 lg:flex">

      <img
          src="https://images.unsplash.com/photo-1646221496474-f1980f9b5be5?auto=format&fit=crop&q=80&w=1080"
          alt="Jeu de stratégie Viking"
          class="absolute inset-0 object-cover w-full h-full"
      />

      <div class="absolute inset-0 flex items-center justify-center p-16 bg-black/60">
        <blockquote class="text-4xl font-bold leading-relaxed text-center text-white font-cinzel">
          "La stratégie est le chemin vers le Valhalla."
        </blockquote>
      </div>

    </div>

    <div class="flex items-center justify-center flex-1 p-6 sm:p-12">
      <MoleculesLoginForm />
    </div>

  </div>
</template>

<script setup lang="ts">
// Pas besoin de logique spéciale ici, tout est délégué au MoleculesLoginForm
</script>