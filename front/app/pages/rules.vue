<script setup lang="ts">
definePageMeta({
  layout: 'sidebar'
})
</script>

<template>
  <UContainer class="py-8 max-w-4xl">

    <header class="mb-8 text-center">
      <h1 class="font-['Cinzel',serif] text-vert-950 text-3xl sm:text-4xl mb-2 leading-tight font-bold">
        Règles du Hnefatafl
      </h1>
      <p class="text-spring-wood-600 text-base italic">
        Le jeu de stratégie des Vikings
      </p>
    </header>

    <UCard :ui="{ root: 'bg-white shadow-sm ring-1 ring-vert-100', body: 'p-6 sm:p-8 space-y-8' }">

      <section>
        <h2 class="font-['Cinzel',serif] text-vert-950 text-2xl mb-3 flex items-center gap-2">
          <UIcon name="i-lucide-book-open" class="text-golden-grass-500 w-6 h-6" />
          Introduction
        </h2>
        <p class="text-spring-wood-800 leading-relaxed text-base">
          Le Hnefatafl (prononcé "nef-ah-tah-fel") est un ancien jeu de société viking datant de l'âge des Vikings.
          C'est un jeu de stratégie asymétrique où un joueur défend le roi tandis que l'autre tente de le capturer.
        </p>
      </section>

      <USeparator class="border-spring-wood-200" />

      <section>
        <h2 class="font-['Cinzel',serif] text-vert-950 text-2xl mb-4 flex items-center gap-2">
          <UIcon name="i-lucide-target" class="text-golden-grass-500 w-6 h-6" />
          Objectif du jeu
        </h2>

        <div class="grid md:grid-cols-2 gap-4">
          <UCard :ui="{ root: 'bg-spring-wood-50 ring-1 ring-spring-wood-200 shadow-none', body: 'p-4' }">
            <h3 class="font-['Cinzel',serif] text-vert-800 text-lg mb-2 font-bold flex items-center gap-2">
              <UIcon name="i-lucide-shield" class="text-vert-600 w-5 h-5" />
              Défenseurs (Blancs)
            </h3>
            <p class="text-spring-wood-700 text-sm leading-relaxed">Conduire le roi depuis le centre du plateau jusqu'à l'une des quatre cases de coin.</p>
          </UCard>

          <UCard :ui="{ root: 'bg-spring-wood-50 ring-1 ring-spring-wood-200 shadow-none', body: 'p-4' }">
            <h3 class="font-['Cinzel',serif] text-vert-800 text-lg mb-2 font-bold flex items-center gap-2">
              <UIcon name="i-lucide-swords" class="text-red-700 w-5 h-5" />
              Attaquants (Noirs)
            </h3>
            <p class="text-spring-wood-700 text-sm leading-relaxed">Capturer le roi en l'encerclant de tous les côtés avant qu'il n'atteigne un coin.</p>
          </UCard>
        </div>
      </section>

      <USeparator class="border-spring-wood-200" />

      <section>
        <h2 class="font-['Cinzel',serif] text-vert-950 text-2xl mb-4 flex items-center gap-2">
          <UIcon name="i-lucide-move" class="text-golden-grass-500 w-6 h-6" />
          Règles de déplacement
        </h2>
        <ul class="space-y-3 text-spring-wood-800 leading-relaxed text-base">
          <li class="flex items-start gap-2">
            <UIcon name="i-lucide-chevron-right" class="text-golden-grass-500 w-5 h-5 shrink-0 mt-0.5" />
            <span>Toutes les pièces se déplacent comme la <strong>Tour aux échecs</strong> (horizontal ou vertical, autant de cases vides que souhaité).</span>
          </li>
          <li class="flex items-start gap-2">
            <UIcon name="i-lucide-chevron-right" class="text-golden-grass-500 w-5 h-5 shrink-0 mt-0.5" />
            <span>Les pièces ne peuvent pas sauter par-dessus d'autres pièces.</span>
          </li>
          <li class="flex items-start gap-2">
            <UIcon name="i-lucide-chevron-right" class="text-golden-grass-500 w-5 h-5 shrink-0 mt-0.5" />
            <span>Seul le roi peut occuper les cases de coin et la case centrale (trône).</span>
          </li>
        </ul>
      </section>

      <USeparator class="border-spring-wood-200" />

      <section>
        <h2 class="font-['Cinzel',serif] text-vert-950 text-2xl mb-4 flex items-center gap-2">
          <UIcon name="i-lucide-crosshair" class="text-golden-grass-500 w-6 h-6" />
          Capture de pièces
        </h2>
        <ul class="space-y-3 text-spring-wood-800 leading-relaxed text-base">
          <li class="flex items-start gap-2">
            <UIcon name="i-lucide-skull" class="text-vert-700 w-4 h-4 shrink-0 mt-1" />
            <span>Une pièce est capturée lorsqu'elle est encerclée par <strong>deux pièces ennemies</strong> sur des côtés opposés.</span>
          </li>
          <li class="flex items-start gap-2">
            <UIcon name="i-lucide-skull" class="text-vert-700 w-4 h-4 shrink-0 mt-1" />
            <span>Le roi doit être encerclé sur les <strong>quatre côtés</strong> pour être capturé (ou sur trois côtés si adjacent au trône).</span>
          </li>
          <li class="flex items-start gap-2">
            <UIcon name="i-lucide-skull" class="text-vert-700 w-4 h-4 shrink-0 mt-1" />
            <span>Les cases de coin et le trône peuvent servir de "bloqueur" pour aider à la capture.</span>
          </li>
        </ul>
      </section>

      <USeparator class="border-spring-wood-200" />

      <section>
        <h2 class="font-['Cinzel',serif] text-vert-950 text-2xl mb-4 flex items-center gap-2">
          <UIcon name="i-lucide-lightbulb" class="text-golden-grass-500 w-6 h-6" />
          Conseils stratégiques
        </h2>

        <div class="space-y-3">
          <UAlert
              icon="i-lucide-shield-check"
              title="Pour les Défenseurs"
              description="Protégez le roi tout en créant des chemins vers les coins. Utilisez vos pièces pour bloquer les attaquants et créer des opportunités de fuite."
              :ui="{ root: 'bg-vert-50 ring-vert-200 py-3', icon: 'text-vert-700 w-5 h-5', title: 'text-vert-900 font-bold text-sm', description: 'text-vert-800 text-sm mt-1' }"
          />

          <UAlert
              icon="i-lucide-swords"
              title="Pour les Attaquants"
              description="Réduisez progressivement l'espace disponible pour le roi. Coordonnez vos pièces pour créer un filet autour du centre du plateau."
              :ui="{ root: 'bg-red-50 ring-red-200 py-3', icon: 'text-red-700 w-5 h-5', title: 'text-red-900 font-bold text-sm', description: 'text-red-800 text-sm mt-1' }"
          />
        </div>
      </section>

    </UCard>
  </UContainer>
</template>