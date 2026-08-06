<?php

namespace App\Bot;

use App\Config\GameRules;

/**
 * Service utilitaire partagé entre GameEngine et toutes les stratégies Bot.
 * Contient UNIQUEMENT des opérations pures sur le plateau (sans persistance DB).
 *
 * Toutes les méthodes sont stateless : elles prennent un tableau en entrée
 * et retournent un tableau modifié. Aucun effet de bord.
 */
class GameBoardHelper
{
    // -------------------------------------------------------------------------
    // Constantes des pièces (dupliquées ici pour que les bots soient autonomes)
    // -------------------------------------------------------------------------
    const EMPTY    = 0;
    const ATTACKER = 1; // Pions noirs (Attaquants)
    const DEFENDER = 2; // Pions blancs (Défenseurs)
    const KING     = 3; // Le Roi

    const CELL_NORMAL = 0;
    const CELL_THRONE = 1; // Le trône au centre
    const CELL_CORNER = 2; // Les coins (victoire)

    // -------------------------------------------------------------------------
    // Opérations sur le plateau
    // -------------------------------------------------------------------------

    /**
     * Applique un mouvement et retourne le nouveau plateau (immuable, sans effet de bord).
     */
    public function applyMove(array $board, array $from, array $to): array
    {
        $piece = $board[$from[0]][$from[1]];
        $board[$to[0]][$to[1]]     = $piece;
        $board[$from[0]][$from[1]] = self::EMPTY;
        return $board;
    }

    /**
     * Génère tous les mouvements légaux pour un camp donné à partir d'un tableau de plateau.
     *
     * @param array $board      Grille 2D [y][x] → constante de pièce
     * @param int   $boardSize  Dimension du plateau (NxN)
     * @param array $terrain    Grille 2D [y][x] → constante de terrain
     * @param bool  $forAttacker true = génère les coups des Attaquants, false = Défenseurs+Roi
     * @return array[] Liste de ['from' => [y, x], 'to' => [y, x]]
     */
    public function generateMoves(array $board, int $boardSize, array $terrain, bool $forAttacker): array
    {
        $possibleMoves = [];
        $directions    = [[0, 1], [0, -1], [1, 0], [-1, 0]];

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

                        // Seul le Roi peut aller sur le Trône ou les Coins
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
     * Gère les captures custodiales (sandwich) après un déplacement.
     * Retourne le plateau mis à jour. Le Roi n'est jamais capturé ici (cf. checkVictory).
     */
    public function handleCaptures(array $board, int $x, int $y, int $aggressorPiece, array $terrain, int $size, array $rules): array
    {
        $kingWeapon = $rules[GameRules::KEY_KING_WEAPON] ?? GameRules::KING_ARMED;
        if ($aggressorPiece === self::KING && $kingWeapon !== GameRules::KING_ARMED) {
            return $board; // Roi désarmé : pas de capture possible
        }

        $directions = [[0, -1], [0, 1], [-1, 0], [1, 0]];
        $isAttacker = ($aggressorPiece === self::ATTACKER);

        foreach ($directions as [$dx, $dy]) {
            $victimX = $x + $dx;
            $victimY = $y + $dy;
            $anvilX  = $x + ($dx * 2);
            $anvilY  = $y + ($dy * 2);

            if ($anvilX < 0 || $anvilX >= $size || $anvilY < 0 || $anvilY >= $size) continue;

            $victimPiece = $board[$victimY][$victimX];
            if ($victimPiece === self::EMPTY) continue;

            $isVictimEnemy = $isAttacker
                ? ($victimPiece === self::DEFENDER || $victimPiece === self::KING)
                : ($victimPiece === self::ATTACKER);

            if (!$isVictimEnemy) continue;
            if ($victimPiece === self::KING) continue; // Capture du Roi gérée dans checkVictory

            $anvilPiece   = $board[$anvilY][$anvilX];
            $anvilTerrain = $terrain[$anvilY][$anvilX];

            if ($isAttacker) {
                $isAnvilAlly = ($anvilPiece === self::ATTACKER);
            } else {
                $isAnvilAlly = match (true) {
                    $anvilPiece === self::DEFENDER => true,
                    $anvilPiece === self::KING     => ($kingWeapon === GameRules::KING_ARMED),
                    default                        => false,
                };
            }

            $throneHostility  = $rules[GameRules::KEY_THRONE_HOSTILITY] ?? GameRules::THRONE_HOSTILE_EMPTY;
            $isCornerHostile  = ($anvilTerrain === self::CELL_CORNER) && ($anvilPiece === self::EMPTY);
            $isThroneHostile  = ($anvilTerrain === self::CELL_THRONE) && ($anvilPiece === self::EMPTY)
                             && ($throneHostility !== GameRules::THRONE_NEVER_HOSTILE);

            if ($isAnvilAlly || $isCornerHostile || $isThroneHostile) {
                $board[$victimY][$victimX] = self::EMPTY;
            }
        }

        return $board;
    }

    /**
     * Vérifie si une condition de victoire est atteinte sur le plateau donné.
     *
     * @param bool|null $isNextTurnAttacker Optionnel : vérifie l'absence de coups pour le joueur dont c'est le tour
     * @return string|null 'ATTACKER', 'DEFENDER', ou null si la partie continue
     */
    public function checkVictory(array $board, array $terrain, int $size, array $rules, ?bool $isNextTurnAttacker = null): ?string
    {
        // 1. Compter les pièces et trouver le Roi
        $kingPos       = null;
        $attackerCount = 0;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($board[$y][$x] === self::KING) {
                    $kingPos = [$y, $x];
                } elseif ($board[$y][$x] === self::ATTACKER) {
                    $attackerCount++;
                }
            }
        }

