<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import TabBar from '@/components/atoms/TabBar.vue'
import GameList from '@/components/organisms/GameList.vue'
import { useUserMap } from '@/composables/useUserMap'

definePageMeta({
  layout: 'authenticated'
})

const { apiFetch } = useApi()
const { user: currentUser, fetchMe } = useMe()
const { getUserByIri, setUsers } = useUserMap()

// États réactifs
const games = ref<any[]>([])
const users = ref<any[]>([])
const gameBoards = ref<any[]>([])

const isLoading = ref(true)
const isSubmitting = ref(false)
const errorMessage = ref<string | null>(null)
const successMessage = ref<string | null>(null)
const botWarning = ref<string | null>(null)

// Onglet actif pour la liste des parties : 'active' (en cours), 'pending' (défis en attente), 'finished' (terminées)
const activeTab = ref<'active' | 'pending' | 'finished'>('active')

// Contrôle de la modal de création de défi ami
const isChallengeModalOpen = ref(false)

// Formulaire de défi (sans opponentSelect, uniquement side et time)
const selectedSide = ref<'attacker' | 'defender' | 'random'>('random')
const selectedTimeControl = ref('10+5')

// Index du variant sélectionné
const selectedBoardIndex = ref(0)

// Matchmaking
const isMatching = ref(false)
const matchmakingElapsed = ref(0)
const matchmakingEloRange = ref(50)
let matchmakingTimer: NodeJS.Timeout | null = null

// Rapprochement ELO et ID utilisateur
const currentUserElo = computed(() => currentUser.value?.elo || 1200)
const currentUserId = computed<string | null>(() => {
  if (!currentUser.value) return null
  return currentUser.value['@id'] || (currentUser.value.id ? `/api/users/${currentUser.value.id}` : null)
})

const emptyBoard = {
  name: "Chargement...",
  boardSize: 11,
  initialLayout: [] as number[][],
  terrainLayout: [] as number[][],
  description: "Récupération de la variante depuis les runes...",
  rulesSummary: {
    winCondition: "...",
    kingCapture: "...",
    kingWeapon: "...",
    throneHostility: "..."
  }
}

// Charger et traduire les variantes depuis l'API
const allBoards = computed<any[]>(() => {
  return gameBoards.value.map(board => {
    const rules = board.rules || {}
    
    let winConditionText = 'Bords'
    if (rules.win_condition === 'corner') winConditionText = 'Coins (4)'
    else if (rules.win_condition === 'edge') winConditionText = 'Bords'
    
    let kingCaptureText = '4 côtés'
    if (rules.king_capture === '2_sides') kingCaptureText = '2 côtés (Roi faible)'
    else if (rules.king_capture === '4_sides') kingCaptureText = '4 côtés (Roi fort)'
    
    let kingWeaponText = 'Armé'
    if (rules.king_weapon === 'unarmed' || rules.king_weapon === 'weak') kingWeaponText = 'Désarmé'
    else if (rules.king_weapon === 'armed') kingWeaponText = 'Armé'
    
    let throneHostilityText = 'Hostile vide'
    if (rules.throne_hostility === 'always') throneHostilityText = 'Toujours hostile'
    else if (rules.throne_hostility === 'empty') throneHostilityText = 'Hostile vide'
    else if (rules.throne_hostility === 'never') throneHostilityText = 'Jamais hostile'

    return {
      ...board,
      description: board.description || "Une variante historique de Hnefatafl.",
      rulesSummary: {
        winCondition: winConditionText,
        kingCapture: kingCaptureText,
        kingWeapon: kingWeaponText,
        throneHostility: throneHostilityText
      }
    }
  })
})

const selectedBoard = computed(() => {
  return allBoards.value[selectedBoardIndex.value] || emptyBoard
})

const prevVariant = () => {
  if (allBoards.value.length <= 1) return
  selectedBoardIndex.value = (selectedBoardIndex.value - 1 + allBoards.value.length) % allBoards.value.length
}

const nextVariant = () => {
  if (allBoards.value.length <= 1) return
  selectedBoardIndex.value = (selectedBoardIndex.value + 1) % allBoards.value.length
}

