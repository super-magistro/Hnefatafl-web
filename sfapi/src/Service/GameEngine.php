<?php

namespace App\Service;

use App\Bot\BotStrategyInterface;
use App\Bot\EasyBotStrategy;
use App\Bot\GameBoardHelper;
use App\Bot\HardBotStrategy;
use App\Config\GameRules;
use App\Entity\Game;
use App\Entity\User;
use App\Service\EloCalculator;
use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;

/**
 * Moteur de jeu Hnefatafl.
 *
 * Responsabilités :
 * - Valider et appliquer les coups humains (playMove)
 * - Router les coups de bot vers la bonne stratégie (makeBotMove)
 * - Déléguer toute la logique pure du plateau à GameBoardHelper
 *
 * Pour ajouter un nouveau bot :
 * 1. Créer une classe App\Bot\XxxBotStrategy implements BotStrategyInterface
 * 2. L'ajouter à BOT_STRATEGIES ci-dessous
 * 3. Créer l'utilisateur correspondant en base de données
 */
class GameEngine
{
    // Constantes des pièces (gardées ici pour compatibilité avec les tests existants)
    const EMPTY    = 0;
    const ATTACKER = 1;
    const DEFENDER = 2;
    const KING     = 3;

    const CELL_NORMAL = 0;
    const CELL_THRONE = 1;
    const CELL_CORNER = 2;

    /**
     * Registre des bots : email → classe de stratégie.
     * Ajouter une entrée ici suffit pour enregistrer un nouveau bot.
     */
    private const BOT_STRATEGIES = [
        'easy-bot@hnefatafl.com' => EasyBotStrategy::class,
        'bot@hnefatafl.com'      => HardBotStrategy::class,
    ];

    public function __construct(
        private readonly GameBoardHelper $boardHelper,
        private readonly EloCalculator $eloCalculator
    ) {}

    // =========================================================================
    // API PUBLIQUE
    // =========================================================================

    /**
     * Valide et applique le coup d'un joueur humain.
     * Lance une exception si le coup est invalide.
     */
    public function playMove(Game $game, array $from, array $to, ?User $user): Game
    {
        // 1. Chargement du plateau
        $board = $game->getBoardState();
        if (empty($board)) {
            if (!$game->getGameBoard()) {
                throw new UnexpectedValueException("Aucun plateau de jeu (GameBoard) associé à cette partie.");
            }
            $board = $game->getGameBoard()->getInitialLayout();
        }

        if ($game->getStatus() === 'FINISHED') {
            throw new LogicException("La partie est terminée !");
        }

        $gameBoard = $game->getGameBoard();
        $terrain   = $gameBoard->getTerrainLayout();
        $boardSize = $gameBoard->getBoardSize();
        $rules     = $gameBoard?->getRules() ?: GameRules::getRulesForVariant($game->getVariant() ?? '');

        [$fromY, $fromX] = $from;
        [$toY, $toX]     = $to;

        // 2. Vérification du tour
        $movesCount     = count($game->getMoves());
        $isAttackerTurn = ($movesCount % 2 === 0);

        if ($user) {
            $expectedPlayer = $isAttackerTurn ? $game->getAttacker() : $game->getDefender();
            if ($expectedPlayer && $user->getId() !== $expectedPlayer->getId()) {
                throw new LogicException("Ce n'est pas votre tour !");
            }
        }

        // 3. Validation de la pièce
        if (!isset($board[$fromY][$fromX])) {
            throw new InvalidArgumentException("Coordonnées de départ invalides.");
        }

        $piece = $board[$fromY][$fromX];

        if ($piece === self::EMPTY) {
            throw new LogicException("Il n'y a pas de pièce à cet endroit.");
        }
        if ($isAttackerTurn && ($piece === self::DEFENDER || $piece === self::KING)) {
            throw new LogicException("C'est aux Attaquants (Noirs) de jouer.");
        }
        if (!$isAttackerTurn && $piece === self::ATTACKER) {
            throw new LogicException("C'est aux Défenseurs (Blancs/Roi) de jouer.");
        }

        // 4. Validation du mouvement
        if ($fromX !== $toX && $fromY !== $toY) {
            throw new LogicException("Déplacement en diagonale interdit.");
        }
        if (!isset($board[$toY][$toX])) {
            throw new InvalidArgumentException("Coordonnées d'arrivée invalides (hors du plateau).");
        }
        if ($board[$toY][$toX] !== self::EMPTY) {
            throw new InvalidArgumentException("La case d'arrivée n'est pas vide.");
        }
        if (!$this->isPathClear($board, $fromX, $fromY, $toX, $toY)) {
            throw new LogicException("Le chemin est bloqué par une autre pièce.");
        }

        $targetTerrain = $terrain[$toY][$toX] ?? 0;
        if ($piece !== self::KING && ($targetTerrain === self::CELL_THRONE || $targetTerrain === self::CELL_CORNER)) {
            throw new LogicException("Seul le Roi peut aller sur le Trône ou les Coins.");
        }

        // 5. Exécution
        $board[$toY][$toX]     = $piece;
        $board[$fromY][$fromX] = self::EMPTY;

        // 6. Captures
        $board = $this->boardHelper->handleCaptures($board, $toX, $toY, $piece, $terrain, $boardSize, $rules);

        // 7. Mise à jour de la partie
        $game->setBoardState($board);

        $moveHistory   = $game->getMoves();
        $moveHistory[] = [
            'from'     => $from,
            'to'       => $to,
            'player'   => $isAttackerTurn ? 'attacker' : 'defender',
            'notation' => "Move from $fromY,$fromX to $toY,$toX",
        ];
        $game->setMoves($moveHistory);

        // 8. Vérification de victoire
        $nextMovesCount     = count($game->getMoves());
        $isNextTurnAttacker = ($nextMovesCount % 2 === 0);
        $victory            = $this->boardHelper->checkVictory($board, $terrain, $boardSize, $rules, $isNextTurnAttacker);
        if ($victory) {
            $game->setStatus('FINISHED');
            $game->setWinner($victory === 'ATTACKER' ? $game->getAttacker() : $game->getDefender());
            $this->eloCalculator->updateEloForFinishedGame($game);
        } else {
            $game->setStatus('PLAYING');
        }

        return $game;
    }

