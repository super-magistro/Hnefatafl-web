<?php

namespace App\Bot;

use App\Entity\Game;
use App\Service\GameEngine;

/**
 * Interface commune à toutes les stratégies de Bot Hnefatafl.
 *
 * Pour ajouter un nouveau bot :
 * 1. Créer une classe qui implémente cette interface
 * 2. Enregistrer son email dans GameEngine::BOT_STRATEGIES
 * 3. Ajouter l'utilisateur en base (AppFixtures ou migration)
 */
interface BotStrategyInterface
{
    /**
     * Calcule et joue le meilleur coup possible pour le bot.
     *
     * @param Game            $game   La partie en cours
     * @param GameBoardHelper $helper Utilitaires purs du plateau (sans persistance)
     * @param GameEngine      $engine Moteur de jeu pour appeler playMove() et persister
     * @return Game|null La partie mise à jour, ou null si aucun coup disponible
     */
    public function makeMove(Game $game, GameBoardHelper $helper, GameEngine $engine): ?Game;
}