        if (!$kingPos) return 'ATTACKER'; // Roi introuvable (capture totale ou bug)
        if ($attackerCount === 0) return 'DEFENDER'; // Plus aucun attaquant (anéantissement)

        [$kY, $kX] = $kingPos;
        $directions = [[0, 1], [0, -1], [1, 0], [-1, 0]];

        // 2. Victoire Défenseur : Roi a atteint sa destination
        $winCondition = $rules[GameRules::KEY_WIN_CONDITION] ?? GameRules::WIN_CORNER;
        if ($winCondition === GameRules::WIN_CORNER && ($terrain[$kY][$kX] ?? 0) === self::CELL_CORNER) {
            return 'DEFENDER';
        }
        if ($winCondition === GameRules::WIN_EDGE && ($kY === 0 || $kY === $size - 1 || $kX === 0 || $kX === $size - 1)) {
            return 'DEFENDER';
        }

        // 3. Victoire Attaquant : Roi est capturé
        $kingCaptureRule = $rules[GameRules::KEY_KING_CAPTURE] ?? GameRules::CAPTURE_4_SIDES;
        $isOnThrone      = (($terrain[$kY][$kX] ?? 0) === self::CELL_THRONE);

        $isAdjacentToThrone = false;
        foreach ($directions as [$dx, $dy]) {
            $ny = $kY + $dy; $nx = $kX + $dx;
            if ($ny >= 0 && $ny < $size && $nx >= 0 && $nx < $size && ($terrain[$ny][$nx] ?? 0) === self::CELL_THRONE) {
                $isAdjacentToThrone = true;
                break;
            }
        }

        if ($isOnThrone || $isAdjacentToThrone) {
            $blocked = 0;
            foreach ($directions as [$dx, $dy]) {
                $ny = $kY + $dy; $nx = $kX + $dx;
                if ($ny >= 0 && $ny < $size && $nx >= 0 && $nx < $size) {
                    $p = $board[$ny][$nx];
                    if ($p === self::ATTACKER) $blocked++;
                    elseif (($terrain[$ny][$nx] ?? 0) === self::CELL_THRONE && $p === self::EMPTY) $blocked++;
                }
            }
            if ($blocked === 4) return 'ATTACKER';
        } else {
            if ($kingCaptureRule === GameRules::CAPTURE_2_SIDES) {
                $v = $this->isAnvilForKing($kY - 1, $kX, $board, $terrain, $size, $rules)
                  && $this->isAnvilForKing($kY + 1, $kX, $board, $terrain, $size, $rules);
                $h = $this->isAnvilForKing($kY, $kX - 1, $board, $terrain, $size, $rules)
                  && $this->isAnvilForKing($kY, $kX + 1, $board, $terrain, $size, $rules);
                if ($v || $h) return 'ATTACKER';
            } else {
                $blocked = 0;
                foreach ($directions as [$dx, $dy]) {
                    $ny = $kY + $dy; $nx = $kX + $dx;
                    if ($ny < 0 || $ny >= $size || $nx < 0 || $nx >= $size) { $blocked++; continue; }
                    $p = $board[$ny][$nx];
                    if ($p === self::ATTACKER) $blocked++;
                    elseif (($terrain[$ny][$nx] ?? 0) === self::CELL_CORNER && $p === self::EMPTY) $blocked++;
                    elseif (($terrain[$ny][$nx] ?? 0) === self::CELL_THRONE && $p === self::EMPTY) $blocked++;
                }
                if ($blocked === 4) return 'ATTACKER';
            }
        }

