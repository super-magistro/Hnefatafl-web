<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  layout: 'sidebar'
})

const { apiFetch } = useApi()

const gameBoards = ref<any[]>([])
const selectedBoardIndex = useState('selectedBoardIndex', () => 0)
const isLoading = ref(true)

const selectedBoard = computed(() => {
  if (gameBoards.value.length === 0) return null
  return gameBoards.value[selectedBoardIndex.value] || gameBoards.value[0] || null
})

// Traduction dynamique cohérente avec le Hall des Batailles
const rulesSummary = computed(() => {
  if (!selectedBoard.value) {
    return {
      winCondition: '...',
      kingCapture: '...',
      kingWeapon: '...',
      throneHostility: '...'
    }
  }
  return translateBoardRules(selectedBoard.value.rules)
})

// Accès sécurisé à l'API
onMounted(async () => {
  isLoading.value = true
  try {
    const data = await apiFetch('/game_boards')
    const boards = data['hydra:member'] || data['member'] || []
    if (boards.length > 0) {
      gameBoards.value = boards.map((b: any) => {
        const cleanName = b.name.split(' (')[0]
        return {
          ...b,
          name: cleanName
        }
      })
    }
  } catch (err) {
    console.error("Impossible de charger les variantes depuis la base de données :", err)
  } finally {
    isLoading.value = false
  }
})

// Navigation par flèches
const prevVariant = () => {
  if (gameBoards.value.length <= 1) return
  selectedBoardIndex.value = (selectedBoardIndex.value - 1 + gameBoards.value.length) % gameBoards.value.length
}

