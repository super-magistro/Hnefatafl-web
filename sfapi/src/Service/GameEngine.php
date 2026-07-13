<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
use App\Config\GameRules;
use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;

class GameEngine
{
    // Constantes pour identifier les pièces sur le plateau
    const EMPTY = 0;
    const ATTACKER = 1; // Pions noirs
    const DEFENDER = 2; // Pions blancs
    const KING = 3;     // Le Roi

    // Constantes pour le terrain (GameBoard->terrainLayout)
    const CELL_NORMAL = 0;
    const CELL_THRONE = 1; // Le trône au centre
    const CELL_CORNER = 2; // Les coins (Victoire)

    /**
     * Méthode principale appelée par le Contrôleur
     */
    public function playMove(Game $game, array $from, array $to, ?User $user): Game
    {
        // 1. Chargement et Initialisation
        $board = $game->getBoardState();

        // Si le plateau est vide, on le charge depuis le GameBoard associé
        if (empty($board)) {
            if (!$game->getGameBoard()) {
                throw new UnexpectedValueException("Aucun plateau de jeu (GameBoard) associé à cette partie.");
            }
            $board = $game->getGameBoard()->getInitialLayout();
        }

        if ($game->getStatus() === 'FINISHED') {
            throw new LogicException("La partie est terminée !");
        }

        // Récupération des infos du terrain
        $gameBoard = $game->getGameBoard();
        $terrain = $gameBoard->getTerrainLayout();
        $boardSize = $gameBoard->getBoardSize();

        // Récupération des règles de la variante (ou par défaut)
        $rules = $gameBoard?->getRules() ?: GameRules::getRulesForVariant($game->getVariant() ?? '');

        // Coordonnées [Ligne, Colonne]
        [$fromY, $fromX] = $from;
        [$toY, $toX] = $to;

        // 2. Vérification du Tour (Attaquant commence toujours)
        $movesCount = count($game->getMoves());
        $isAttackerTurn = ($movesCount % 2 === 0);

        // Vérification de sécurité : est-ce que le bon utilisateur joue ?
        if ($user) {
            $expectedPlayer = $isAttackerTurn ? $game->getAttacker() : $game->getDefender();
            // On compare les ID (ou emails)
            if ($expectedPlayer && $user->getId() !== $expectedPlayer->getId()) {
                throw new LogicException("Ce n'est pas votre tour !");
            }
        }

        // 3. VALIDATION DE LA PIÈCE
        if (!isset($board[$fromY][$fromX])) {
            throw new InvalidArgumentException("Coordonnées de départ invalides.");
        }

        $piece = $board[$fromY][$fromX];

        if ($piece === self::EMPTY) {
            throw new LogicException("Il n'y a pas de pièce à cet endroit.");
        }

        // Vérifie si le joueur bouge sa propre pièce
        if ($isAttackerTurn && ($piece === self::DEFENDER || $piece === self::KING)) {
            throw new LogicException("C'est aux Attaquants (Noirs) de jouer.");
        }
        if (!$isAttackerTurn && $piece === self::ATTACKER) {
            throw new LogicException("C'est aux Défenseurs (Blancs/Roi) de jouer.");
        }

        // 4. VALIDATION DU MOUVEMENT (Règles Hnefatafl)

        // A. Mouvement orthogonal uniquement (pas de diagonale)
        if ($fromX !== $toX && $fromY !== $toY) {
            throw new LogicException("Déplacement en diagonale interdit.");
        }

        // B. La case d'arrivée est-elle occupée ?
        if (!isset($board[$toY][$toX])) {
            throw new InvalidArgumentException("Coordonnées d'arrivée invalides (hors du plateau).");
        }

        if ($board[$toY][$toX] !== self::EMPTY) {
            throw new InvalidArgumentException("La case d'arrivée n'est pas vide.");
        }

        // C. Le chemin est-il libre ? (Pas de saut par dessus les pions)
        if (!$this->isPathClear($board, $fromX, $fromY, $toX, $toY)) {
            throw new LogicException("Le chemin est bloqué par une autre pièce.");
        }

        // D. Restrictions spéciales (Trône et Coins)
        // Généralement, seul le Roi peut aller sur le Trône (1) ou les Coins (2)
        $targetTerrain = $terrain[$toY][$toX] ?? 0;
        if ($piece !== self::KING && ($targetTerrain === self::CELL_THRONE || $targetTerrain === self::CELL_CORNER)) {
            // Exception : Parfois le roi peut quitter le trône et personne ne peut y aller.
            // Ici on applique la règle stricte : seul le Roi va sur les cases spéciales.
            throw new LogicException("Seul le Roi peut aller sur le Trône ou les Coins.");
        }

        // 5. EXÉCUTION
        $board[$toY][$toX] = $piece;
        $board[$fromY][$fromX] = self::EMPTY;

        // 6. GESTION DES CAPTURES (Le "Sandwich")
        $board = $this->handleCaptures($board, $toX, $toY, $piece, $terrain, $boardSize, $rules);

        // 7. MISE À JOUR DE L'ÉTAT DU JEU
        $game->setBoardState($board);

        // Ajout à l'historique
        $moveHistory = $game->getMoves();
        $moveHistory[] = [
            'from' => $from,
            'to' => $to,
            'player' => $isAttackerTurn ? 'attacker' : 'defender',
            'notation' => "Move from $fromY,$fromX to $toY,$toX"
        ];
        $game->setMoves($moveHistory);

        // 8. VÉRIFICATION DE VICTOIRE
        $victory = $this->checkVictory($board, $terrain, $boardSize, $rules);

        if ($victory) {
            $game->setStatus('FINISHED');
            if ($victory === 'ATTACKER') {
                $game->setWinner($game->getAttacker());
            } else {
                $game->setWinner($game->getDefender());
            }
        } else {
            $game->setStatus('PLAYING');
        }

        return $game;
    }