// Chargement initial des données
const loadData = async () => {
  isLoading.value = true
  errorMessage.value = null
  try {
    const promises: Promise<any>[] = [
      apiFetch('/games'),
      apiFetch('/users'),
      apiFetch('/game_boards')
    ]
    if (!currentUser.value) {
      promises.push(fetchMe())
    }
    
    const results = await Promise.all(promises)
    const gamesData = results[0]
    const usersData = results[1]
    const boardsData = results[2]

    games.value = gamesData['hydra:member'] || gamesData['member'] || []
    users.value = usersData['hydra:member'] || usersData['member'] || []
    gameBoards.value = boardsData['hydra:member'] || boardsData['member'] || []
    setUsers(users.value)
  } catch (err: any) {
    console.error('Erreur lors du chargement des données:', err)
    errorMessage.value = "Impossible de charger le Hall des Batailles. Vérifiez la connexion d'API."
  } finally {
    isLoading.value = false
  }
}

onMounted(() => {
  loadData()
})

// Copier le lien d'invitation de la partie
const copyGameLink = (gameId: number) => {
  const link = `${window.location.origin}/games/${gameId}`
  
  const handleSuccess = () => {
    successMessage.value = `Lien de combat créé et copié ! Partagez-le avec votre ami : ${link}`
    errorMessage.value = null
    botWarning.value = null
    setTimeout(() => {
      if (successMessage.value) successMessage.value = null
    }, 10000)
  }

  const handleFallback = () => {
    try {
      const textArea = document.createElement("textarea")
      textArea.value = link
      textArea.style.position = "fixed"
      textArea.style.left = "-999999px"
      textArea.style.top = "-999999px"
      document.body.appendChild(textArea)
      textArea.focus()
      textArea.select()
      const successful = document.execCommand('copy')
      document.body.removeChild(textArea)
      if (successful) {
        handleSuccess()
        return
      }
    } catch (err) {
      console.error('Erreur de copie de secours:', err)
    }
    
    errorMessage.value = `Lien créé (copie automatique impossible sur ce navigateur) : ${link}`
    successMessage.value = null
    botWarning.value = null
  }

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(link)
      .then(handleSuccess)
      .catch(err => {
        console.error('Erreur lors de la copie du lien:', err)
        handleFallback()
      })
  } else {
    handleFallback()
  }
}

// Séparation des parties
const activeGames = computed(() => games.value.filter(g => g.status === 'PLAYING'))
const pendingGames = computed(() => games.value.filter(g => g.status === 'PENDING'))
const finishedGames = computed(() => games.value.filter(g => g.status === 'FINISHED'))

// Tour du joueur
const isMyTurn = (game: any) => {
  if (game.status !== 'PLAYING' && game.status !== 'PENDING') return false
  const movesCount = game.moves ? game.moves.length : 0
  const isAttackerTurn = movesCount % 2 === 0
  return isAttackerTurn
    ? game.attacker === currentUserId.value
    : game.defender === currentUserId.value
}

// Matchmaking Simulation
const startMatchmaking = () => {
  if (isMatching.value) return
  if (!currentUserId.value) {
    errorMessage.value = "Vous devez être connecté pour lancer une bataille."
    return
  }
  
  isMatching.value = true
  matchmakingElapsed.value = 0
  matchmakingEloRange.value = 50
  errorMessage.value = null
  successMessage.value = null
  botWarning.value = null
  
  matchmakingTimer = setInterval(() => {
    matchmakingElapsed.value = Number((matchmakingElapsed.value + 0.5).toFixed(1))
    matchmakingEloRange.value += 30
    
    if (matchmakingElapsed.value >= 2.5) {
      stopMatchmaking(true)
    }
  }, 500)
}

