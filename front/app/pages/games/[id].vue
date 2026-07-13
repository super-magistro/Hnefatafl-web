<script setup lang="ts">
import { copyToClipboard } from '@/utils/clipboard'

definePageMeta({
  layout: 'authenticated'
})

const route = useRoute()
const { apiFetch } = useApi()
const { user: currentUser, fetchMe } = useMe()

// Helper pour extraire l'ID utilisateur
const getUserId = (userOrIri: any): number | null => {
  if (!userOrIri) return null
  if (typeof userOrIri === 'number') return userOrIri
  if (typeof userOrIri === 'string') {
    if (userOrIri === '/me' || userOrIri.endsWith('/me')) {
      if (currentUser.value && currentUser.value.id) {
        return Number(currentUser.value.id)
      }
    }
    const match = userOrIri.match(/\/users\/(\d+)/)
    return match ? Number(match[1]) : null
  }
  if (userOrIri.id) return Number(userOrIri.id)
  if (userOrIri['@id']) {
    const match = userOrIri['@id'].match(/\/users\/(\d+)/)
    return match ? Number(match[1]) : null
  }
  return null
}

// Helper pour extraire l'IRI d'une entité ou d'une chaîne
const getIri = (userOrIri: any): string | null => {
  if (!userOrIri) return null
  if (typeof userOrIri === 'string') return userOrIri
  return userOrIri['@id'] || (userOrIri.id ? `/api/users/${userOrIri.id}` : null)
}

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
let pollingInterval: any = null

const currentUserIdVal = computed<number | null>(() => {
  return currentUser.value?.id ? Number(currentUser.value.id) : null
})

