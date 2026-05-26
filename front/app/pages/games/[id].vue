<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue'

definePageMeta({
  layout: 'authenticated'
})

const route = useRoute()
const router = useRouter()
const { apiFetch } = useApi()
const { user: currentUser, fetchMe } = useMe()

// Données réactives
const game = ref<any>(null)
const boardDetails = ref<any>(null)
const opponentUser = ref<any>(null)
const isLoading = ref(true)
const isActionLoading = ref(false)
const errorMessage = ref<string | null>(null)

// Sélection de pion
const selectedCell = ref<[number, number] | null>(null)
const validMoves = ref<[number, number][]>([])

// Polling pour récupérer les nouveaux coups de l'adversaire
let pollingInterval: NodeJS.Timeout | null = null

const currentUserId = computed<string | null>(() => {
  if (!currentUser.value) return null
  return currentUser.value['@id'] || (currentUser.value.id ? `/api/users/${currentUser.value.id}` : null)
})

// Rejoindre la partie en tant que second joueur
const joinGame = async (role: 'attacker' | 'defender') => {
  if (!currentUserId.value) {
    errorMessage.value = "Impossible de rejoindre la partie : utilisateur non connecté ou profil non récupéré."
    return
  }
  isActionLoading.value = true
  try {
    const payload = {
      [role]: currentUserId.value,
      status: 'PLAYING'
    }
    const updated = await apiFetch(`/games/${route.params.id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/merge-patch+json'
      },
      body: payload
    })
    game.value = updated
    
    if (game.value.gameBoard) {
      boardDetails.value = await apiFetch(game.value.gameBoard)
    }
    const opponentIri = role === 'attacker' ? game.value.defender : game.value.attacker
    if (opponentIri) {
      opponentUser.value = await apiFetch(opponentIri)
    }
    alert("Vous venez de rejoindre la bataille en tant que " + (role === 'attacker' ? "Attaquant" : "Défenseur") + " !")
  } catch (err: any) {
    console.error('Erreur de ralliement de la partie:', err)
    errorMessage.value = "Impossible de rejoindre la partie."
  } finally {
    isActionLoading.value = false
  }
}

// Chargement des données de la partie
const loadGame = async (showLoader = false) => {
  if (showLoader) isLoading.value = true
  errorMessage.value = null
  try {
    const gameData = await apiFetch(`/games/${route.params.id}`)
    game.value = gameData

    // Rejoindre automatiquement si la partie est PENDING et qu'une place est libre
    if (game.value.status === 'PENDING') {
      const isCreator = game.value.attacker === currentUserId.value || game.value.defender === currentUserId.value
      if (!isCreator) {
        if (!game.value.attacker) {
          await joinGame('attacker')
          return
        } else if (!game.value.defender) {
          await joinGame('defender')
          return
        }
      }
    }

    // Charger les détails du plateau (variante) si pas encore fait
    if (!boardDetails.value && game.value.gameBoard) {
      boardDetails.value = await apiFetch(game.value.gameBoard)
    }

    // Déterminer l'adversaire
    const opponentIri = game.value.attacker === currentUserId.value 
      ? game.value.defender 
      : game.value.attacker
    
    if (opponentIri && (!opponentUser.value || opponentUser.value['@id'] !== opponentIri)) {
      opponentUser.value = await apiFetch(opponentIri)
    }
  } catch (err: any) {
    console.error('Erreur lors du chargement de la partie:', err)
    errorMessage.value = "Impossible de charger la partie. Le drakkar a peut-être sombré."
  } finally {
    if (showLoader) isLoading.value = false
  }
}

// Lancement et arrêt du polling
const startPolling = () => {
  pollingInterval = setInterval(() => {
    // On ne rafraîchit que si la partie est active et que ce n'est pas notre tour
    if (game.value && game.value.status === 'PLAYING' && !isMyTurn.value) {
      loadGame(false)
    }
  }, 3000)
}

const stopPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
}

onMounted(async () => {
  if (!currentUser.value) {
    await fetchMe()
  }
  await loadGame(true)
  startPolling()
})

onUnmounted(() => {
  stopPolling()
})

// Déductions d'état
const boardSize = computed(() => boardDetails.value?.boardSize || 11)

const currentBoardState = computed(() => {
  if (game.value?.boardState && game.value.boardState.length > 0) {
    return game.value.boardState
  }
  return boardDetails.value?.initialLayout || []
})

const isMyTurn = computed(() => {
  if (!game.value || (game.value.status !== 'PLAYING' && game.value.status !== 'PENDING')) return false
  const movesCount = game.value.moves ? game.value.moves.length : 0
  const isAttackerTurn = movesCount % 2 === 0
  return isAttackerTurn
    ? game.value.attacker === currentUserId.value
    : game.value.defender === currentUserId.value
})

const myRole = computed(() => {
  if (!game.value) return null
  return game.value.attacker === currentUserId.value ? 'attacker' : 'defender'
})

// Calculer les coups valides pour une pièce sélectionnée
const calculateValidMoves = (y: number, x: number) => {
  const moves: [number, number][] = []
  const board = currentBoardState.value
  if (!board || board.length === 0) return moves

  const piece = board[y][x]
  if (piece === 0) return moves

  const size = board.length
  const terrain = boardDetails.value?.terrainLayout || []

  // Directions orthogonales (Hnefatafl ne se joue qu'en lignes droites)
  const directions = [
    [0, 1],  // Droite
    [0, -1], // Gauche
    [1, 0],  // Bas
    [-1, 0]  // Haut
  ]

  for (const [dy, dx] of directions) {
    let ny = y + dy
    let nx = x + dx

    while (ny >= 0 && ny < size && nx >= 0 && nx < size) {
      // Bloqué par une autre pièce
      if (board[ny][nx] !== 0) {
        break
      }

      // Règles du terrain : Seul le Roi (3) peut aller sur le Trône (1) ou les Coins (2)
      const cellTerrain = terrain[ny][nx]
      if (piece !== 3 && (cellTerrain === 1 || cellTerrain === 2)) {
        if (cellTerrain === 1) {
          // Le trône est un obstacle physique infranchissable pour les soldats
          break
        }
        // Coins
        break
      }

      moves.push([ny, nx])
      ny += dy
      nx += dx
    }
  }

  return moves
}

// Clic sur une case du plateau
const handleCellClick = async (y: number, x: number) => {
  if (!isMyTurn.value || (game.value.status !== 'PLAYING' && game.value.status !== 'PENDING')) return

  const board = currentBoardState.value
  const piece = board[y][x]

  // Si on clique sur une destination valide de notre pièce sélectionnée
  if (selectedCell.value && validMoves.value.some(([vy, vx]) => vy === y && vx === x)) {
    const [fromY, fromX] = selectedCell.value
    await playMove(fromY, fromX, y, x)
    selectedCell.value = null
    validMoves.value = []
    return
  }

  // Sinon, on tente de sélectionner un de nos pions
  if (piece !== 0) {
    const isAttackerPiece = piece === 1
    const isDefenderPiece = piece === 2 || piece === 3

    if ((myRole.value === 'attacker' && isAttackerPiece) || (myRole.value === 'defender' && isDefenderPiece)) {
      selectedCell.value = [y, x]
      validMoves.value = calculateValidMoves(y, x)
    } else {
      selectedCell.value = null
      validMoves.value = []
    }
  } else {
    selectedCell.value = null
    validMoves.value = []
  }
}

// Jouer un coup via l'API
const playMove = async (fromY: number, fromX: number, toY: number, toX: number) => {
  isActionLoading.value = true
  errorMessage.value = null
  try {
    const response = await apiFetch(`/games/${game.value.id}/play`, {
      method: 'POST',
      body: {
        from: [fromY, fromX],
        to: [toY, toX]
      }
    })
    game.value = response
  } catch (err: any) {
    console.error('Erreur de déplacement:', err)
    errorMessage.value = err.data?.detail || "Mouvement illégal ou refusé par les dieux nordiques."
  } finally {
    isActionLoading.value = false
  }
}

// Abandonner la partie
const handleResign = async () => {
  if (!confirm("Êtes-vous sûr de vouloir abandonner cette bataille ? Votre honneur en dépend.")) return
  
  isActionLoading.value = true
  errorMessage.value = null
  try {
    const response = await apiFetch(`/games/${game.value.id}/resign`, {
      method: 'POST'
    })
    game.value = response
  } catch (err: any) {
    console.error('Erreur lors de l\'abandon:', err)
    errorMessage.value = "Impossible d'abandonner. Le combat doit continuer !"
  } finally {
    isActionLoading.value = false
  }
}

// Copier le lien de la table de combat
const copyGameLink = () => {
  const link = window.location.href
  navigator.clipboard.writeText(link)
    .then(() => {
      alert("Lien de combat copié dans le presse-papier ! Transmettez-le au second joueur.")
    })
    .catch(() => {
      errorMessage.value = `Impossible de copier le lien automatiquement. Le voici : ${link}`
    })
}

// Obtenir le libellé de notation pour le coup
const getMoveLabel = (move: any) => {
  const cols = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S']
  const [fY, fX] = move.from
  const [tY, tX] = move.to
  return `${move.player === 'attacker' ? '⚔️' : '🛡️'} ${cols[fX]}${boardSize.value - fY} ➔ ${cols[tX]}${boardSize.value - tY}`
}
</script>

<template>
  <div class="space-y-6 max-w-7xl mx-auto">
    <!-- Retour au hall -->
    <div class="flex items-center justify-between">
      <UButton
          to="/games"
          variant="Link"
          icon="i-lucide-arrow-left"
          class="text-pine-cone-600 font-bold"
      >
        Retour au Hall des Batailles
      </UButton>

      <span v-if="game" class="text-xs font-semibold text-pine-cone-500 bg-neutral-200 px-3 py-1 rounded-full uppercase tracking-wider">
        Variante : {{ game.variant }} ({{ boardSize }}x{{ boardSize }})
      </span>
    </div>

    <!-- Chargement -->
    <div v-if="isLoading" class="flex flex-col items-center justify-center py-32 space-y-4">
      <UIcon name="i-lucide-loader-2" class="w-12 h-12 text-primary-600 animate-spin" />
      <p class="text-sm font-semibold font-['Cinzel',serif] text-pine-cone-600 tracking-wider">
        Déploiement du plateau de guerre...
      </p>
    </div>

    <!-- Alertes -->
    <UAlert
        v-if="errorMessage"
        color="error"
        variant="subtle"
        icon="i-lucide-alert-triangle"
        :title="errorMessage"
        class="rounded-xl"
    />

    <!-- Vue principale de la partie -->
    <div v-if="game && boardDetails" class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- Colonne Plateau (8/12) -->
      <div class="lg:col-span-8 flex flex-col items-center">
        <!-- Infos Joueurs au dessus du plateau -->
        <div class="w-full max-w-[600px] flex items-center justify-between mb-4 bg-white p-4 rounded-xl border border-neutral-200 shadow-sm">
          <!-- Adversaire -->
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full flex-center bg-oil-100 text-oil-950 font-bold text-lg ring-1 ring-oil-200">
              {{ opponentUser?.email?.charAt(0).toUpperCase() || '?' }}
            </div>
            <div>
              <p class="font-bold text-oil-950 text-sm">{{ opponentUser?.email?.split('@')[0] || 'Opposant' }}</p>
              <p class="text-xs text-pine-cone-500">{{ opponentUser?.elo || 1200 }} Elo • {{ myRole === 'attacker' ? 'Défense (Blancs)' : 'Attaque (Noirs)' }}</p>
            </div>
          </div>

          <!-- Indicateur de tour -->
          <div class="text-right">
            <span v-if="game.status === 'FINISHED'" class="text-xs font-bold text-error-700 bg-error-50 border border-error-100 px-3 py-1.5 rounded-full uppercase tracking-wider">
              🏆 Match terminé
            </span>
            <span v-else-if="isMyTurn" class="text-xs font-extrabold text-white bg-primary-700 px-3 py-1.5 rounded-full uppercase tracking-wider animate-pulse flex items-center gap-1.5 shadow-md">
              <span>⚔️ À VOUS</span>
            </span>
            <span v-else class="text-xs font-semibold text-pine-cone-600 bg-neutral-100 border border-neutral-200 px-3 py-1.5 rounded-full uppercase tracking-wider">
              ⌛ Tour adverse
            </span>
          </div>
        </div>

        <!-- Le Plateau de Jeu -->
        <div class="w-full max-w-[600px] aspect-square bg-oil-950 p-3 rounded-2xl shadow-2xl relative border-4 border-oil-900">
          <div 
              class="grid h-full w-full gap-[2px] bg-oil-800 rounded-lg overflow-hidden"
              :style="`grid-template-columns: repeat(${boardSize}, minmax(0, 1fr)); grid-template-rows: repeat(${boardSize}, minmax(0, 1fr))`"
          >
            <!-- Rendu de chaque case -->
            <div
                v-for="idx in boardSize * boardSize"
                :key="idx"
                @click="handleCellClick(Math.floor((idx - 1) / boardSize), (idx - 1) % boardSize)"
                class="relative aspect-square transition-all duration-200 select-none flex items-center justify-center cursor-pointer"
                :class="[
                  // Alternance de couleur du plateau
                  (Math.floor((idx - 1) / boardSize) + ((idx - 1) % boardSize)) % 2 === 0
                     ? 'bg-neutral-200 hover:bg-neutral-300'
                     : 'bg-neutral-300 hover:bg-neutral-400',
                  
                  // Style spécial Trône (Centre)
                  boardDetails.terrainLayout[Math.floor((idx - 1) / boardSize)][(idx - 1) % boardSize] === 1
                    ? '!bg-oil-800 border-2 border-golden-grass-500/40'
                    : '',
                  
                  // Style spécial Coins (Échappatoire)
                  boardDetails.terrainLayout[Math.floor((idx - 1) / boardSize)][(idx - 1) % boardSize] === 2
                    ? '!bg-golden-grass-200/50 border border-golden-grass-500'
                    : '',
                  
                  // Sélectionné
                  selectedCell && selectedCell[0] === Math.floor((idx - 1) / boardSize) && selectedCell[1] === (idx - 1) % boardSize
                    ? 'ring-4 ring-primary-500 ring-inset z-10'
                    : ''
                ]"
            >
              <!-- Repère visuel pour le trône (Runes/Croix) -->
              <span 
                  v-if="boardDetails.terrainLayout[Math.floor((idx - 1) / boardSize)][(idx - 1) % boardSize] === 1 && currentBoardState[Math.floor((idx - 1) / boardSize)][(idx - 1) % boardSize] === 0"
                  class="text-golden-grass-500/30 font-bold text-lg select-none"
              >
                ᛟ
              </span>

              <!-- Pions -->
              <div
                  v-if="currentBoardState[Math.floor((idx - 1) / boardSize)][(idx - 1) % boardSize] !== 0"
                  class="w-4/5 h-4/5 rounded-full flex items-center justify-center font-bold shadow-md transform hover:scale-105 transition-transform duration-200"
                  :class="[
                    // Attaquant (1) - Noir / Or Viking
                    currentBoardState[Math.floor((idx - 1) / boardSize)][(idx - 1) % boardSize] === 1
                      ? 'bg-oil-900 border-2 border-oil-950 text-white text-xs ring-1 ring-vert-600/40'
                      : '',
                    
                    // Défenseur (2) - Beige / Cuir
                    currentBoardState[Math.floor((idx - 1) / boardSize)][(idx - 1) % boardSize] === 2
                      ? 'bg-neutral-50 border-2 border-neutral-400 text-oil-950 text-xs shadow-inner'
                      : '',
                    
                    // Le Roi (3) - Trône / Or étincelant
                    currentBoardState[Math.floor((idx - 1) / boardSize)][(idx - 1) % boardSize] === 3
                      ? 'bg-golden-grass-500 border-4 border-golden-grass-600 text-golden-grass-950 text-base ring-2 ring-golden-grass-300 font-extrabold scale-105 shadow-xl'
                      : ''
                  ]"
              >
                <!-- Symbole sur le bouclier -->
                <span v-if="currentBoardState[Math.floor((idx - 1) / boardSize)][(idx - 1) % boardSize] === 1" class="text-[9px] opacity-70">⚡</span>
                <span v-if="currentBoardState[Math.floor((idx - 1) / boardSize)][(idx - 1) % boardSize] === 2" class="text-[9px] opacity-70">🛡️</span>
                <span v-if="currentBoardState[Math.floor((idx - 1) / boardSize)][(idx - 1) % boardSize] === 3" class="text-sm">👑</span>
              </div>

              <!-- Indicateur de déplacement possible (Petit point vert) -->
              <div
                  v-if="validMoves.some(([vy, vx]) => vy === Math.floor((idx - 1) / boardSize) && vx === (idx - 1) % boardSize)"
                  class="absolute w-4 h-4 rounded-full bg-primary-500/60 ring-2 ring-white/30 z-10"
              />
            </div>
          </div>
        </div>

        <!-- Infos Joueur en dessous du plateau -->
        <div class="w-full max-w-[600px] flex items-center justify-between mt-4 bg-white p-4 rounded-xl border border-neutral-200 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full flex-center bg-primary-700 text-golden-grass-500 font-bold text-lg ring-1 ring-primary-800">
              {{ currentUser?.email?.charAt(0).toUpperCase() || 'V' }}
            </div>
            <div>
              <p class="font-bold text-oil-950 text-sm">Vous ({{ currentUser?.email?.split('@')[0] }})</p>
              <p class="text-xs text-pine-cone-500">{{ currentUser?.elo || 1200 }} Elo • {{ myRole === 'attacker' ? 'Attaque (Noirs)' : 'Défense (Blancs)' }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Colonne Panneau de jeu (4/12) -->
      <div class="lg:col-span-4 space-y-6">
        <!-- Carte d'état de la partie -->
        <UCard variant="subtle" class="shadow-sm">
          <template #header>
            <h3 class="text-lg font-bold font-['Cinzel',serif] text-oil-950 flex items-center gap-2">
              <UIcon name="i-lucide-scroll" class="text-golden-grass-600" />
              Rapport de Guerre
            </h3>
          </template>

          <div class="space-y-4">
            <div class="flex justify-between items-center text-sm">
              <span class="text-pine-cone-600">Statut :</span>
              <span class="font-bold uppercase tracking-wider" :class="game.status === 'PLAYING' ? 'text-primary-700' : 'text-error-600'">
                {{ game.status === 'PLAYING' ? 'En cours' : 'Terminée' }}
              </span>
            </div>

            <!-- Affichage du gagnant si fini -->
            <div v-if="game.status === 'FINISHED'" class="bg-primary-50 border border-primary-200 p-4 rounded-xl text-center space-y-2">
              <UIcon name="i-lucide-crown" class="w-10 h-10 text-golden-grass-500 mx-auto animate-bounce" />
              <h4 class="font-bold font-['Cinzel',serif] text-primary-900 text-base">Victoire de</h4>
              <p class="text-lg font-extrabold text-primary-950">
                {{ game.winner ? (game.winner.email ? game.winner.email.split('@')[0] : 'Joueur Gagnant') : 'Égalité' }}
              </p>
            </div>

            <div class="flex justify-between items-center text-sm">
              <span class="text-pine-cone-600">Cadence :</span>
              <span class="font-bold text-oil-950">{{ game.timeControl }}</span>
            </div>

            <!-- Liste des coups -->
            <div class="space-y-2">
              <span class="text-xs font-bold text-pine-cone-600 uppercase tracking-wider">Journal de combat :</span>
              <div class="h-[180px] overflow-y-auto border border-neutral-200 rounded-xl bg-white p-3 space-y-1.5 text-sm font-medium">
                <div v-if="!game.moves || game.moves.length === 0" class="text-xs text-pine-cone-400 text-center py-10">
                  Aucun mouvement n'a encore été tenté. Que la bataille commence !
                </div>
                <div 
                    v-for="(move, idx) in game.moves" 
                    :key="idx"
                    class="flex justify-between items-center py-1 px-2 rounded hover:bg-neutral-50"
                >
                  <span class="text-xs text-pine-cone-400 font-bold">#{{ idx + 1 }}</span>
                  <span class="font-bold text-oil-900">{{ getMoveLabel(move) }}</span>
                </div>
              </div>
            </div>
          </div>

          <template #footer>
            <div class="space-y-3">
              <UButton
                  v-if="game.status === 'PLAYING' || game.status === 'PENDING'"
                  variant="outline"
                  block
                  icon="i-lucide-share-2"
                  @click="copyGameLink"
                  class="font-semibold text-warning-600 border-warning-500/30 hover:bg-warning-50"
              >
                Copier le lien d'invitation
              </UButton>
              <UButton
                  v-if="game.status === 'PLAYING'"
                  variant="outline"
                  color="error"
                  block
                  icon="i-lucide-flag"
                  :loading="isActionLoading"
                  @click="handleResign"
              >
                Abandonner la bataille
              </UButton>
              <UButton
                  to="/games"
                  variant="outline"
                  block
                  icon="i-lucide-arrow-left"
              >
                Retour au Hall
              </UButton>
            </div>
          </template>
        </UCard>
      </div>

    </div>
  </div>
</template>

<style scoped>
/* Style spécifique pour le plateau */
</style>
