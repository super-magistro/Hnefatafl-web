<?php

namespace App\Tests\Unit;

use App\Bot\GameBoardHelper;
use App\Entity\Game;
use App\Entity\GameBoard;
use App\Entity\User;
use App\Service\EloCalculator;
use App\Service\GameEngine;
use PHPUnit\Framework\TestCase;

class GameEngineTest extends TestCase
{
    private GameEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new GameEngine(new GameBoardHelper(), new EloCalculator());
    }

    public function testMovePieceValid(): void
    {
        // 1. Préparation (Mocking léger)
        $game = new Game();
        $board = new GameBoard();
        $board->setBoardSize(7);
        $board->setTerrainLayout(array_fill(0, 7, array_fill(0, 7, 0))); // Terrain vide
        $game->setGameBoard($board);

        // Attaquant (1) en [0,0], case vide en [0,1]
        $initialBoard = array_fill(0, 7, array_fill(0, 7, 0));
        $initialBoard[0][0] = 1;
        $game->setBoardState($initialBoard);

        // On simule que c'est le tour de l'attaquant (0 coups joués)
        $game->setMoves([]);

        // 2. Action : On bouge de [0,0] vers [0,1]
        $this->engine->playMove($game, [0, 0], [0, 1], null);

        // 3. Assertion
        $newBoard = $game->getBoardState();
        $this->assertEquals(0, $newBoard[0][0], "La case de départ doit être vide");
        $this->assertEquals(1, $newBoard[0][1], "La case d'arrivée doit contenir la pièce");
    }

    public function testMoveForbiddenObstacle(): void
    {
        $game = new Game();
        $board = new GameBoard();
        $board->setBoardSize(7);
        $board->setTerrainLayout(array_fill(0, 7, array_fill(0, 7, 0)));
        $game->setGameBoard($board);

        // Attaquant en [0,0], Obstacle en [0,1], on veut aller en [0,2]
        $initialBoard = array_fill(0, 7, array_fill(0, 7, 0));
        $initialBoard[0][0] = 1;
        $initialBoard[0][1] = 1; // Obstacle
        $game->setBoardState($initialBoard);
        $game->setMoves([]);

        // On s'attend à une exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Le chemin est bloqué");

        $this->engine->playMove($game, [0, 0], [0, 2], null);
    }

    public function testCaptureSandwich(): void
    {
        $game = new Game();
        $board = new GameBoard();
        $board->setBoardSize(7);
        $board->setTerrainLayout(array_fill(0, 7, array_fill(0, 7, 0)));
        $board->setRules(['winCondition' => 'CORNER']); // Règles par défaut
        $game->setGameBoard($board);

        // Situation de prise en sandwich
        // [1] [2] [ ]  <-- On va bouger un [1] sur la case vide pour faire [1][2][1]
        $initialBoard = array_fill(0, 7, array_fill(0, 7, 0));
        $initialBoard[0][0] = 1; // Attaquant gauche
        $initialBoard[0][1] = 2; // Victime (Défenseur)
        $initialBoard[0][3] = 1; // Attaquant qui va bouger (est en col 3)

        $game->setBoardState($initialBoard);
        $game->setMoves([]); // Tour Attaquant

        // Action : L'attaquant en [0,3] vient en [0,2]
        $this->engine->playMove($game, [0, 3], [0, 2], null);

        // Vérification : La pièce en [0,1] doit avoir disparu (0)
        $newBoard = $game->getBoardState();
        $this->assertEquals(0, $newBoard[0][1], "Le défenseur pris en sandwich doit être capturé");
    }

    public function testVictoryEdge(): void
    {
        $game = new Game();
        $attacker = new User();
        $defender = new User();
        $game->setAttacker($attacker);
        $game->setDefender($defender);

        $board = new GameBoard();
        $board->setBoardSize(7);
        $board->setTerrainLayout(array_fill(0, 7, array_fill(0, 7, 0)));
        // Règle de victoire sur les bords
        $board->setRules([
            \App\Config\GameRules::KEY_WIN_CONDITION => \App\Config\GameRules::WIN_EDGE,
        ]);
        $game->setGameBoard($board);
        $game->setVariant('Tablut');

        // Le Roi (3) est en [1,1], il va bouger en [0,1] qui est sur le bord
        $initialBoard = array_fill(0, 7, array_fill(0, 7, 0));
        $initialBoard[1][1] = 3;
        $game->setBoardState($initialBoard);
        $game->setMoves(['A1-A2']); // Déjà 1 coup (Tour Défenseur car nombre de coups impair)

        // Action : On déplace le Roi sur le bord
        $this->engine->playMove($game, [1, 1], [0, 1], null);

        // Assertions
        $this->assertEquals('FINISHED', $game->getStatus(), "La partie doit être terminée");
        $this->assertEquals($defender, $game->getWinner(), "Le vainqueur doit être le défenseur");
    }

    public function testKingCapture2Sides(): void
    {
        $game = new Game();
        $attacker = new User();
        $defender = new User();
        $game->setAttacker($attacker);
        $game->setDefender($defender);

        $board = new GameBoard();
        $board->setBoardSize(7);
        $board->setTerrainLayout(array_fill(0, 7, array_fill(0, 7, 0)));
        // Roi faible (capturable à 2 côtés)
        $board->setRules([
            \App\Config\GameRules::KEY_KING_CAPTURE => \App\Config\GameRules::CAPTURE_2_SIDES,
        ]);
        $game->setGameBoard($board);

        // Roi en [1,1] pris en sandwich verticalement par un Attaquant en [0,1] et un autre qui va en [2,1]
        $initialBoard = array_fill(0, 7, array_fill(0, 7, 0));
        $initialBoard[0][1] = 1; // Attaquant du haut
        $initialBoard[1][1] = 3; // Roi au milieu
        $initialBoard[2][2] = 1; // Attaquant qui va bouger en [2,1]
        $game->setBoardState($initialBoard);
        $game->setMoves([]); // Tour Attaquant

        // Action : Déplacement de l'attaquant pour faire le sandwich
        $this->engine->playMove($game, [2, 2], [2, 1], null);

        // Assertions
        $this->assertEquals('FINISHED', $game->getStatus(), "La partie doit être terminée");
        $this->assertEquals($attacker, $game->getWinner(), "Le vainqueur doit être l'attaquant");
    }

    public function testKingCapture4SidesOnThrone(): void
    {
        $game = new Game();
        $attacker = new User();
        $defender = new User();
        $game->setAttacker($attacker);
        $game->setDefender($defender);

        $board = new GameBoard();
        $board->setBoardSize(7);
        // Trône en [3,3]
        $terrain = array_fill(0, 7, array_fill(0, 7, 0));
        $terrain[3][3] = 1; // CELL_THRONE
        $board->setTerrainLayout($terrain);
        // Roi fort
        $board->setRules([
            \App\Config\GameRules::KEY_KING_CAPTURE => \App\Config\GameRules::CAPTURE_4_SIDES,
        ]);
        $game->setGameBoard($board);

        // Roi sur le trône [3,3]
        // Entouré de 3 attaquants en [2,3], [4,3], [3,2]. Le 4ème va bouger de [3,5] vers [3,4].
        $initialBoard = array_fill(0, 7, array_fill(0, 7, 0));
        $initialBoard[3][3] = 3; // Roi
        $initialBoard[2][3] = 1; // Attaquant Haut
        $initialBoard[4][3] = 1; // Attaquant Bas
        $initialBoard[3][2] = 1; // Attaquant Gauche
        $initialBoard[3][5] = 1; // Attaquant qui va bouger en [3,4] (Droite)
        $game->setBoardState($initialBoard);
        $game->setMoves([]);

        // Action
        $this->engine->playMove($game, [3, 5], [3, 4], null);

        // Assertion
        $this->assertEquals('FINISHED', $game->getStatus(), "Le Roi fort doit être capturé sur le trône s'il est entouré de 4 attaquants");
        $this->assertEquals($attacker, $game->getWinner(), "Le vainqueur doit être l'attaquant");
    }

    public function testUnarmedKingCannotCapture(): void
    {
        $game = new Game();
        $board = new GameBoard();
        $board->setBoardSize(7);
        $board->setTerrainLayout(array_fill(0, 7, array_fill(0, 7, 0)));
        // Roi désarmé (unarmed)
        $board->setRules([
            \App\Config\GameRules::KEY_KING_WEAPON => \App\Config\GameRules::KING_UNARMED,
        ]);
        $game->setGameBoard($board);

        // Roi en [0,3] va en [0,2] pour prendre en sandwich l'attaquant en [0,1] avec le défenseur en [0,0]
        $initialBoard = array_fill(0, 7, array_fill(0, 7, 0));
        $initialBoard[0][0] = 2; // Défenseur
        $initialBoard[0][1] = 1; // Attaquant (cible)
        $initialBoard[0][3] = 3; // Roi désarmé qui va bouger en [0,2]
        $game->setBoardState($initialBoard);
        $game->setMoves(['A1-A2']); // Tour Défenseur

        // Action
        $this->engine->playMove($game, [0, 3], [0, 2], null);

        // Assertion : La cible en [0,1] ne doit PAS être capturée car le Roi est unarmed
        $newBoard = $game->getBoardState();
        $this->assertEquals(1, $newBoard[0][1], "L'attaquant ne doit pas être capturé par un Roi désarmé");
    }

    public function testKingCapture4SidesAdjacentToThrone(): void
    {
        $game = new Game();
        $attacker = new User();
        $defender = new User();
        $game->setAttacker($attacker);
        $game->setDefender($defender);

        $board = new GameBoard();
        $board->setBoardSize(7);
        // Trône en [3,3]
        $terrain = array_fill(0, 7, array_fill(0, 7, 0));
        $terrain[3][3] = 1; // CELL_THRONE
        $board->setTerrainLayout($terrain);
        // Roi fort
        $board->setRules([
            \App\Config\GameRules::KEY_KING_CAPTURE => \App\Config\GameRules::CAPTURE_4_SIDES,
        ]);
        $game->setGameBoard($board);

        // Roi adjacent au trône en [3,2]. Le trône [3,3] est vide (bloqueur 1).
        // Ses 3 autres côtés sont : Haut [2,2], Bas [4,2], Gauche [3,1].
        // Les attaquants sont déjà en [2,2] (Haut) et [4,2] (Bas). L'attaquant en [3,0] va bouger en [3,1] (Gauche).
        $initialBoard = array_fill(0, 7, array_fill(0, 7, 0));
        $initialBoard[3][2] = 3; // Roi
        $initialBoard[2][2] = 1; // Attaquant Haut
        $initialBoard[4][2] = 1; // Attaquant Bas
        $initialBoard[3][0] = 1; // Attaquant qui va bouger en [3,1] (Gauche)
        $game->setBoardState($initialBoard);
        $game->setMoves([]);

        // Action
        $this->engine->playMove($game, [3, 0], [3, 1], null);

        // Assertion
        $this->assertEquals('FINISHED', $game->getStatus(), "Le Roi fort doit être capturé s'il est adjacent au trône vide et entouré sur ses 3 autres côtés libres");
        $this->assertEquals($attacker, $game->getWinner(), "Le vainqueur doit être l'attaquant");
    }

    public function testThroneHostilityAlways(): void
    {
        $game = new Game();
        $board = new GameBoard();
        $board->setBoardSize(7);
        $terrain = array_fill(0, 7, array_fill(0, 7, 0));
        $terrain[3][3] = 1; // CELL_THRONE
        $board->setTerrainLayout($terrain);
        $board->setRules([
            \App\Config\GameRules::KEY_THRONE_HOSTILITY => \App\Config\GameRules::THRONE_ALWAYS_HOSTILE,
        ]);
        $game->setGameBoard($board);

        // Trône en [3,3] vide. Défenseur en [3,2].
        // Un attaquant en [3,0] se déplace en [3,1] pour prendre en sandwich le défenseur contre le trône.
        $initialBoard = array_fill(0, 7, array_fill(0, 7, 0));
        $initialBoard[3][2] = 2; // Défenseur
        $initialBoard[3][0] = 1; // Attaquant
        $game->setBoardState($initialBoard);
        $game->setMoves([]);

        // Action
        $this->engine->playMove($game, [3, 0], [3, 1], null);

        // Assertion : Le défenseur en [3,2] doit être capturé
        $newBoard = $game->getBoardState();
        $this->assertEquals(0, $newBoard[3][2], "Le défenseur doit être capturé contre le trône avec la règle 'always'");
    }

    public function testThroneHostilityNever(): void
    {
        $game = new Game();
        $board = new GameBoard();
        $board->setBoardSize(7);
        $terrain = array_fill(0, 7, array_fill(0, 7, 0));
        $terrain[3][3] = 1; // CELL_THRONE
        $board->setTerrainLayout($terrain);
        $board->setRules([
            \App\Config\GameRules::KEY_THRONE_HOSTILITY => \App\Config\GameRules::THRONE_NEVER_HOSTILE,
        ]);
        $game->setGameBoard($board);

        // Trône en [3,3] vide. Défenseur en [3,2].
        // Un attaquant en [3,0] se déplace en [3,1] pour essayer de prendre en sandwich le défenseur contre le trône.
        $initialBoard = array_fill(0, 7, array_fill(0, 7, 0));
        $initialBoard[3][2] = 2; // Défenseur
        $initialBoard[3][0] = 1; // Attaquant
        $game->setBoardState($initialBoard);
        $game->setMoves([]);

        // Action
        $this->engine->playMove($game, [3, 0], [3, 1], null);

        // Assertion : Le défenseur en [3,2] ne doit PAS être capturé
        $newBoard = $game->getBoardState();
        $this->assertEquals(2, $newBoard[3][2], "Le défenseur ne doit pas être capturé contre le trône avec la règle 'never'");
    }
}