// Rejoindre la partie en tant que second joueur
const joinGame = async (role: 'attacker' | 'defender') => {
  if (!currentUserIdVal.value) {
    errorMessage.value = "Impossible de rejoindre la partie : utilisateur non connecté."
    return
  }
  isActionLoading.value = true
  try {
    const payload = {
      [role]: `/api/users/${currentUserIdVal.value}`,
      status: 'PLAYING'
    }
    game.value = await apiFetch(`/games/${route.params.id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/merge-patch+json'
      },
      body: payload
    })
    
    const boardIri = getIri(game.value.gameBoard)
    if (boardIri) {
      boardDetails.value = await apiFetch(boardIri)
    }
    const opponentId = role === 'attacker' ? getUserId(game.value.defender) : getUserId(game.value.attacker)
    if (opponentId) {
      opponentUser.value = await apiFetch(`/users/${opponentId}`)
    }
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
    game.value = await apiFetch(`/games/${route.params.id}`)

    const attackerId = getUserId(game.value.attacker)
    const defenderId = getUserId(game.value.defender)

    // Rejoindre automatiquement si la partie est PENDING et qu'une place est libre
    if (game.value.status === 'PENDING') {
      const isCreator = attackerId === currentUserIdVal.value || defenderId === currentUserIdVal.value
      if (!isCreator) {
        if (!attackerId) {
          await joinGame('attacker')
          return
        } else if (!defenderId) {
          await joinGame('defender')
          return
        }
      }
    }

    // Charger les détails du plateau (variante) si pas encore fait
    const boardIri = getIri(game.value.gameBoard)
    if (!boardDetails.value && boardIri) {
      boardDetails.value = await apiFetch(boardIri)
    }

    // Déterminer l'adversaire
    const opponentId = attackerId === currentUserIdVal.value 
      ? defenderId 
      : attackerId
    
    if (opponentId && (!opponentUser.value || opponentUser.value.id !== opponentId)) {
      opponentUser.value = await apiFetch(`/users/${opponentId}`)
    }
  } catch (err: any) {
    console.error('Erreur lors du chargement de la partie:', err)
    errorMessage.value = "Impossible de charger la partie."
  } finally {
    if (showLoader) isLoading.value = false
  }
}

// Lancement et arrêt du polling
const startPolling = () => {
  pollingInterval = setInterval(() => {
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

const sidebarCollapsed = useState('sidebarCollapsed', () => false)

onMounted(async () => {
  sidebarCollapsed.value = true // Masque la sidebar pour libérer de l'espace de jeu
  if (!currentUser.value) {
    await fetchMe()
  }
  await loadGame(true)
  startPolling()
})

onUnmounted(() => {
  stopPolling()
  sidebarCollapsed.value = false // Réaffiche la sidebar en quittant la partie
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
  const attackerId = getUserId(game.value.attacker)
  const defenderId = getUserId(game.value.defender)
  return isAttackerTurn
    ? attackerId === currentUserIdVal.value
    : defenderId === currentUserIdVal.value
})

const winnerName = computed(() => {
  if (!game.value || !game.value.winner) return ''
  
  const winnerId = getUserId(game.value.winner)
  
  if (winnerId === currentUserIdVal.value) {
    return currentUser.value?.email?.split('@')[0] || 'Vous'
  }
  
  if (opponentUser.value && winnerId === opponentUser.value.id) {
    return opponentUser.value?.email?.split('@')[0] || 'Adversaire'
  }
  
  return 'Joueur'
})

const myRole = computed(() => {
  if (!game.value) return null
  return getUserId(game.value.attacker) === currentUserIdVal.value ? 'attacker' : 'defender'
})

// Logique du gameplay
const handleCellClick = async (y: number, x: number) => {
  if (!game.value || game.value.status !== 'PLAYING') return
  if (!isMyTurn.value) return

  const board = currentBoardState.value
  const cellContent = board[y][x]

  // Cas 1 : Pièce alliée sélectionnée
  const isAttackerPiece = cellContent === 1
  const isDefenderPiece = cellContent === 2 || cellContent === 3
  const isAlliedPiece = (myRole.value === 'attacker' && isAttackerPiece) || 
                        (myRole.value === 'defender' && isDefenderPiece)

  if (isAlliedPiece) {
    selectedCell.value = [y, x]
    calculateValidMoves(y, x)
    return
  }

  // Cas 2 : Click sur un déplacement légal
  const isValidMove = validMoves.value.some((m: [number, number]) => m[0] === y && m[1] === x)
  if (isValidMove && selectedCell.value) {
    const [fromY, fromX] = selectedCell.value
    await playMove(fromY, fromX, y, x)
  }

  // Reset de la sélection
  selectedCell.value = null
  validMoves.value = []
}

// Envoyer un coup
const playMove = async (fromY: number, fromX: number, toY: number, toX: number) => {
  isActionLoading.value = true
  errorMessage.value = null
  try {
    game.value = await apiFetch(`/games/${game.value.id}/play`, {
      method: 'POST',
      body: {
        from: [fromY, fromX],
        to: [toY, toX]
      }
    })
  } catch (err: any) {
    console.error('Erreur lors du déplacement:', err)
    errorMessage.value = "Le mouvement est illégal ou a échoué. Les dieux n'ont pas validé ce coup."
  } finally {
    isActionLoading.value = false
  }
}

// Calculer les coups valides pour une pièce sélectionnée
const calculateValidMoves = (y: number, x: number) => {
  const moves: [number, number][] = []
  const board = currentBoardState.value
  if (!board || board.length === 0) return moves

  const piece = board[y][x]
  if (piece === 0) return moves

  const size = board.length
  const terrain = boardDetails.value?.terrainLayout || []

  // Directions : haut, bas, gauche, droite
  const dirs: [number, number][] = [[-1, 0], [1, 0], [0, -1], [0, 1]]

  for (const [dy, dx] of dirs) {
    let cy = y + dy
    let cx = x + dx
    while (cy >= 0 && cy < size && cx >= 0 && cx < size) {
      if (board[cy][cx] !== 0) break // Bloqué par une autre pièce

      // Seul le Roi (3) peut aller sur le trône (1) et les coins (2)
      const terrainType = terrain[cy]?.[cx]
      if (piece !== 3 && (terrainType === 1 || terrainType === 2)) {
        cy += dy
        cx += dx
        continue // Interdit aux pièces de base
      }

      moves.push([cy, cx])
      cy += dy
      cx += dx
    }
  }
  validMoves.value = moves
}

// Abandonner
const handleResign = async () => {
  if (!confirm("Voulez-vous vraiment sonner la retraite et abandonner cette partie ?")) return
  isActionLoading.value = true
  errorMessage.value = null
  try {
    game.value = await apiFetch(`/games/${game.value.id}/resign`, {
      method: 'POST'
    })
  } catch (err: any) {
    console.error('Erreur lors de l\'abandon:', err)
    errorMessage.value = "Impossible d'abandonner. Le combat doit continuer !"
  } finally {
    isActionLoading.value = false
  }
}

// Copier le lien d'invitation
const isLinkCopied = ref(false)

const copyGameLink = async () => {
  const link = window.location.href
  const success = await copyToClipboard(link)
  if (success) {
    isLinkCopied.value = true
    setTimeout(() => {
      isLinkCopied.value = false
    }, 3000)
  } else {
    errorMessage.value = `Impossible de copier le lien. Le voici : ${link}`
  }
}

// Envoi d'un message dans le chat
const isSendingMessage = ref(false)

const sendChatMessage = async (text: string) => {
  if (!text || !game.value) return
  isSendingMessage.value = true
  try {
    const newMsg = {
      sender: currentUser.value?.email?.split('@')[0] || 'Joueur',
      text: text,
      timestamp: new Date().toISOString()
    }
    const currentChat = game.value.chat || []
    const updatedChat = [...currentChat, newMsg]
    
    game.value.chat = updatedChat
    
    game.value = await apiFetch(`/games/${game.value.id}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/merge-patch+json'
      },
      body: {
        chat: updatedChat
      }
    })
  } catch (err) {
    console.error('Erreur lors de l\'envoi du message:', err)
  } finally {
    isSendingMessage.value = false
  }
}
</script>

