<?php

namespace App\Service;

use App\Entity\Game;

class GameEngine
{
    public function playTurn(Game $game): Game
    {
        // --- C'est ici que la magie opère ---

        // Exemple basique : on incrémente un tour
        // Supposons que tu aies une propriété 'turn' dans ton entité Game
        // $game->setTurn($game->getTurn() + 1);

        // Exemple : Logique de fin de partie
        // if ($game->getScore() > 10) {
        //     $game->setStatus('FINISHED');
        // }

        return $game;
    }
}
