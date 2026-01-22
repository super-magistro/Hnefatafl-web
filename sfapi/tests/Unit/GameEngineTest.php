<?php

namespace App\Tests\Unit;

use App\Entity\Game;
use App\Entity\GameBoard;
use App\Entity\User;
use App\Service\GameEngine;
use PHPUnit\Framework\TestCase;

class GameEngineTest extends TestCase
{
    private GameEngine $engine;

    protected function setUp(): void
    {
        $this->engine = new GameEngine();
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
}