        // 4. Immobilisation : Le joueur dont c'est le tour n'a plus aucun coup légal possible
        if ($isNextTurnAttacker !== null) {
            $nextMoves = $this->generateMoves($board, $size, $terrain, $isNextTurnAttacker);
            if (empty($nextMoves)) {
                return $isNextTurnAttacker ? 'DEFENDER' : 'ATTACKER';
            }
        }

        return null;
    }

    /**
     * Évalue un plateau du point de vue de l'Attaquant.
     * Score positif = avantage Attaquant. Score négatif = avantage Défenseur.
     *
     * Heuristiques inspirées de Bokhtiar-Adil/Vikings-chess-Hnefatafl :
     * - Avantage numérique (pièces)
     * - Distance du Roi aux coins
     * - Encerclement du Roi
     */
    public function evaluateBoard(array $board, int $boardSize, array $terrain): int
    {
        $score = 0;
        $attackerCount = 0;
        $defenderCount = 0;
        $kingY = null;
        $kingX = null;
        $center  = (int)($boardSize / 2);
        $corners = [[0, 0], [0, $boardSize - 1], [$boardSize - 1, 0], [$boardSize - 1, $boardSize - 1]];

        for ($y = 0; $y < $boardSize; $y++) {
            for ($x = 0; $x < $boardSize; $x++) {
                match ($board[$y][$x]) {
                    self::ATTACKER => $attackerCount++,
                    self::DEFENDER => $defenderCount++,
                    self::KING     => [$kingY, $kingX] = [$y, $x],
                    default        => null,
                };
            }
        }

        // 1. Avantage numérique
        $score += ($attackerCount - $defenderCount) * 100;

        if ($kingY === null) return $score;

        // 2. Distance du Roi au coin le plus proche (plus proche = bon pour Défenseur = score négatif)
        $minDistToCorner = PHP_INT_MAX;
        foreach ($corners as [$cY, $cX]) {
            $minDistToCorner = min($minDistToCorner, abs($kingY - $cY) + abs($kingX - $cX));
        }
        $score -= (10 - $minDistToCorner) * 150;

        // 3. Distance du Roi au centre (éloigné = bon pour Défenseur = score négatif)
        $score -= (abs($kingY - $center) + abs($kingX - $center)) * 50;

        // 4. Encerclement du Roi (entouré = bon pour Attaquant = score positif)
        $surroundCount = 0;
        foreach ([[0, 1], [0, -1], [1, 0], [-1, 0]] as [$dy, $dx]) {
            $ny = $kingY + $dy; $nx = $kingX + $dx;
            if ($ny >= 0 && $ny < $boardSize && $nx >= 0 && $nx < $boardSize) {
                if ($board[$ny][$nx] === self::ATTACKER
                    || ($terrain[$ny][$nx] ?? 0) === self::CELL_THRONE
                    || ($terrain[$ny][$nx] ?? 0) === self::CELL_CORNER) {
                    $surroundCount++;
                }
            }
        }
        $score += $surroundCount * 300;

        // 5. Roi aligné avec un coin = menace d'évasion (très bon pour Défenseur)
        foreach ($corners as [$cY, $cX]) {
            if ($kingY === $cY || $kingX === $cX) {
                $score -= 500;
            }
        }

        return $score;
    }

    /**
     * Détermine si une case peut servir d'enclume pour capturer le Roi par sandwich (2 côtés).
     */
    public function isAnvilForKing(int $y, int $x, array $board, array $terrain, int $size, array $rules): bool
    {
        if ($y < 0 || $y >= $size || $x < 0 || $x >= $size) return false;

        $piece = $board[$y][$x];
        if ($piece === self::ATTACKER) return true;
        if (($terrain[$y][$x] ?? 0) === self::CELL_CORNER && $piece === self::EMPTY) return true;
        if (($terrain[$y][$x] ?? 0) === self::CELL_THRONE && $piece === self::EMPTY) {
            $th = $rules[GameRules::KEY_THRONE_HOSTILITY] ?? GameRules::THRONE_HOSTILE_EMPTY;
            return $th === GameRules::THRONE_ALWAYS_HOSTILE || $th === GameRules::THRONE_HOSTILE_EMPTY;
        }

        return false;
    }
}
