<?php

namespace App\Service;

use App\Entity\Game;
use App\Entity\User;
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
        $terrain = $game->getGameBoard()->getTerrainLayout();
        $boardSize = $game->getGameBoard()->getBoardSize();

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
        $board = $this->handleCaptures($board, $toX, $toY, $piece, $terrain, $boardSize);

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
        $victory = $this->checkVictory($board, $terrain, $boardSize);

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
    private function handleCaptures(array $board, int $x, int $y, int $aggressorPiece, array $terrain, int $size): array
    {
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

            if (!$isVictimEnemy) {continue;}

            // RÈGLE SPÉCIALE ROI : Généralement le Roi ne se fait pas capturer par simple sandwich
            // sauf s'il est "faible" ou entouré de 4 côtés.
            // Pour simplifier ici : on interdit la capture du roi par sandwich simple.
            if ($victimPiece === self::KING) {continue;}

            // Vérifier l'Enclume (Le marteau est la pièce qu'on vient de bouger)
            $anvilPiece = $board[$anvilY][$anvilX];
            $anvilTerrain = $terrain[$anvilY][$anvilX];

            // Mon allié est-il sur l'enclume ?
            $isAnvilAlly = $isAttacker
                ? ($anvilPiece === self::ATTACKER)
                : ($anvilPiece === self::DEFENDER || $anvilPiece === self::KING);

            // Les coins et le trône (s'il est vide) comptent souvent comme "hostiles" pour capturer
            $isAnvilHostileStructure = ($anvilPiece === self::EMPTY) &&
                ($anvilTerrain === self::CELL_CORNER || $anvilTerrain === self::CELL_THRONE);

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
    private function checkVictory(array $board, array $terrain, int $size): ?string
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

        // 2. VICTOIRE DÉFENSEUR : Le Roi est sur un Coin
        if ($terrain[$kY][$kX] === self::CELL_CORNER) {
            return 'DEFENDER';
        }

        // 3. VICTOIRE ATTAQUANT : Le Roi est encerclé sur 4 côtés
        // On vérifie les 4 voisins du Roi
        $directions = [[0, 1], [0, -1], [1, 0], [-1, 0]];
        $surrounded = true;

        foreach ($directions as [$dx, $dy]) {
            $nx = $kX + $dx;
            $ny = $kY + $dy;

            // Si le Roi est au bord du plateau, il n'est pas "encerclé" (sauf variante spéciale)
            if ($nx < 0 || $nx >= $size || $ny < 0 || $ny >= $size) {
                $surrounded = false;
                break;
            }

            $neighborPiece = $board[$ny][$nx];

            // Le Roi est bloqué si le voisin est un Attaquant
            // OU si c'est le Trône (et qu'on considère le trône comme hostile)
            $isBlocker = ($neighborPiece === self::ATTACKER);

            if (!$isBlocker) {
                $surrounded = false;
                break;
            }
        }

        if ($surrounded) {
            return 'ATTACKER';
        }

        return null; // La partie continue
    }
}
