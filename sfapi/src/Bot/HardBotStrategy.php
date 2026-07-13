<?php

namespace App\Bot;

use App\Config\GameRules;
use App\Entity\Game;
use App\Service\GameEngine;

/**
 * Bot Odin — L'Œil du Corbeau (Difficile) — Minimax + Alpha-Bêta Pruning.
 *
 * Explore l'arbre de jeu jusqu'à la profondeur DEPTH pour choisir le meilleur coup.
 * L'élagage Alpha-Bêta élimine les branches non prometteuses sans les évaluer.
 * Inspiré de Bokhtiar-Adil/Vikings-chess-Hnefatafl (Python/Pygame).
 * Niveau cible : Elo ~1400.
 *
 * ----- Comment améliorer ce bot ? -----
 * 1. Augmenter DEPTH (profondeur 4 = ~10× plus lent, attention aux timeouts)
 * 2. Implémenter le "move ordering" (trier les coups par captures potentielles)
 *    avant le Minimax → l'élagage sera plus efficace
 * 3. Ajouter une table de transposition (memoization des positions déjà évaluées)
 * 4. Améliorer evaluateBoard() dans GameBoardHelper (mobilité, contrôle des axes…)
 * 5. Implémenter l'"iterative deepening" (profondeur progressive avec contrainte de temps)
 */
class HardBotStrategy implements BotStrategyInterface
{
    private const DEPTH = 3;

    public function makeMove(Game $game, GameBoardHelper $helper, GameEngine $engine): ?Game
    {
        $board = $game->getBoardState();
        if (empty($board)) {
            $board = $game->getGameBoard()->getInitialLayout();
        }

        $gameBoard = $game->getGameBoard();
        $terrain   = $gameBoard->getTerrainLayout();
        $boardSize = $gameBoard->getBoardSize();
        $rules     = $gameBoard?->getRules() ?: GameRules::getRulesForVariant($game->getVariant() ?? '');

        $movesCount = count($game->getMoves());
        $isAttacker = ($movesCount % 2 === 0);

        $bestMove  = null;
        $bestScore = $isAttacker ? -PHP_INT_MAX : PHP_INT_MAX;

        $possibleMoves = $helper->generateMoves($board, $boardSize, $terrain, $isAttacker);
        if (empty($possibleMoves)) return null;

        // Mélanger pour obtenir de la variété sur les coups de valeur égale
        shuffle($possibleMoves);

        foreach ($possibleMoves as $move) {
            $piece    = $board[$move['from'][0]][$move['from'][1]];
            $boardSim = $helper->applyMove($board, $move['from'], $move['to']);
            $boardSim = $helper->handleCaptures($boardSim, $move['to'][1], $move['to'][0], $piece, $terrain, $boardSize, $rules);

            $score = $this->minimax($boardSim, self::DEPTH - 1, !$isAttacker, -PHP_INT_MAX, PHP_INT_MAX, $boardSize, $terrain, $rules, $helper);

            if ($isAttacker && $score > $bestScore) {
                $bestScore = $score;
                $bestMove  = $move;
            } elseif (!$isAttacker && $score < $bestScore) {
                $bestScore = $score;
                $bestMove  = $move;
            }
        }

        if ($bestMove) {
            return $engine->playMove($game, $bestMove['from'], $bestMove['to'], null);
        }

        return null;
    }

    /**
     * Algorithme Minimax avec Alpha-Bêta Pruning.
     *
     * Convention :
     * - Attaquant = joueur MAX (cherche à maximiser le score)
     * - Défenseur = joueur MIN (cherche à minimiser le score)
     *
     * @param int $alpha Meilleur score garanti pour MAX (élagage si beta <= alpha)
     * @param int $beta  Meilleur score garanti pour MIN (élagage si beta <= alpha)
     */
    private function minimax(
        array           $board,
        int             $depth,
        bool            $isAttackerTurn,
        int             $alpha,
        int             $beta,
        int             $boardSize,
        array           $terrain,
        array           $rules,
        GameBoardHelper $helper
    ): int {
        // Nœud terminal : victoire ou profondeur atteinte
        $victory = $helper->checkVictory($board, $terrain, $boardSize, $rules);
        if ($victory === 'ATTACKER') return 100_000;
        if ($victory === 'DEFENDER') return -100_000;
        if ($depth === 0) return $helper->evaluateBoard($board, $boardSize, $terrain);

        $moves = $helper->generateMoves($board, $boardSize, $terrain, $isAttackerTurn);
        if (empty($moves)) {
            // Aucun coup disponible = impasse = défaite pour ce camp
            return $isAttackerTurn ? -100_000 : 100_000;
        }

        if ($isAttackerTurn) {
            // Nœud MAX
            $maxScore = -PHP_INT_MAX;
            foreach ($moves as $move) {
                $piece    = $board[$move['from'][0]][$move['from'][1]];
                $boardSim = $helper->applyMove($board, $move['from'], $move['to']);
                $boardSim = $helper->handleCaptures($boardSim, $move['to'][1], $move['to'][0], $piece, $terrain, $boardSize, $rules);

                $score    = $this->minimax($boardSim, $depth - 1, false, $alpha, $beta, $boardSize, $terrain, $rules, $helper);
                $maxScore = max($maxScore, $score);
                $alpha    = max($alpha, $score);
                if ($beta <= $alpha) break; // Élagage Bêta
            }
            return $maxScore;
        } else {
            // Nœud MIN
            $minScore = PHP_INT_MAX;
            foreach ($moves as $move) {
                $piece    = $board[$move['from'][0]][$move['from'][1]];
                $boardSim = $helper->applyMove($board, $move['from'], $move['to']);
                $boardSim = $helper->handleCaptures($boardSim, $move['to'][1], $move['to'][0], $piece, $terrain, $boardSize, $rules);

                $score    = $this->minimax($boardSim, $depth - 1, true, $alpha, $beta, $boardSize, $terrain, $rules, $helper);
                $minScore = min($minScore, $score);
                $beta     = min($beta, $score);
                if ($beta <= $alpha) break; // Élagage Alpha
            }
            return $minScore;
        }
    }
}
