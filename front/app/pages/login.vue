<template>
  <div class="flex w-screen h-screen overflow-hidden">

    <div class="relative hidden lg:flex w-1/2 bg-vert-900">

      <img
          src="https://images.unsplash.com/photo-1646221496474-f1980f9b5be5?auto=format&fit=crop&q=80&w=1080"
          alt="Jeu de stratégie Viking"
          class="absolute inset-0 object-cover w-full h-full opacity-80"
      />

      <div class="absolute inset-0 flex items-center justify-center p-12 bg-linear-to-br from-vert-200/90 to-vert-900/70">
        <blockquote class="font-cinzel text-spring-wood-100 text-[2.5rem] text-center border-l-[5px] border-golden-grass-500 pl-8 drop-shadow-[2px_2px_4px_rgba(0,0,0,0.5)]">
          "La stratégie est le chemin vers le Valhalla."
        </blockquote>
      </div>

    </div>

    <div class="flex items-center justify-center flex-1 p-4 bg-spring-wood-100">
      <MoleculesLoginForm />
    </div>

  </div>
</template>