    // =========================================================================
    // FONCTIONS UTILITAIRES (Logique pure)
    // =========================================================================

    /**
     * Vérifie qu'il n'y a pas d'obstacle entre le départ et l'arrivée (Mouvement de la Tour)
     */
    private function isPathClear(array $board, int $x1, int $y1, int $x2, int $y2): bool
    {
        $deltaX = $x2 <=> $x1; // Retourne -1, 0 ou 1
        $deltaY = $y2 <=> $y1;

        $currX = $x1 + $deltaX;
        $currY = $y1 + $deltaY;

        while ($currX !== $x2 || $currY !== $y2) {
            if ($board[$currY][$currX] !== self::EMPTY) {
                return false;
            }
            $currX += $deltaX;
            $currY += $deltaY;
        }
        return true;
    }

    /**
     * Gère la capture par encerclement (Custodial Capture)
     */
    private function handleCaptures(array $board, int $x, int $y, int $aggressorPiece, array $terrain, int $size, array $rules): array
    {
        // Si le Roi est l'agresseur et qu'il est désarmé, aucune capture possible
        $kingWeapon = $rules[GameRules::KEY_KING_WEAPON] ?? GameRules::KING_ARMED;
        if ($aggressorPiece === self::KING && $kingWeapon !== GameRules::KING_ARMED) {
            return $board;
        }

        // Directions : Haut, Bas, Gauche, Droite
        $directions = [[0, -1], [0, 1], [-1, 0], [1, 0]];

        $isAttacker = ($aggressorPiece === self::ATTACKER);

        foreach ($directions as [$dx, $dy]) {
            $victimX = $x + $dx;
            $victimY = $y + $dy;
            $anvilX  = $x + ($dx * 2); // La case de l'autre côté de la victime ("l'enclume")
            $anvilY  = $y + ($dy * 2);

            // Vérifier si l'enclume est sur le plateau
            if ($anvilX < 0 || $anvilX >= $size || $anvilY < 0 || $anvilY >= $size) {
                continue;
            }

            $victimPiece = $board[$victimY][$victimX];

            // Pas de capture si case vide
            if ($victimPiece === self::EMPTY) {
                continue;
            }

            // Définir qui est l'ennemi
            $isVictimEnemy = $isAttacker
                ? ($victimPiece === self::DEFENDER || $victimPiece === self::KING)
                : ($victimPiece === self::ATTACKER);

            if (!$isVictimEnemy) {
                continue;
            }

            // RÈGLE SPÉCIALE ROI : Le Roi n'est jamais capturé par simple sandwich normal ici.
            // Sa capture est gérée de manière centralisée dans checkVictory.
            if ($victimPiece === self::KING) {
                continue;
            }

            // Vérifier l'Enclume
            $anvilPiece = $board[$anvilY][$anvilX];
            $anvilTerrain = $terrain[$anvilY][$anvilX];

            // Mon allié est-il sur l'enclume ?
            if ($isAttacker) {
                $isAnvilAlly = ($anvilPiece === self::ATTACKER);
            } else {
                if ($anvilPiece === self::DEFENDER) {
                    $isAnvilAlly = true;
                } elseif ($anvilPiece === self::KING) {
                    $isAnvilAlly = ($kingWeapon === GameRules::KING_ARMED);
                } else {
                    $isAnvilAlly = false;
                }
            }

            // Les coins et le trône (selon la règle d'hostilité) peuvent servir d'enclume s'ils sont vides
            $isCornerHostile = ($anvilTerrain === self::CELL_CORNER) && ($anvilPiece === self::EMPTY);

            $throneHostility = $rules[GameRules::KEY_THRONE_HOSTILITY] ?? GameRules::THRONE_HOSTILE_EMPTY;
            $isThroneHostile = ($anvilTerrain === self::CELL_THRONE) && ($anvilPiece === self::EMPTY) && ($throneHostility !== GameRules::THRONE_NEVER_HOSTILE);

            $isAnvilHostileStructure = $isCornerHostile || $isThroneHostile;

            if ($isAnvilAlly || $isAnvilHostileStructure) {
                // BOUM ! Capture effectuée
                $board[$victimY][$victimX] = self::EMPTY;
            }
        }

        return $board;
    }

