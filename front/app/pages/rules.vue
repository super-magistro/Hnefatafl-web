<script setup lang="ts">
definePageMeta({
  layout: 'sidebar'
})
</script>

<template>
  <UContainer class="py-12 max-w-4xl">

    <header class="mb-14 text-center">
      <h1 class="font-['Cinzel',serif] text-primary-950 text-4xl sm:text-5xl mb-4 font-bold uppercase tracking-widest">
        Règles du Hnefatafl
      </h1>
      <p class="text-neutral-600 text-lg italic">
        Le jeu de stratégie des Vikings
      </p>
    </header>

    <UCard>
      <div class="flex flex-col gap-12">

        <section>
          <h2 class="font-['Cinzel',serif] text-primary-950 text-2xl mb-6 flex items-center gap-3 uppercase tracking-wider">
            <UIcon name="i-lucide-book-open" class="text-warning-500 w-7 h-7" />
            Introduction
          </h2>
          <p class="text-neutral-800 leading-loose text-base">
            Le Hnefatafl (prononcé "nef-ah-tah-fel") est un ancien jeu de société viking datant de l'âge des Vikings.
            C'est un jeu de stratégie asymétrique où un joueur défend le roi tandis que l'autre tente de le capturer.
          </p>
        </section>

        <USeparator />

        <section>
          <h2 class="font-['Cinzel',serif] text-primary-950 text-2xl mb-8 flex items-center gap-3 uppercase tracking-wider">
            <UIcon name="i-lucide-target" class="text-warning-500 w-7 h-7" />
            Objectif du jeu
          </h2>

          <div class="grid md:grid-cols-2 gap-8">
            <UCard variant="subtle">
              <h3 class="font-['Cinzel',serif] text-primary-800 text-lg mb-4 font-bold flex items-center gap-3 uppercase">
                <UIcon name="i-lucide-shield" class="text-primary-600 w-6 h-6" />
                Défenseurs (Blancs)
              </h3>
              <p class="text-neutral-700 text-base leading-relaxed">Conduire le roi depuis le centre du plateau jusqu'à l'une des quatre cases de coin.</p>
            </UCard>

            <UCard variant="subtle">
              <h3 class="font-['Cinzel',serif] text-error-800 text-lg mb-4 font-bold flex items-center gap-3 uppercase">
                <UIcon name="i-lucide-swords" class="text-error-600 w-6 h-6" />
                Attaquants (Noirs)
              </h3>
              <p class="text-neutral-700 text-base leading-relaxed">Capturer le roi en l'encerclant de tous les côtés avant qu'il n'atteigne un coin.</p>
            </UCard>
          </div>
        </section>

        <USeparator />

        <section>
          <h2 class="font-['Cinzel',serif] text-primary-950 text-2xl mb-8 flex items-center gap-3 uppercase tracking-wider">
            <UIcon name="i-lucide-move" class="text-warning-500 w-7 h-7" />
            Règles de déplacement
          </h2>
          <ul class="flex flex-col gap-6 text-neutral-800 leading-relaxed text-base">
            <li class="flex items-start gap-4">
              <UIcon name="i-lucide-chevron-right" class="text-warning-500 w-6 h-6 shrink-0 mt-0.5" />
              <span>Toutes les pièces se déplacent comme la <strong>Tour aux échecs</strong> (horizontal ou vertical, autant de cases vides que souhaité).</span>
            </li>
            <li class="flex items-start gap-4">
              <UIcon name="i-lucide-chevron-right" class="text-warning-500 w-6 h-6 shrink-0 mt-0.5" />
              <span>Les pièces ne peuvent pas sauter par-dessus d'autres pièces.</span>
            </li>
            <li class="flex items-start gap-4">
              <UIcon name="i-lucide-chevron-right" class="text-warning-500 w-6 h-6 shrink-0 mt-0.5" />
              <span>Seul le roi peut occuper les cases de coin et la case centrale (trône).</span>
            </li>
          </ul>
        </section>

        <USeparator />

        <section>
          <h2 class="font-['Cinzel',serif] text-primary-950 text-2xl mb-8 flex items-center gap-3 uppercase tracking-wider">
            <UIcon name="i-lucide-crosshair" class="text-warning-500 w-7 h-7" />
            Capture de pièces
          </h2>
          <ul class="flex flex-col gap-6 text-neutral-800 leading-relaxed text-base">
            <li class="flex items-start gap-4">
              <UIcon name="i-lucide-skull" class="text-primary-700 w-5 h-5 shrink-0 mt-1" />
              <span>Une pièce est capturée lorsqu'elle est encerclée par <strong>deux pièces ennemies</strong> sur des côtés opposés.</span>
            </li>
            <li class="flex items-start gap-4">
              <UIcon name="i-lucide-skull" class="text-primary-700 w-5 h-5 shrink-0 mt-1" />
              <span>Le roi doit être encerclé sur les <strong>quatre côtés</strong> pour être capturé (ou sur trois côtés si adjacent au trône).</span>
            </li>
            <li class="flex items-start gap-4">
              <UIcon name="i-lucide-skull" class="text-primary-700 w-5 h-5 shrink-0 mt-1" />
              <span>Les cases de coin et le trône peuvent servir de "bloqueur" pour aider à la capture.</span>
            </li>
          </ul>
        </section>

        <USeparator />

        <section>
          <h2 class="font-['Cinzel',serif] text-primary-950 text-2xl mb-8 flex items-center gap-3 uppercase tracking-wider">
            <UIcon name="i-lucide-lightbulb" class="text-warning-500 w-7 h-7" />
            Conseils stratégiques
          </h2>

          <div class="flex flex-col gap-6">
            <UAlert
                icon="i-lucide-shield-check"
                title="Pour les Défenseurs"
                description="Protégez le roi tout en créant des chemins vers les coins. Utilisez vos pièces pour bloquer les attaquants et créer des opportunités de fuite."
                color="primary"
                variant="subtle"
            />

            <UAlert
                icon="i-lucide-swords"
                title="Pour les Attaquants"
                description="Réduisez progressivement l'espace disponible pour le roi. Coordonnez vos pièces pour créer un filet autour du centre du plateau."
                color="error"
                variant="subtle"
            />
          </div>
        </section>

      </div>
    </UCard>
  </UContainer>
</template>