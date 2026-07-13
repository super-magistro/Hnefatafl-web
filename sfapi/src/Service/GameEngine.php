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
}
