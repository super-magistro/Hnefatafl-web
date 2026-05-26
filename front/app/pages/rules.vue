<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'

definePageMeta({
  layout: 'sidebar'
})

const { apiFetch } = useApi()

// Liste statique de secours des plateaux avec les règles historiques viking
const fallbackBoards = [
  {
    id: 13,
    name: "Brandubh",
    boardSize: 7,
    description: "Variante irlandaise rapide et brutale. Les attaquants encerclent étroitement les défenseurs, rendant chaque coup crucial dès le départ.",
    initialLayout: [
      [0,0,0,1,0,0,0],
      [0,0,0,1,0,0,0],
      [0,0,0,2,0,0,0],
      [1,1,2,3,2,1,1],
      [0,0,0,2,0,0,0],
      [0,0,0,1,0,0,0],
      [0,0,0,1,0,0,0]
    ],
    terrainLayout: [
      [2,0,0,0,0,0,2],
      [0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0],
      [0,0,0,1,0,0,0],
      [0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0],
      [2,0,0,0,0,0,2]
    ],
    rules: {
      win_condition: "corner",
      king_capture: "2_sides",
      king_weapon: "armed",
      throne_hostility: "always"
    }
  },
  {
    id: 14,
    name: "Tablut",
    boardSize: 9,
    description: "La célèbre variante Saami documentée par le botaniste Carl von Linné en Laponie. Très équilibrée et parfaite pour l'apprentissage.",
    initialLayout: [
      [0,0,0,1,1,1,0,0,0],
      [0,0,0,0,1,0,0,0,0],
      [0,0,0,0,2,0,0,0,0],
      [1,0,0,0,2,0,0,0,1],
      [1,1,2,2,3,2,2,1,1],
      [1,0,0,0,2,0,0,0,1],
      [0,0,0,0,2,0,0,0,0],
      [0,0,0,0,1,0,0,0,0],
      [0,0,0,1,1,1,0,0,0]
    ],
    terrainLayout: [
      [0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0],
      [0,0,0,0,1,0,0,0,0],
      [0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0]
    ],
    rules: {
      win_condition: "edge",
      king_capture: "4_sides",
      king_weapon: "armed",
      throne_hostility: "empty"
    }
  },
  {
    id: 15,
    name: "Copenhagen",
    boardSize: 11,
    description: "Variante moderne conçue pour la compétition. Elle intègre des règles avancées comme la capture en mur de boucliers (Shieldwall) et les forts de sortie.",
    initialLayout: [
      [0,0,0,1,1,1,1,1,0,0,0],
      [0,0,0,0,0,1,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [1,0,0,0,0,2,0,0,0,0,1],
      [1,0,0,0,2,2,2,0,0,0,1],
      [1,1,0,2,2,3,2,2,0,1,1],
      [1,0,0,0,2,2,2,0,0,0,1],
      [1,0,0,0,0,2,0,0,0,0,1],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,1,0,0,0,0,0],
      [0,0,0,1,1,1,1,1,0,0,0]
    ],
    terrainLayout: [
      [2,0,0,0,0,0,0,0,0,0,2],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,1,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [2,0,0,0,0,0,0,0,0,0,2]
    ],
    rules: {
      win_condition: "corner",
      king_capture: "4_sides",
      king_weapon: "armed",
      throne_hostility: "empty",
      shieldWall: true,
      exitForts: true
    }
  },
  {
    id: 16,
    name: "Tawlbwrdd",
    boardSize: 11,
    description: "Une variante galloise historique mentionnée dans les écrits du roi Howel Dda. Le trône central reste toujours hostile à tous sauf au Roi.",
    initialLayout: [
      [0,0,0,0,1,1,1,0,0,0,0],
      [0,0,0,0,1,0,1,0,0,0,0],
      [0,0,0,0,0,1,0,0,0,0,0],
      [0,0,0,0,0,2,0,0,0,0,0],
      [1,1,0,0,2,2,2,0,0,1,1],
      [1,0,1,2,2,3,2,2,1,0,1],
      [1,1,0,0,2,2,2,0,0,1,1],
      [0,0,0,0,0,2,0,0,0,0,0],
      [0,0,0,0,0,1,0,0,0,0,0],
      [0,0,0,0,1,0,1,0,0,0,0],
      [0,0,0,0,1,1,1,0,0,0,0]
    ],
    terrainLayout: [
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,1,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0]
    ],
    rules: {
      win_condition: "edge",
      king_capture: "2_sides",
      king_weapon: "armed",
      throne_hostility: "always"
    }
  },
  {
    id: 17,
    name: "Fetlar Hnefatafl",
    boardSize: 11,
    description: "Variante originaire de l'île de Fetlar dans l'archipel des Shetland. Elle utilise des dispositions de pièces similaires au Copenhagen.",
    initialLayout: [
      [0,0,0,1,1,1,1,1,0,0,0],
      [0,0,0,0,0,1,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [1,0,0,0,0,2,0,0,0,0,1],
      [1,0,0,0,2,2,2,0,0,0,1],
      [1,1,0,2,2,3,2,2,0,1,1],
      [1,0,0,0,2,2,2,0,0,0,1],
      [1,0,0,0,0,2,0,0,0,0,1],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,1,0,0,0,0,0],
      [0,0,0,1,1,1,1,1,0,0,0]
    ],
    terrainLayout: [
      [2,0,0,0,0,0,0,0,0,0,2],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,1,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0],
      [2,0,0,0,0,0,0,0,0,0,2]
    ],
    rules: {
      win_condition: "corner",
      king_capture: "4_sides",
      king_weapon: "armed",
      throne_hostility: "always"
    }
  },
  {
    id: 18,
    name: "Alea Evangelii",
    boardSize: 19,
    description: "Une gigantesque variante anglo-saxonne reconstituée à partir d'un manuscrit du Xe siècle. Une véritable reconstitution de siège.",
    initialLayout: [
      [0,0,1,0,0,1,0,0,1,0,1,0,0,1,0,0,1,0,0],
      [0,0,1,0,0,1,0,0,0,1,0,0,0,1,0,0,1,0,0],
      [1,1,1,0,0,1,0,0,0,1,0,0,0,1,0,0,1,1,1],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,1,1,1],
      [0,0,0,0,0,0,2,0,2,2,2,0,2,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,2,0,0,0,0,0,0,0,0,0],
      [1,0,0,0,0,0,2,0,2,0,2,0,2,0,0,0,0,0,1],
      [0,1,1,0,0,0,2,2,0,3,0,2,2,0,0,0,1,1,0],
      [1,0,0,0,0,0,2,0,2,0,2,0,2,0,0,0,0,0,1],
      [0,0,0,0,0,0,0,0,0,2,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,2,0,2,2,2,0,2,0,0,0,0,0,0],
      [1,1,1,0,0,0,0,0,0,0,0,0,0,0,0,0,1,1,1],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [1,1,1,0,0,1,0,0,0,1,0,0,0,1,0,0,1,1,1],
      [0,0,1,0,0,1,0,0,0,1,0,0,0,1,0,0,1,0,0],
      [0,0,1,0,0,1,0,0,1,0,1,0,0,1,0,0,1,0,0]
    ],
    terrainLayout: [
      [2,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,2],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0],
      [2,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,0,2]
    ],
    rules: {
      win_condition: "corner",
      king_capture: "4_sides",
      king_weapon: "unarmed",
      throne_hostility: "always"
    }
  }
]