const stopMatchmaking = async (findMatch = false) => {
  if (matchmakingTimer) {
    clearInterval(matchmakingTimer)
    matchmakingTimer = null
  }
  
  if (!findMatch) {
    isMatching.value = false
    return
  }
  
  try {
    const potentialOpponents = users.value.filter(u => u['@id'] !== currentUserId.value)
    if (potentialOpponents.length === 0) {
      isMatching.value = false
      errorMessage.value = "Aucun guerrier disponible dans la taverne. Invitez un ami du clan !"
      return
    }
    
    // Trouver le joueur ayant l'Elo le plus proche
    const matchedUser = potentialOpponents.reduce((closest, current) => {
      const diffClosest = Math.abs((closest.elo || 1200) - currentUserElo.value)
      const diffCurrent = Math.abs((current.elo || 1200) - currentUserElo.value)
      return diffCurrent < diffClosest ? current : closest
    })
    
    // Détermination aléatoire du camp
    const side = Math.random() > 0.5 ? 'attacker' : 'defender'
    const attackerIri = side === 'attacker' ? currentUserId.value : matchedUser['@id']
    const defenderIri = side === 'defender' ? currentUserId.value : matchedUser['@id']
    
    const board = selectedBoard.value
    let boardIri = board['@id']
    if (!boardIri) {
      const matchedApiBoard = gameBoards.value.find(b => b.name.split(' ')[0] === board.name.split(' ')[0])
      boardIri = matchedApiBoard?.['@id'] || gameBoards.value[0]?.['@id']
    }
    
    if (!boardIri) {
      throw new Error("Aucune variante de jeu disponible.")
    }
    
    const payload = {
      variant: board.name.split(' ')[0],
      timeControl: '10+5',
      status: 'PLAYING', // Démarrage immédiat
      attacker: attackerIri,
      defender: defenderIri,
      gameBoard: boardIri,
      boardState: board.initialLayout || []
    }
    
    isSubmitting.value = true
    const newGame: any = await apiFetch('/games', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json'
      },
      body: payload
    })
    
    successMessage.value = `Un adversaire de force similaire (${getUserByIri(matchedUser['@id']).email.split('@')[0]}) a été trouvé !`
    isMatching.value = false
    
    navigateTo(`/games/${newGame.id}`)
  } catch (err: any) {
    console.error('Erreur lors du matchmaking:', err, err.data)
    errorMessage.value = err.data?.detail || err.data?.description || "Impossible d'initier le combat rapide avec l'API."
    isMatching.value = false
  } finally {
    isSubmitting.value = false
  }
}

// Défi Ami (Création d'une partie avec l'opposant vide)
const handleCreateGame = async () => {
  isSubmitting.value = true
  errorMessage.value = null
  successMessage.value = null
  botWarning.value = null

  if (!currentUserId.value) {
    errorMessage.value = "Impossible de créer la table de défi : utilisateur non connecté ou profil non récupéré."
    isSubmitting.value = false
    return
  }

  try {
    let attackerIri: string | null = null
    let defenderIri: string | null = null

    let side = selectedSide.value
    if (side === 'random') {
      side = Math.random() > 0.5 ? 'attacker' : 'defender'
    }

    if (side === 'attacker') {
      attackerIri = currentUserId.value
    } else {
      defenderIri = currentUserId.value
    }

    const board = selectedBoard.value
    let boardIri = board['@id']
    if (!boardIri) {
      const matchedApiBoard = gameBoards.value.find(b => b.name.split(' ')[0] === board.name.split(' ')[0])
      boardIri = matchedApiBoard?.['@id'] || gameBoards.value[0]?.['@id']
    }

    if (!boardIri) {
      throw new Error("Aucune variante de jeu disponible.")
    }

    const payload = {
      variant: board.name.split(' ')[0],
      timeControl: selectedTimeControl.value,
      status: 'PENDING',
      attacker: attackerIri,
      defender: defenderIri,
      gameBoard: boardIri,
      boardState: board.initialLayout || []
    }

    const newGame: any = await apiFetch('/games', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/ld+json'
      },
      body: payload
    })

    isChallengeModalOpen.value = false
    
    // Copier immédiatement le lien et avertir l'utilisateur
    copyGameLink(newGame.id)

    await loadData()
  } catch (err: any) {
    console.error('Erreur de création de défi lien:', err, err.data)
    errorMessage.value = err.data?.detail || err.data?.description || "Impossible de créer la table de défi."
  } finally {
    isSubmitting.value = false
  }
}