<template>
  <div class="h-auto lg:h-[calc(100vh-100px)] flex flex-col max-w-7xl mx-auto overflow-y-auto lg:overflow-hidden">
    <!-- Retour au hall -->
    <div class="flex items-center justify-between py-2 shrink-0">
      <UButton
          to="/games"
          variant="link"
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
    <MoleculesLoadingScreen v-if="isLoading" />

    <!-- Alertes -->
    <UAlert
        v-if="errorMessage"
        color="error"
        variant="subtle"
        icon="i-lucide-alert-triangle"
        :title="errorMessage"
        class="rounded-xl shrink-0 mb-4"
    />

    <!-- Vue principale de la partie -->
    <div v-if="game && boardDetails" class="grid grid-cols-1 lg:grid-cols-12 gap-8 flex-1 min-h-0 overflow-y-auto lg:overflow-hidden mb-4">
      
      <!-- Colonne Plateau (8/12) -->
      <div class="lg:col-span-8 flex flex-col items-center justify-between h-auto lg:h-full min-h-0">
        <!-- Infos Joueur Adversaire au-dessus -->
        <MoleculesPlayerInfoCard
          :email="opponentUser?.email"
          :elo="opponentUser?.elo"
          :role="myRole === 'attacker' ? 'defender' : 'attacker'"
          :is-current-player="false"
          :show-turn-indicator="true"
          :is-turn-active="!isMyTurn"
          :game-status="game.status"
          class="mb-3"
        />

        <!-- Le Plateau de Jeu -->
        <MoleculesGameBoard
          :board-size="boardSize"
          :board-state="currentBoardState"
          :terrain-layout="boardDetails.terrainLayout"
          :valid-moves="validMoves"
          :selected-cell="selectedCell"
          :interactive="true"
          @cell-click="handleCellClick"
        />

        <!-- Infos Joueur Local en-dessous -->
        <MoleculesPlayerInfoCard
          :email="currentUser?.email"
          :elo="currentUser?.elo"
          :role="myRole || 'attacker'"
          :is-current-player="true"
          :show-turn-indicator="true"
          :is-turn-active="isMyTurn"
          :game-status="game.status"
          class="mt-3"
        />
      </div>

      <!-- Colonne Panneau de jeu (4/12) -->
      <div class="lg:col-span-4 flex flex-col gap-4 h-auto lg:h-full min-h-0 overflow-y-auto lg:overflow-hidden">
        <!-- Journal de combat / Actions -->
        <OrganismsBattleLog
          :game="game"
          :winner-name="winnerName"
          :is-link-copied="isLinkCopied"
          :is-action-loading="isActionLoading"
          @copy-link="copyGameLink"
          @resign="handleResign"
        />

        <!-- Chat de Taverne -->
        <OrganismsTavernChat
          :messages="game.chat || []"
          :current-sender-name="currentUser?.email?.split('@')[0] || 'Joueur'"
          :is-sending="isSendingMessage"
          @send="sendChatMessage"
        />
      </div>

    </div>
  </div>
</template>
