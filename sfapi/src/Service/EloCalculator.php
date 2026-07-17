<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;

/**
 * Service de calcul du classement ELO.
 *
 * Applique la formule officielle FIDE :
 * Nouveau ELO = Ancien ELO + K * (ScoreRéel - ScoreAttendu)
 */
class EloCalculator
{
    private const K_FACTOR = 32;

    /**
     * Calcule et met à jour les scores ELO des joueurs à la fin d'une partie.
     */
    public function updateEloForFinishedGame(Game $game): void
    {
        $attacker = $game->getAttacker();
        $defender = $game->getDefender();
        $winner = $game->getWinner();

        // On ne calcule l'Elo que si les deux joueurs et le vainqueur sont définis
        if (!$attacker || !$defender || !$winner) {
            return;
        }

        $eloA = $attacker->getElo() ?? 1200;
        $eloD = $defender->getElo() ?? 1200;

        // Étape 1 : Calcul de l'espérance de score (probabilité attendue)
        $expectedA = 1.0 / (1.0 + pow(10.0, ($eloD - $eloA) / 400.0));
        $expectedD = 1.0 / (1.0 + pow(10.0, ($eloA - $eloD) / 400.0));

        // Étape 2 : Détermination du score réel
        $scoreA = ($winner->getId() === $attacker->getId()) ? 1.0 : 0.0;
        $scoreD = ($winner->getId() === $defender->getId()) ? 1.0 : 0.0;

        // Étape 3 : Ajustement de l'Elo
        $newEloA = round($eloA + self::K_FACTOR * ($scoreA - $expectedA));
        $newEloD = round($eloD + self::K_FACTOR * ($scoreD - $expectedD));

        // Mise à jour des entités
        $attacker->setElo((int)$newEloA);
        $defender->setElo((int)$newEloD);
    }
}