    /**
     * Route le coup du bot vers la stratégie appropriée selon l'email.
     * Si l'email est inconnu, utilise le bot difficile par défaut.
     */
    public function makeBotMove(Game $game, ?string $botEmail = null): ?Game
    {
        if ($game->getStatus() === 'FINISHED') {
            return $game;
        }

        $gameBoard = $game->getGameBoard();
        $terrain   = $gameBoard->getTerrainLayout();
        $boardSize = $gameBoard->getBoardSize();
        $rules     = $gameBoard?->getRules() ?: GameRules::getRulesForVariant($game->getVariant() ?? '');

        $movesCount     = count($game->getMoves());
        $isAttackerTurn = ($movesCount % 2 === 0);

        // Vérification préalable si la partie est déjà gagnée (ex: 0 pièces ou blocage)
        $board   = $game->getBoardState() ?: $gameBoard->getInitialLayout();
        $victory = $this->boardHelper->checkVictory($board, $terrain, $boardSize, $rules, $isAttackerTurn);
        if ($victory !== null) {
            $game->setStatus('FINISHED');
            $game->setWinner($victory === 'ATTACKER' ? $game->getAttacker() : $game->getDefender());
            $this->eloCalculator->updateEloForFinishedGame($game);
            return $game;
        }

        $strategyClass = self::BOT_STRATEGIES[$botEmail] ?? HardBotStrategy::class;
        /** @var BotStrategyInterface $strategy */
        $strategy = new $strategyClass();
        $result   = $strategy->makeMove($game, $this->boardHelper, $this);

        if ($result === null) {
            $victory = $isAttackerTurn ? 'DEFENDER' : 'ATTACKER';
            $game->setStatus('FINISHED');
            $game->setWinner($victory === 'ATTACKER' ? $game->getAttacker() : $game->getDefender());
            $this->eloCalculator->updateEloForFinishedGame($game);
            return $game;
        }

        return $result;
    }

    /**
     * Génère les mouvements possibles pour le camp courant (ou le camp spécifié).
     * Point d'entrée public utilisé par les contrôleurs/tests.
     */
    public function generatePossibleMoves(Game $game, ?bool $forAttacker = null): array
    {
        $board = $game->getBoardState();
        if (empty($board)) {
            $board = $game->getGameBoard()->getInitialLayout();
        }

        $gameBoard = $game->getGameBoard();
        $terrain   = $gameBoard->getTerrainLayout();
        $boardSize = $gameBoard->getBoardSize();

        if ($forAttacker === null) {
            $forAttacker = (count($game->getMoves()) % 2 === 0);
        }

        return $this->boardHelper->generateMoves($board, $boardSize, $terrain, $forAttacker);
    }

    // =========================================================================
    // MÉTHODES PRIVÉES (validation uniquement — logique plateau dans BoardHelper)
    // =========================================================================

    /**
     * Vérifie qu'il n'y a pas d'obstacle entre le départ et l'arrivée (mouvement de Tour).
     */
    private function isPathClear(array $board, int $x1, int $y1, int $x2, int $y2): bool
    {
        $deltaX = $x2 <=> $x1;
        $deltaY = $y2 <=> $y1;
        $currX  = $x1 + $deltaX;
        $currY  = $y1 + $deltaY;

        while ($currX !== $x2 || $currY !== $y2) {
            if ($board[$currY][$currX] !== self::EMPTY) return false;
            $currX += $deltaX;
            $currY += $deltaY;
        }

        return true;
    }
}
