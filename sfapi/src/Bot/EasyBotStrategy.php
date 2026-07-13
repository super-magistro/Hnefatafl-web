<?php

namespace App\Bot;

use App\Config\GameRules;
use App\Entity\Game;
use App\Service\GameEngine;

/**
 * Bot Novice (Facile) — Algorithme heuristique basique.
 *
 * Évalue chaque coup possible sur 1 seul niveau de profondeur.
 * Introduit une base aléatoire pour être imprévisible et imparfait.
 * Niveau cible : Elo ~400.
 *
 * ----- Comment améliorer ce bot ? -----
 * 1. Réduire la part d'aléatoire (rand(0, X)) pour le rendre plus prévisible
 * 2. Ajouter des heuristiques (ex: défendre le Roi, contrôler les couloirs)
 * 3. Augmenter la profondeur à 2 (voir HardBotStrategy pour le pattern Minimax)
 * 4. Ajuster les poids (captures × 2000, proximité × 150…)
 */
class EasyBotStrategy implements BotStrategyInterface
{
    public function makeMove(Game $game, GameBoardHelper $helper, GameEngine $engine): ?Game
    {
        $boardBefore = $game->getBoardState();
        if (empty($boardBefore)) {
            $boardBefore = $game->getGameBoard()->getInitialLayout();
        }

        $gameBoard = $game->getGameBoard();
        $terrain   = $gameBoard->getTerrainLayout();
        $boardSize = $gameBoard->getBoardSize();
        $rules     = $gameBoard?->getRules() ?: GameRules::getRulesForVariant($game->getVariant() ?? '');

        $movesCount = count($game->getMoves());
        $isAttacker = ($movesCount % 2 === 0);

        $possibleMoves = $helper->generateMoves($boardBefore, $boardSize, $terrain, $isAttacker);
        if (empty($possibleMoves)) return null;

        // --- Contexte initial ---
        $opponentType        = $isAttacker ? GameBoardHelper::DEFENDER : GameBoardHelper::ATTACKER;
        $opponentCountBefore = 0;
        $kingYBefore         = null;
        $kingXBefore         = null;

        for ($y = 0; $y < $boardSize; $y++) {
            for ($x = 0; $x < $boardSize; $x++) {
                if ($boardBefore[$y][$x] === $opponentType || ($isAttacker && $boardBefore[$y][$x] === GameBoardHelper::KING)) {
                    $opponentCountBefore++;
                }
                if ($boardBefore[$y][$x] === GameBoardHelper::KING) {
                    $kingYBefore = $y;
                    $kingXBefore = $x;
                }
            }
        }

        $bestMove  = null;
        $bestScore = -INF;

        foreach ($possibleMoves as $move) {
            // Base aléatoire → variété et imperfection délibérée du bot
            $score = rand(0, 100);
            ['from' => $from, 'to' => $to] = $move;

            $pieceMoved = $boardBefore[$from[0]][$from[1]];
            $boardSim   = $helper->applyMove($boardBefore, $from, $to);
            $boardSim   = $helper->handleCaptures($boardSim, $to[1], $to[0], $pieceMoved, $terrain, $boardSize, $rules);

            // 1. Coup gagnant immédiat → priorité absolue
            $victory = $helper->checkVictory($boardSim, $terrain, $boardSize, $rules);
            if ($victory !== null) {
                if (($victory === 'ATTACKER' && $isAttacker) || ($victory === 'DEFENDER' && !$isAttacker)) {
                    $score += 1_000_000;
                }
            }

            // 2. Bonus captures (chaque pièce capturée = +2000)
            $opponentCountAfter = 0;
            for ($y = 0; $y < $boardSize; $y++) {
                for ($x = 0; $x < $boardSize; $x++) {
                    if ($boardSim[$y][$x] === $opponentType || ($isAttacker && $boardSim[$y][$x] === GameBoardHelper::KING)) {
                        $opponentCountAfter++;
                    }
                }
            }
            $captures = $opponentCountBefore - $opponentCountAfter;
            if ($captures > 0) {
                $score += $captures * 2000;
            }

            // 3. Heuristiques positionnelles simples (1 seul niveau de profondeur)
            if (!$isAttacker && $pieceMoved === GameBoardHelper::KING && $kingYBefore !== null) {
                // Défenseur : rapprocher le Roi d'un coin
                $corners   = [[0, 0], [0, $boardSize - 1], [$boardSize - 1, 0], [$boardSize - 1, $boardSize - 1]];
                $minBefore = INF;
                $minAfter  = INF;
                foreach ($corners as [$cY, $cX]) {
                    $minBefore = min($minBefore, abs($from[0] - $cY) + abs($from[1] - $cX));
                    $minAfter  = min($minAfter, abs($to[0] - $cY) + abs($to[1] - $cX));
                }
                $score += ($minBefore - $minAfter) * 1000;
            } elseif ($isAttacker && $kingYBefore !== null) {
                // Attaquant : se rapprocher du Roi et l'encercler
                $distBefore = abs($from[0] - $kingYBefore) + abs($from[1] - $kingXBefore);
                $distAfter  = abs($to[0] - $kingYBefore) + abs($to[1] - $kingXBefore);
                $score += ($distBefore - $distAfter) * 150;
                if ($distAfter === 1) $score += 1000; // Bonus case adjacente au Roi
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestMove  = $move;
            }
        }

        if ($bestMove) {
            return $engine->playMove($game, $bestMove['from'], $bestMove['to'], null);
        }

        return null;
    }
}
