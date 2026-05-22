<template>
  <UContainer class="py-12 max-w-5xl">
    <h1 class="text-4xl font-bold font-['Cinzel'] mb-10">Table de commandement</h1>
    <div class="grid md:grid-cols-3 gap-6">
      <NuxtLink to="/games">
        <UCard class="hover:ring-2 hover:ring-golden-grass-500 cursor-pointer h-full">
        </UCard>
      </NuxtLink>

      <NuxtLink to="/rules">
        <UCard class="hover:ring-2 hover:ring-golden-grass-500 cursor-pointer h-full">
        </UCard>
      </NuxtLink>

      <NuxtLink to="/settings">
        <UCard class="hover:ring-2 hover:ring-golden-grass-500 cursor-pointer h-full">
        </UCard>
      </NuxtLink>
    </div>
  </UContainer>
</template>