    /**
     * Vérifie si quelqu'un a gagné
     */
    private function checkVictory(array $board, array $terrain, int $size, array $rules): ?string
    {
        $kingPos = null;

        // 1. Trouver le Roi
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($board[$y][$x] === self::KING) {
                    $kingPos = [$y, $x];
                    break 2;
                }
            }
        }

        // Si le Roi n'est plus là (bug ou capture totale), Attaquant gagne
        if (!$kingPos) {
            return 'ATTACKER';
        }

        [$kY, $kX] = $kingPos;

        // 2. VICTOIRE DÉFENSEUR : Le Roi a atteint sa destination
        $winConditionRule = $rules[GameRules::KEY_WIN_CONDITION] ?? GameRules::WIN_CORNER;
        if ($winConditionRule === GameRules::WIN_CORNER) {
            if ($terrain[$kY][$kX] === self::CELL_CORNER) {
                return 'DEFENDER';
            }
        } elseif ($winConditionRule === GameRules::WIN_EDGE) {
            if ($kY === 0 || $kY === $size - 1 || $kX === 0 || $kX === $size - 1) {
                return 'DEFENDER';
            }
        }

        // 3. VICTOIRE ATTAQUANT : Le Roi est capturé
        $kingCaptureRule = $rules[GameRules::KEY_KING_CAPTURE] ?? GameRules::CAPTURE_4_SIDES;

        // Est-il sur le trône ?
        $isOnThrone = ($terrain[$kY][$kX] === self::CELL_THRONE);

        // Est-il adjacent au trône ?
        $isAdjacentToThrone = false;
        $directions = [[0, 1], [0, -1], [1, 0], [-1, 0]];
        foreach ($directions as [$dx, $dy]) {
            $ny = $kY + $dy;
            $nx = $kX + $dx;
            if ($ny >= 0 && $ny < $size && $nx >= 0 && $nx < $size) {
                if ($terrain[$ny][$nx] === self::CELL_THRONE) {
                    $isAdjacentToThrone = true;
                    break;
                }
            }
        }

        // Si le Roi est sur le trône ou adjacent au trône, il doit être entouré sur 4 côtés
        // (le trône vide compte comme un bloqueur s'il est adjacent).
        if ($isOnThrone || $isAdjacentToThrone) {
            $blockedSides = 0;
            foreach ($directions as [$dx, $dy]) {
                $ny = $kY + $dy;
                $nx = $kX + $dx;
                if ($ny >= 0 && $ny < $size && $nx >= 0 && $nx < $size) {
                    $piece = $board[$ny][$nx];
                    if ($piece === self::ATTACKER) {
                        $blockedSides++;
                    } elseif ($terrain[$ny][$nx] === self::CELL_THRONE && $piece === self::EMPTY) {
                        $blockedSides++;
                    }
                }
            }
            if ($blockedSides === 4) {
                return 'ATTACKER';
            }
        } else {
            // S'il est ailleurs sur le plateau
            if ($kingCaptureRule === GameRules::CAPTURE_2_SIDES) {
                // Capturable par sandwich simple (2 côtés opposés)
                $verticalSandwich = $this->isAnvilForKing($kY - 1, $kX, $board, $terrain, $size, $rules)
                    && $this->isAnvilForKing($kY + 1, $kX, $board, $terrain, $size, $rules);
                $horizontalSandwich = $this->isAnvilForKing($kY, $kX - 1, $board, $terrain, $size, $rules)
                    && $this->isAnvilForKing($kY, $kX + 1, $board, $terrain, $size, $rules);

                if ($verticalSandwich || $horizontalSandwich) {
                    return 'ATTACKER';
                }
            } else {
                // CAPTURE_4_SIDES : Doit être entouré sur ses 4 côtés par des attaquants, des coins ou des bords
                $blockedSides = 0;
                foreach ($directions as [$dx, $dy]) {
                    $ny = $kY + $dy;
                    $nx = $kX + $dx;

                    if ($ny < 0 || $ny >= $size || $nx < 0 || $nx >= $size) {
                        $blockedSides++; // Le bord bloque
                        continue;
                    }

                    $piece = $board[$ny][$nx];
                    if ($piece === self::ATTACKER) {
                        $blockedSides++;
                    } elseif ($terrain[$ny][$nx] === self::CELL_CORNER && $piece === self::EMPTY) {
                        $blockedSides++;
                    } elseif ($terrain[$ny][$nx] === self::CELL_THRONE && $piece === self::EMPTY) {
                        $blockedSides++;
                    }
                }
                if ($blockedSides === 4) {
                    return 'ATTACKER';
                }
            }
        }

        return null; // La partie continue
    }

    /**
     * Détermine si une case sert d'enclume pour capturer le Roi par sandwich
     */
    private function isAnvilForKing(int $y, int $x, array $board, array $terrain, int $size, array $rules): bool
    {
        if ($y < 0 || $y >= $size || $x < 0 || $x >= $size) {
            return false; // Le bord n'est pas une enclume pour un sandwich de Roi
        }

        $piece = $board[$y][$x];
        if ($piece === self::ATTACKER) {
            return true;
        }

        // Coins vides : toujours hostile
        if ($terrain[$y][$x] === self::CELL_CORNER && $piece === self::EMPTY) {
            return true;
        }

        // Trône vide : hostile selon throne_hostility
        if ($terrain[$y][$x] === self::CELL_THRONE && $piece === self::EMPTY) {
            $throneHostility = $rules[GameRules::KEY_THRONE_HOSTILITY] ?? GameRules::THRONE_HOSTILE_EMPTY;
            return $throneHostility === GameRules::THRONE_ALWAYS_HOSTILE || $throneHostility === GameRules::THRONE_HOSTILE_EMPTY;
        }

        return false;
    }

    /**
     * Génère tous les mouvements possibles pour un camp donné
     */
    public function generatePossibleMoves(Game $game, ?bool $forAttacker = null): array
    {
        $board = $game->getBoardState();
        if (empty($board)) {
            $board = $game->getGameBoard()->getInitialLayout();
        }
        $gameBoard = $game->getGameBoard();
        $terrain = $gameBoard->getTerrainLayout();
        $boardSize = $gameBoard->getBoardSize();

        if ($forAttacker === null) {
            $movesCount = count($game->getMoves());
            $forAttacker = ($movesCount % 2 === 0);
        }

        return $this->generateMovesForBoard($board, $boardSize, $terrain, $forAttacker);
    }

    /**
     * Génère les mouvements possibles directement à partir d'un tableau de plateau
     */
    private function generateMovesForBoard(array $board, int $boardSize, array $terrain, bool $forAttacker): array
    {
        $possibleMoves = [];
        $directions = [[0, 1], [0, -1], [1, 0], [-1, 0]];

        for ($y = 0; $y < $boardSize; $y++) {
            for ($x = 0; $x < $boardSize; $x++) {
                $piece = $board[$y][$x];
                if ($piece === self::EMPTY) continue;

                $isPieceOwned = $forAttacker
                    ? ($piece === self::ATTACKER)
                    : ($piece === self::DEFENDER || $piece === self::KING);

                if (!$isPieceOwned) continue;

                foreach ($directions as [$dy, $dx]) {
                    $currY = $y + $dy;
                    $currX = $x + $dx;

                    while ($currY >= 0 && $currY < $boardSize && $currX >= 0 && $currX < $boardSize) {
                        if ($board[$currY][$currX] !== self::EMPTY) break;

                        $targetTerrain = $terrain[$currY][$currX] ?? self::CELL_NORMAL;
                        if ($piece !== self::KING && ($targetTerrain === self::CELL_THRONE || $targetTerrain === self::CELL_CORNER)) {
                            $currY += $dy;
                            $currX += $dx;
                            continue;
                        }

                        $possibleMoves[] = ['from' => [$y, $x], 'to' => [$currY, $currX]];

                        $currY += $dy;
                        $currX += $dx;
                    }
                }
            }
        }

        return $possibleMoves;
    }

    /**
     * Calcule et joue le meilleur coup pour le Bot.
     * Route vers le bon algorithme selon l'email du bot.
     * - 'easy-bot@hnefatafl.com' → Algorithme heuristique basique (Novice)
     * - 'bot@hnefatafl.com'      → Minimax + Alpha-Beta (Odin)
     */
    public function makeBotMove(Game $game, ?string $botEmail = null): ?Game
    {
        if ($botEmail === 'easy-bot@hnefatafl.com') {
            return $this->makeEasyBotMove($game);
        }
        return $this->makeHardBotMove($game);
    }

    /**
     * Bot Novice (Facile) — Algorithme heuristique basique
     * Prioritise les captures et la proximité du Roi, sans exploration future.
     */
    private function makeEasyBotMove(Game $game): ?Game
    {
        $boardBefore = $game->getBoardState();
        if (empty($boardBefore)) {
            $boardBefore = $game->getGameBoard()->getInitialLayout();
        }
        $gameBoard = $game->getGameBoard();
        $terrain = $gameBoard->getTerrainLayout();
        $boardSize = $gameBoard->getBoardSize();
        $rules = $gameBoard?->getRules() ?: GameRules::getRulesForVariant($game->getVariant() ?? '');

        $movesCount = count($game->getMoves());
        $isAttacker = ($movesCount % 2 === 0);

        $possibleMoves = $this->generateMovesForBoard($boardBefore, $boardSize, $terrain, $isAttacker);
        if (empty($possibleMoves)) return null;

        $opponentPieceType = $isAttacker ? self::DEFENDER : self::ATTACKER;
        $opponentCountBefore = 0;
        $kingYBefore = null;
        $kingXBefore = null;
        for ($y = 0; $y < $boardSize; $y++) {
            for ($x = 0; $x < $boardSize; $x++) {
                if ($boardBefore[$y][$x] === $opponentPieceType || ($isAttacker && $boardBefore[$y][$x] === self::KING)) {
                    $opponentCountBefore++;
                }
                if ($boardBefore[$y][$x] === self::KING) {
                    $kingYBefore = $y;
                    $kingXBefore = $x;
                }
            }
        }

        $bestMove = null;
        $bestScore = -INF;

        foreach ($possibleMoves as $move) {
            $score = rand(0, 100); // Base aléatoire → rend le bot imparfait
            $from = $move['from'];
            $to = $move['to'];

            $boardSim = $boardBefore;
            $pieceMoved = $boardSim[$from[0]][$from[1]];
            $boardSim[$to[0]][$to[1]] = $pieceMoved;
            $boardSim[$from[0]][$from[1]] = self::EMPTY;
            $boardSim = $this->handleCaptures($boardSim, $to[1], $to[0], $pieceMoved, $terrain, $boardSize, $rules);

            // 1. Coup gagnant immédiat → priorité absolue
            $victory = $this->checkVictory($boardSim, $terrain, $boardSize, $rules);
            if ($victory !== null) {
                if (($victory === 'ATTACKER' && $isAttacker) || ($victory === 'DEFENDER' && !$isAttacker)) {
                    $score += 1000000;
                }
            }

            // 2. Bonus captures
            $opponentCountAfter = 0;
            for ($y = 0; $y < $boardSize; $y++) {
                for ($x = 0; $x < $boardSize; $x++) {
                    if ($boardSim[$y][$x] === $opponentPieceType || ($isAttacker && $boardSim[$y][$x] === self::KING)) {
                        $opponentCountAfter++;
                    }
                }
            }
            $captures = $opponentCountBefore - $opponentCountAfter;
            if ($captures > 0) {
                $score += $captures * 2000;
            }

            // 3. Heuristiques positionnelles simples
            if (!$isAttacker && $pieceMoved === self::KING && $kingYBefore !== null) {
                $corners = [[0, 0], [0, $boardSize-1], [$boardSize-1, 0], [$boardSize-1, $boardSize-1]];
                $minDistBefore = INF;
                $minDistAfter = INF;
                foreach ($corners as [$cY, $cX]) {
                    $minDistBefore = min($minDistBefore, abs($from[0]-$cY)+abs($from[1]-$cX));
                    $minDistAfter  = min($minDistAfter,  abs($to[0]-$cY)+abs($to[1]-$cX));
                }
                $score += ($minDistBefore - $minDistAfter) * 1000;
            } elseif ($isAttacker && $kingYBefore !== null) {
                $distBefore = abs($from[0]-$kingYBefore)+abs($from[1]-$kingXBefore);
                $distAfter  = abs($to[0]-$kingYBefore)+abs($to[1]-$kingXBefore);
                $score += ($distBefore - $distAfter) * 150;
                if ($distAfter === 1) $score += 1000;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove = $move;
            }
        }

        if ($bestMove) {
            return $this->playMove($game, $bestMove['from'], $bestMove['to'], null);
        }

        return null;
    }

    /**
     * Bot Odin (Difficile) — Minimax + Alpha-Beta Pruning (profondeur 3)
     * Inspiré de Bokhtiar-Adil/Vikings-chess-Hnefatafl
     */
    private function makeHardBotMove(Game $game): ?Game
    {
        $board = $game->getBoardState();
        if (empty($board)) {
            $board = $game->getGameBoard()->getInitialLayout();
        }
        $gameBoard = $game->getGameBoard();
        $terrain = $gameBoard->getTerrainLayout();
        $boardSize = $gameBoard->getBoardSize();
        $rules = $gameBoard?->getRules() ?: GameRules::getRulesForVariant($game->getVariant() ?? '');

        $movesCount = count($game->getMoves());
        $isAttacker = ($movesCount % 2 === 0);

        $depth = 3;
        $bestMove = null;
        $bestScore = $isAttacker ? -PHP_INT_MAX : PHP_INT_MAX;

        $possibleMoves = $this->generateMovesForBoard($board, $boardSize, $terrain, $isAttacker);
        if (empty($possibleMoves)) return null;

        shuffle($possibleMoves);

        foreach ($possibleMoves as $move) {
            $boardSim = $this->applyMove($board, $move['from'], $move['to']);
            $boardSim = $this->handleCaptures($boardSim, $move['to'][1], $move['to'][0], $board[$move['from'][0]][$move['from'][1]], $terrain, $boardSize, $rules);

            $score = $this->minimax($boardSim, $depth - 1, !$isAttacker, -PHP_INT_MAX, PHP_INT_MAX, $boardSize, $terrain, $rules);

            if ($isAttacker && $score > $bestScore) {
                $bestScore = $score;
                $bestMove = $move;
            } elseif (!$isAttacker && $score < $bestScore) {
                $bestScore = $score;
                $bestMove = $move;
            }
        }

        if ($bestMove) {
            return $this->playMove($game, $bestMove['from'], $bestMove['to'], null);
        }

        return null;
    }

    /**
     * Algorithme Minimax avec Alpha-Bêta Pruning
     * L'Attaquant est MAX, le Défenseur/Roi est MIN
     */
    private function minimax(array $board, int $depth, bool $isAttackerTurn, int $alpha, int $beta, int $boardSize, array $terrain, array $rules): int
    {
        // Vérifier la victoire sur ce plateau
        $victory = $this->checkVictory($board, $terrain, $boardSize, $rules);
        if ($victory === 'ATTACKER') return 100000;
        if ($victory === 'DEFENDER') return -100000;

        if ($depth === 0) {
            return $this->evaluateBoard($board, $boardSize, $terrain);
        }

        $moves = $this->generateMovesForBoard($board, $boardSize, $terrain, $isAttackerTurn);
        if (empty($moves)) {
            // Aucun coup disponible = défaite pour ce camp
            return $isAttackerTurn ? -100000 : 100000;
        }

        if ($isAttackerTurn) {
            // MAX (Attaquant veut maximiser)
            $maxScore = -PHP_INT_MAX;
            foreach ($moves as $move) {
                $boardSim = $this->applyMove($board, $move['from'], $move['to']);
                $piece = $board[$move['from'][0]][$move['from'][1]];
                $boardSim = $this->handleCaptures($boardSim, $move['to'][1], $move['to'][0], $piece, $terrain, $boardSize, $rules);

                $score = $this->minimax($boardSim, $depth - 1, false, $alpha, $beta, $boardSize, $terrain, $rules);
                $maxScore = max($maxScore, $score);
                $alpha = max($alpha, $score);
                if ($beta <= $alpha) break; // Élagage Bêta
            }
            return $maxScore;
        } else {
            // MIN (Défenseur veut minimiser)
            $minScore = PHP_INT_MAX;
            foreach ($moves as $move) {
                $boardSim = $this->applyMove($board, $move['from'], $move['to']);
                $piece = $board[$move['from'][0]][$move['from'][1]];
                $boardSim = $this->handleCaptures($boardSim, $move['to'][1], $move['to'][0], $piece, $terrain, $boardSize, $rules);

                $score = $this->minimax($boardSim, $depth - 1, true, $alpha, $beta, $boardSize, $terrain, $rules);
                $minScore = min($minScore, $score);
                $beta = min($beta, $score);
                if ($beta <= $alpha) break; // Élagage Alpha
            }
            return $minScore;
        }
    }

    /**
     * Applique un mouvement et retourne le nouveau plateau
     */
    private function applyMove(array $board, array $from, array $to): array
    {
        $piece = $board[$from[0]][$from[1]];
        $board[$to[0]][$to[1]] = $piece;
        $board[$from[0]][$from[1]] = self::EMPTY;
        return $board;
    }

    /**
     * Fonction d'évaluation heuristique du plateau
     * Score positif = avantage pour l'Attaquant
     * Score négatif = avantage pour le Défenseur
     * Inspiré du projet Bokhtiar-Adil/Vikings-chess-Hnefatafl
     */
    private function evaluateBoard(array $board, int $boardSize, array $terrain): int
    {
        $score = 0;
        $attackerCount = 0;
        $defenderCount = 0;
        $kingY = null;
        $kingX = null;
        $center = (int)($boardSize / 2);
        $corners = [[0, 0], [0, $boardSize - 1], [$boardSize - 1, 0], [$boardSize - 1, $boardSize - 1]];

        for ($y = 0; $y < $boardSize; $y++) {
            for ($x = 0; $x < $boardSize; $x++) {
                $cell = $board[$y][$x];
                if ($cell === self::ATTACKER) {
                    $attackerCount++;
                } elseif ($cell === self::DEFENDER) {
                    $defenderCount++;
                } elseif ($cell === self::KING) {
                    $kingY = $y;
                    $kingX = $x;
                }
            }
        }

        // 1. Avantage numérique : chaque pièce vaut 100 points
        $score += $attackerCount * 100;
        $score -= $defenderCount * 100;

        // 2. Position du Roi : capital pour les deux camps
        if ($kingY !== null) {
            // Distance du Roi au coin le plus proche (perspective Défenseur = min est bon)
            $minDistToCorner = PHP_INT_MAX;
            foreach ($corners as [$cY, $cX]) {
                $dist = abs($kingY - $cY) + abs($kingX - $cX);
                $minDistToCorner = min($minDistToCorner, $dist);
            }
            // Plus le Roi est proche d'un coin, plus c'est bon pour le Défenseur (score négatif)
            $score -= (10 - $minDistToCorner) * 150;

            // Distance du Roi au centre (plus c'est loin, mieux c'est pour le Défenseur)
            $distToCenter = abs($kingY - $center) + abs($kingX - $center);
            $score -= $distToCenter * 50;

            // Encerclement du Roi : compter les attaquants directement adjacents
            $surroundCount = 0;
            $directions = [[0, 1], [0, -1], [1, 0], [-1, 0]];
            foreach ($directions as [$dy, $dx]) {
                $ny = $kingY + $dy;
                $nx = $kingX + $dx;
                if ($ny >= 0 && $ny < $boardSize && $nx >= 0 && $nx < $boardSize) {
                    if ($board[$ny][$nx] === self::ATTACKER ||
                        ($terrain[$ny][$nx] ?? self::CELL_NORMAL) === self::CELL_THRONE ||
                        ($terrain[$ny][$nx] ?? self::CELL_NORMAL) === self::CELL_CORNER) {
                        $surroundCount++;
                    }
                }
            }
            // Plus le Roi est encerclé, mieux c'est pour l'Attaquant
            $score += $surroundCount * 300;

            // Roi sur une ligne ou colonne libre vers un coin = grande menace pour l'Attaquant
            foreach ($corners as [$cY, $cX]) {
                if ($kingY === $cY || $kingX === $cX) {
                    $score -= 500; // Avantage défenseur = score négatif
                }
            }
        }

        return $score;
    }
}