// Alerte IA
const showBotWarning = () => {
  botWarning.value = "Les corbeaux d'Odin survolent le champ de bataille, mais les forges d'Asgard travaillent encore sur l'intelligence artificielle du Père de Tout. Cette fonctionnalité sera disponible dans une prochaine mise à jour !"
  errorMessage.value = null
  successMessage.value = null
  setTimeout(() => {
    if (botWarning.value) botWarning.value = null
  }, 6000)
}
</script>

<template>
  <div class="space-y-8 max-w-7xl mx-auto">
    <!-- En-tête -->
    <div>
      <h1 class="text-4xl font-bold font-['Cinzel',serif] text-neutral-950 tracking-wide flex items-center gap-3">
        <UIcon name="i-lucide-swords" class="text-warning-500 w-10 h-10" />
        Le Hall des Batailles
      </h1>
      <p class="text-pine-cone-600 mt-1">
        Sélectionnez votre variante de plateau sacrée, lancez-vous dans l'arène ou gérez vos campagnes en cours.
      </p>
    </div>

    <!-- Alertes globales -->
    <UAlert
        v-if="errorMessage"
        color="error"
        variant="subtle"
        icon="i-lucide-alert-triangle"
        :title="errorMessage"
        class="rounded-xl shadow-sm border border-error-200"
    />

    <UAlert
        v-if="successMessage"
        color="primary"
        variant="subtle"
        icon="i-lucide-check-circle"
        :title="successMessage"
        class="rounded-xl shadow-sm border border-primary-200"
    />

    <UAlert
        v-if="botWarning"
        color="warning"
        variant="subtle"
        icon="i-lucide-info"
        :title="botWarning"
        class="rounded-xl shadow-sm border border-warning-200"
    />

    <!-- État de chargement global -->
    <MoleculesLoadingScreen v-if="isLoading" />

    <!-- Grille Principale (Sélectionneur + Actions) -->
    <div v-else class="space-y-8">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
      
      <!-- PARTIE GAUCHE : La tuile de Variante (7/12) -->
      <div class="lg:col-span-7">
        <OrganismsVariantSelector
          :board="selectedBoard"
          :disable-nav="allBoards.length <= 1"
          @prev="prevVariant"
          @next="nextVariant"
        />
      </div>

      <!-- PARTIE DROITE : Boutons d'actions et Matchmaking (5/12) -->
      <div class="lg:col-span-5">
        
        <!-- ÉCRAN DE MATCHMAKING ACTIF (Molecule) -->
        <MoleculesMatchmakingOverlay
          v-if="isMatching"
          :user-elo="currentUserElo"
          :elo-range="matchmakingEloRange"
          :elapsed="matchmakingElapsed"
          @cancel="stopMatchmaking(false)"
        />

        <!-- OPTIONS DE LANCEMENT NORMALES (Organism) -->
        <OrganismsGameLauncher
          v-else
          :user-elo="currentUserElo"
          @matchmaking="startMatchmaking"
          @challenge="isChallengeModalOpen = true"
          @bot-warning="showBotWarning"
        />
      </div>

    </div>

    <!-- SEPARATION -->
    <USeparator class="my-8" />

    <!-- SECTION DES CAMPAGNES VOS PARTIES -->
    <div class="space-y-6">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold font-['Cinzel',serif] text-neutral-950 tracking-wide flex items-center gap-2">
          <UIcon name="i-lucide-scroll" class="text-warning-500 w-6 h-6" />
          Vos Campagnes Actuelles
        </h2>
      </div>

      <TabBar :tabs="[
          { key: 'active', label: 'En Cours', count: activeGames.length },
          { key: 'pending', label: 'Défis', count: pendingGames.length },
          { key: 'finished', label: 'Historique', count: finishedGames.length }
        ]" :activeTab="activeTab" @update:activeTab="activeTab = $event" />

      <!-- Contenu des onglets -->
      <div>
        <!-- Onglet 1: En Cours -->
        <GameList v-if="activeTab === 'active'" :games="activeGames" :currentUserId="currentUserId" @copy-link="copyGameLink">
          <template #empty>
            <div class="col-span-full text-center py-16 bg-white rounded-2xl border border-neutral-200 p-8 shadow-sm">
              <UIcon name="i-lucide-shield-alert" class="w-16 h-16 text-neutral-300 mx-auto mb-4" />
              <h3 class="text-lg font-bold font-['Cinzel',serif] text-neutral-950 mb-2">Aucun combat actif</h3>
              <p class="text-sm text-pine-cone-500 max-w-sm mx-auto">
                Le silence règne dans les plaines. Aucun joueur n'a dégainé son épée contre vous actuellement.
              </p>
            </div>
          </template>
        </GameList>

        <!-- Onglet 2: Défis -->
        <GameList v-if="activeTab === 'pending'" :games="pendingGames" :currentUserId="currentUserId" @copy-link="copyGameLink">
          <template #empty>
            <div class="col-span-full text-center py-16 bg-white rounded-2xl border border-neutral-200 p-8 shadow-sm">
              <UIcon name="i-lucide-scroll" class="w-16 h-16 text-neutral-300 mx-auto mb-4" />
              <h3 class="text-lg font-bold font-['Cinzel',serif] text-neutral-950 mb-2">Aucun défi lancé</h3>
              <p class="text-sm text-pine-cone-500 max-w-sm mx-auto">
                Aucun traité de guerre n'a été signé. Lancez un défi et partagez le lien avec un ami !
              </p>
            </div>
          </template>
        </GameList>

        <!-- Onglet 3: Historique -->
        <div v-if="activeTab === 'finished'" class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
          <div v-if="finishedGames.length === 0" class="col-span-full text-center py-16 bg-white rounded-2xl border border-neutral-200 p-8 shadow-sm">
            <UIcon name="i-lucide-scroll-text" class="w-16 h-16 text-neutral-300 mx-auto mb-4" />
            <h3 class="text-lg font-bold font-['Cinzel',serif] text-neutral-950 mb-2">Historique vierge</h3>
            <p class="text-sm text-pine-cone-500 max-w-sm mx-auto">
              L'histoire n'a pas encore retenu vos victoires. Terminez une partie pour la voir apparaître ici.
            </p>
          </div>

          <UCard
              v-for="game in finishedGames"
              :key="game.id"
              variant="subtle"
              class="border border-neutral-200 bg-white"
          >
            <template #header>
              <div class="flex items-center justify-between">
                <span class="font-bold text-xs uppercase tracking-wider text-pine-cone-600 bg-neutral-100 px-2.5 py-1 rounded-full">
                  {{ game.variant }}
                </span>
                <span class="text-xs text-error-600 font-bold bg-error-50 px-2.5 py-1 rounded-full uppercase tracking-wider">
                  Terminée
                </span>
              </div>
            </template>

            <div class="space-y-4">
              <div class="space-y-2">
                <div class="flex justify-between items-center text-sm">
                  <span class="text-pine-cone-600">Attaquant (Noirs) :</span>
                  <span class="font-bold text-neutral-950">
                    {{ game.attacker ? getUserByIri(game.attacker).email.split('@')[0] : 'Inconnu' }}
                  </span>
                </div>
                <div class="flex justify-between items-center text-sm">
                  <span class="text-pine-cone-600">Défenseur (Blancs) :</span>
                  <span class="font-bold text-neutral-950">
                    {{ game.defender ? getUserByIri(game.defender).email.split('@')[0] : 'Inconnu' }}
                  </span>
                </div>
              </div>

              <USeparator />

              <!-- Vainqueur -->
              <div class="bg-primary-50 border border-primary-100 p-3 rounded-lg flex items-center justify-between">
                <span class="text-xs font-bold text-primary-800 font-['Cinzel',serif] uppercase">Vainqueur :</span>
                <span class="text-sm font-extrabold text-primary-900">
                  🏆 {{ game.winner ? getUserByIri(game.winner).email.split('@')[0] : 'Égalité' }}
                </span>
              </div>
            </div>

            <template #footer>
              <div class="flex gap-2">
                <UButton
                    :to="`/games/${game.id}`"
                    variant="outline"
                    class="flex-1 justify-center"
                    icon="i-lucide-eye"
                    size="md"
                >
                  Revoir
                </UButton>
                <UButton
                    variant="outline"
                    color="neutral"
                    icon="i-lucide-share-2"
                    title="Copier le lien d'invitation"
                    size="md"
                    @click="copyGameLink(game.id)"
                    class="shrink-0"
                />
              </div>
            </template>
          </UCard>
        </div>
      </div>
    </div>
    </div>

    <!-- MODAL DE DEFI AMI -->
    <UModal 
      v-model:open="isChallengeModalOpen" 
      title="Créer un lien d'invitation"
      description="Configurez votre plateau et générez un lien de guerre à partager avec votre adversaire."
    >
      <template #content>
        <UCard class="w-full max-w-lg">
          <template #header>
            <div class="flex items-center gap-3">
              <UIcon name="i-lucide-scroll" class="w-6 h-6 text-warning-500" />
              <h3 class="text-xl font-bold font-['Cinzel',serif] text-neutral-950">Créer une Bataille</h3>
            </div>
          </template>

          <form @submit.prevent="handleCreateGame" class="space-y-5">
            <!-- Choix du variant fixe d'après la sélection active -->
            <div class="bg-neutral-100 border border-neutral-200 p-3.5 rounded-xl">
              <span class="text-xs font-bold text-pine-cone-500 uppercase tracking-wider block">Variante Sélectionnée</span>
              <span class="text-base font-extrabold text-neutral-950 block mt-0.5">{{ selectedBoard.name }}</span>
            </div>

            <!-- Choix du camp -->
            <div>
              <label class="block text-xs font-bold text-neutral-950 uppercase tracking-wider mb-2">Votre camp</label>
              <div class="grid grid-cols-3 gap-2">
                <UButton
                    type="button"
                    class="justify-center font-bold text-xs"
                    :variant="selectedSide === 'attacker' ? 'tabActive' : 'tabInactive'"
                    @click="selectedSide = 'attacker'"
                >
                  ⚔️ Attaque
                </UButton>
                <UButton
                    type="button"
                    class="justify-center font-bold text-xs"
                    :variant="selectedSide === 'defender' ? 'tabActive' : 'tabInactive'"
                    @click="selectedSide = 'defender'"
                >
                  🛡️ Défense
                </UButton>
                <UButton
                    type="button"
                    class="justify-center font-bold text-xs"
                    :variant="selectedSide === 'random' ? 'tabActive' : 'tabInactive'"
                    @click="selectedSide = 'random'"
                >
                  🎲 Aléatoire
                </UButton>
              </div>
            </div>

            <!-- Cadence de jeu -->
            <div>
              <label class="block text-xs font-bold text-neutral-950 uppercase tracking-wider mb-2">Cadence de la bataille</label>
              <USelect
                  v-model="selectedTimeControl"
                  class="w-full"
                  :options="[
                    { label: 'Blitz (5min + 3s)', value: '5+3' },
                    { label: 'Rapide (10min + 5s)', value: '10+5' },
                    { label: 'Classique (15min + 10s)', value: '15+10' },
                    { label: 'Par correspondance (Sans limite)', value: 'indéfini' }
                  ]"
              />
            </div>

            <UButton
                type="submit"
                variant="cta"
                block
                size="lg"
                :loading="isSubmitting"
                class="mt-6 font-bold"
            >
              Générer le Lien d'Invitation
            </UButton>
          </form>
        </UCard>
      </template>
    </UModal>
  </div>
</template>

<style scoped>
.animate-pulse-slow {
  animation: pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
@keyframes pulse {
  0%, 100% {
    opacity: 1;
  }
  50% {
    opacity: .85;
  }
}
</style>