const nextVariant = () => {
  if (gameBoards.value.length <= 1) return
  selectedBoardIndex.value = (selectedBoardIndex.value + 1) % gameBoards.value.length
}
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

        <!-- SECTION VARIANTES DE PLATEAU DYNAMIQUE DEPUIS L'API -->
        <section>
          <h2 class="font-['Cinzel',serif] text-primary-950 text-2xl mb-4 flex items-center gap-3 uppercase tracking-wider">
            <UIcon name="i-lucide-grid" class="text-warning-500 w-7 h-7" />
            Variantes & Règles de plateau
          </h2>
          <p class="text-sm text-neutral-500 mb-8">
            Faites défiler les variantes avec les flèches pour explorer leurs règles spécifiques. Votre choix est synchronisé avec le Hall des Batailles.
          </p>

          <!-- État de Chargement -->
          <div v-if="isLoading" class="flex flex-col items-center justify-center py-12 gap-3">
            <UIcon name="i-lucide-loader-2" class="w-10 h-10 text-primary-600 animate-spin" />
            <p class="text-sm text-neutral-500 italic">Invocation des variantes depuis Asgard...</p>
          </div>

          <!-- Si aucun plateau n'est disponible -->
          <div v-else-if="gameBoards.length === 0" class="text-center py-10 bg-neutral-100 rounded-xl border border-dashed border-neutral-300">
            <UIcon name="i-lucide-shield-alert" class="w-12 h-12 text-neutral-400 mx-auto mb-3" />
            <p class="text-sm text-neutral-600">Aucune variante de plateau n'est enregistrée dans la base de données.</p>
          </div>

          <!-- Affichage du plateau et de ses règles -->
          <div v-else-if="selectedBoard">
            <!-- Sélecteur à flèches de défilement -->
            <div class="flex items-center justify-between bg-neutral-200/40 border border-neutral-200 p-4 rounded-xl mb-8">
              <UButton 
                icon="i-lucide-chevron-left" 
                variant="outline" 
                color="neutral" 
                @click="prevVariant" 
                :disabled="gameBoards.length <= 1"
                class="hover:bg-neutral-200 bg-white"
              />
              <div class="text-center">
                <h3 class="text-xl font-bold font-['Cinzel',serif] text-neutral-950 tracking-wider">
                  {{ selectedBoard.name }}
                </h3>
                <p class="text-xs text-primary-700 font-bold uppercase tracking-widest mt-1">
                  Plateau de {{ selectedBoard.boardSize }}x{{ selectedBoard.boardSize }}
                </p>
              </div>
              <UButton 
                icon="i-lucide-chevron-right" 
                variant="outline" 
                color="neutral" 
                @click="nextVariant" 
                :disabled="gameBoards.length <= 1"
                class="hover:bg-neutral-200 bg-white"
              />
            </div>

            <div class="grid md:grid-cols-12 gap-8 items-center">
              <!-- Colonne gauche : Composant de visualisation existant BoardPreview (5/12) -->
              <div class="md:col-span-5 flex justify-center">
                <MoleculesBoardPreview
                    class="max-w-[280px]"
                    :board-size="selectedBoard.boardSize"
                    :initial-layout="selectedBoard.initialLayout"
                    :terrain-layout="selectedBoard.terrainLayout"
                />
              </div>

              <!-- Colonne droite : Description et règles spécifiques (7/12) -->
              <div class="md:col-span-7 space-y-6">
                <div v-if="selectedBoard.description">
                  <p class="text-neutral-600 text-sm leading-relaxed italic">
                    "{{ selectedBoard.description }}"
                  </p>
                </div>

                <!-- Grille des propriétés du plateau -->
                <div class="grid sm:grid-cols-2 gap-4">
                  <!-- Condition de Victoire -->
                  <UCard variant="subtle" class="!p-4">
                    <div class="flex items-start gap-3">
                      <UIcon name="i-lucide-arrow-up-right" class="text-primary-600 w-5 h-5 shrink-0 mt-0.5" />
                      <div>
                        <h4 class="text-xs font-bold text-neutral-500 uppercase">Échappée du Roi</h4>
                        <p class="text-sm font-semibold text-neutral-900 mt-1">
                          {{ rulesSummary.winCondition }}
                        </p>
                      </div>
                    </div>
                  </UCard>

                  <!-- Capture du Roi -->
                  <UCard variant="subtle" class="!p-4">
                    <div class="flex items-start gap-3">
                      <UIcon name="i-lucide-shield-alert" class="text-error-600 w-5 h-5 shrink-0 mt-0.5" />
                      <div>
                        <h4 class="text-xs font-bold text-neutral-500 uppercase">Capture du Roi</h4>
                        <p class="text-sm font-semibold text-neutral-900 mt-1">
                          {{ rulesSummary.kingCapture }}
                        </p>
                      </div>
                    </div>
                  </UCard>

                  <!-- Hostilité du Trône -->
                  <UCard variant="subtle" class="!p-4">
                    <div class="flex items-start gap-3">
                      <UIcon name="i-lucide-skull" class="text-primary-600 w-5 h-5 shrink-0 mt-0.5" />
                      <div>
                        <h4 class="text-xs font-bold text-neutral-500 uppercase">Hostilité du Trône</h4>
                        <p class="text-sm font-semibold text-neutral-900 mt-1">
                          {{ rulesSummary.throneHostility }}
                        </p>
                      </div>
                    </div>
                  </UCard>

                  <!-- Armement du Roi -->
                  <UCard variant="subtle" class="!p-4">
                    <div class="flex items-start gap-3">
                      <UIcon name="i-lucide-swords" class="text-warning-500 w-5 h-5 shrink-0 mt-0.5" />
                      <div>
                        <h4 class="text-xs font-bold text-neutral-500 uppercase">Combat du Roi</h4>
                        <p class="text-sm font-semibold text-neutral-900 mt-1">
                          {{ rulesSummary.kingWeapon }}
                        </p>
                      </div>
                    </div>
                  </UCard>
                </div>

                <!-- Alertes additionnelles pour règles spéciales (Copenhagen) -->
                <div v-if="selectedBoard.rules?.shieldWall || selectedBoard.rules?.exitForts" class="space-y-3">
                  <UAlert
                      v-if="selectedBoard.rules?.shieldWall"
                      icon="i-lucide-shield-alert"
                      title="Règle Spéciale : Mur de Boucliers (Shieldwall)"
                      description="Un groupe entier de défenseurs collé à la bordure peut être capturé en un coup s'il est entièrement encerclé par les attaquants."
                      color="primary"
                      variant="subtle"
                />
                  <UAlert
                      v-if="selectedBoard.rules?.exitForts"
                      icon="i-lucide-shield-check"
                      title="Règle Spéciale : Forts de Sortie"
                      description="Les coins agissent comme des forts protecteurs aidant à la sortie et la fuite du Roi."
                      color="warning"
                      variant="subtle"
                  />
                </div>

              </div>
            </div>
          </div>
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