const gameBoards = ref<any[]>(fallbackBoards)
const selectedBoardIndex = useState('selectedBoardIndex', () => 0)

const selectedBoard = computed(() => {
  return gameBoards.value[selectedBoardIndex.value] || fallbackBoards[0]
})

// Accès sécurisé à l'API
onMounted(async () => {
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
    console.warn("API indisponible, utilisation du fallback statique pour les règles spécifiques.")
  }
})

// Fonctions d'aide pour le mini-plateau
const isThrone = (r: number, c: number) => {
  const size = selectedBoard.value.boardSize
  const mid = Math.floor(size / 2)
  return r === mid && c === mid
}

const isCorner = (r: number, c: number) => {
  const size = selectedBoard.value.boardSize
  return (r === 0 || r === size - 1) && (c === 0 || c === size - 1)
}

const getCellBgClass = (r: number, c: number) => {
  if (isThrone(r, c)) return 'bg-amber-950/20 border-warning-600/30'
  if (isCorner(r, c)) return 'bg-primary-950/20 border-primary-600/30'
  return (r + c) % 2 === 0 ? 'bg-neutral-900' : 'bg-neutral-950'
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

        <!-- NOUVELLE SECTION DYNAMIQUE DE CONFIGURATION DE PLATEAU -->
        <section>
          <h2 class="font-['Cinzel',serif] text-primary-950 text-2xl mb-4 flex items-center gap-3 uppercase tracking-wider">
            <UIcon name="i-lucide-grid" class="text-warning-500 w-7 h-7" />
            Variantes & Règles de plateau
          </h2>
          <p class="text-sm text-neutral-500 mb-8">
            Sélectionnez un plateau ci-dessous pour explorer ses règles historiques spécifiques et sa disposition de départ. Votre choix est synchronisé avec la table de commandement.
          </p>

          <!-- Version Desktop : boutons horizontaux de plateau -->
          <div class="hidden sm:flex flex-wrap gap-2 mb-8">
            <UButton
                v-for="(board, idx) in gameBoards"
                :key="board.id || idx"
                :label="board.name"
                :variant="selectedBoardIndex === idx ? 'tabActive' : 'tabInactive'"
                @click="selectedBoardIndex = idx"
                class="rounded-lg text-sm px-4 py-2 border border-neutral-200"
            />
          </div>

          <!-- Version Mobile : Select -->
          <div class="block sm:hidden mb-8">
            <USelect
                :model-value="selectedBoardIndex"
                @update:model-value="(val) => selectedBoardIndex = Number(val)"
                class="w-full"
                :options="gameBoards.map((b, idx) => ({ label: b.name, value: idx }))"
            />
          </div>

          <div class="grid md:grid-cols-12 gap-8 items-start">
            <!-- Colonne gauche : Le plateau de jeu miniaturisé (5/12) -->
            <div class="md:col-span-5 flex flex-col items-center">
              <div class="bg-neutral-950 p-4 rounded-2xl border border-neutral-200 shadow-lg inline-block w-full max-w-[280px]">
                <div
                    class="grid gap-[1px] bg-neutral-800 border border-neutral-700 overflow-hidden rounded-md"
                    :style="{
                      'grid-template-columns': `repeat(${selectedBoard.boardSize}, minmax(0, 1fr))`,
                      'width': '100%',
                      'aspect-ratio': '1/1'
                    }"
                >
                  <template v-for="(row, rIdx) in selectedBoard.initialLayout" :key="rIdx">
                    <div
                        v-for="(cell, cIdx) in row"
                        :key="cIdx"
                        class="relative flex items-center justify-center aspect-square border border-neutral-800/10"
                        :class="getCellBgClass(rIdx, cIdx)"
                    >
                      <!-- Trône au centre -->
                      <div v-if="isThrone(rIdx, cIdx)" class="absolute inset-0.5 border border-warning-500/30 rounded-xs flex items-center justify-center">
                        <UIcon name="i-lucide-crown" class="w-3 h-3 text-warning-500/20" />
                      </div>
                      <!-- Coins de fuite -->
                      <div v-if="isCorner(rIdx, cIdx)" class="absolute inset-0.5 border border-primary-500/30 rounded-xs flex items-center justify-center bg-primary-950/20">
                        <UIcon name="i-lucide-shield" class="w-3 h-3 text-primary-500/30" />
                      </div>

                      <!-- Pions -->
                      <!-- Attaquant (1 - Noir) -->
                      <div
                          v-if="cell === 1"
                          class="w-2/3 h-2/3 rounded-full bg-neutral-950 border border-neutral-800 shadow-md flex items-center justify-center"
                          title="Attaquant"
                      >
                        <div class="w-1.5 h-1.5 rounded-full bg-neutral-700"></div>
                      </div>
                      <!-- Défenseur (2 - Blanc) -->
                      <div
                          v-if="cell === 2"
                          class="w-2/3 h-2/3 rounded-full bg-white border border-neutral-300 shadow-md flex items-center justify-center"
                          title="Défenseur"
                      >
                        <div class="w-1.5 h-1.5 rounded-full bg-neutral-200"></div>
                      </div>
                      <!-- Roi (3 - Or) -->
                      <div
                          v-if="cell === 3"
                          class="w-3/4 h-3/4 rounded-full bg-warning-500 border border-warning-400 shadow-lg flex items-center justify-center animate-pulse"
                          title="Roi"
                      >
                        <UIcon name="i-lucide-crown" class="w-3 h-3 text-warning-950 shrink-0" />
                      </div>
                    </div>
                  </template>
                </div>
              </div>
            </div>

            <!-- Colonne droite : Description et règles spécifiques (7/12) -->
            <div class="md:col-span-7 space-y-6">
              <div>
                <h3 class="font-['Cinzel',serif] text-neutral-950 text-xl font-bold mb-2 uppercase tracking-wide">
                  {{ selectedBoard.name }}
                </h3>
                <p class="text-neutral-600 text-sm leading-relaxed">
                  {{ selectedBoard.description }}
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
                        {{ selectedBoard.rules?.win_condition === 'corner' ? 'Vers les Coins (4)' : 'Vers les Bords (Facile)' }}
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
                        {{ selectedBoard.rules?.king_capture === '2_sides' ? 'Roi faible (2 côtés)' : 'Roi fort (4 côtés)' }}
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
                        {{ selectedBoard.rules?.throne_hostility === 'always' ? 'Toujours hostile' : 'Hostile si vide' }}
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
                        {{ selectedBoard.rules?.king_weapon === 'unarmed' ? 'Roi désarmé' : 'Roi armé' }}
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