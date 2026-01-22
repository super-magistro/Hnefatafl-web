<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Entity\Game;
use App\Entity\GameBoard;

class GamePlayControllerTest extends ApiTestCase
{
    private $entityManager;
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    // Helper pour créer une partie rapidement en base
    private function createGameInDb(): array
    {
        $attacker = new User();
        $attacker->setEmail('p1-' . uniqid() . '@test.com');
        $attacker->setPassword('pwd');
        $this->entityManager->persist($attacker);

        $defender = new User();
        $defender->setEmail('p2-' . uniqid() . '@test.com');
        $defender->setPassword('pwd');
        $this->entityManager->persist($defender);

        $board = new GameBoard();
        $board->setName('Plateau de Test');

        $board->setBoardSize(7);

        // Initialisation du plateau vide (7x7)
        $layout = array_fill(0, 7, array_fill(0, 7, 0));

        // --- CORRECTION ICI ---
        $layout[0][0] = 1; // Un Attaquant en haut à gauche
        $layout[3][3] = 3; // LE ROI au centre (Indispensable pour que la partie ne finisse pas tout de suite)
        // ----------------------

        $board->setInitialLayout($layout);
        $board->setTerrainLayout($layout); // On utilise le même layout pour le terrain pour simplifier ici
        $board->setRules([]);
        $this->entityManager->persist($board);

        $game = new Game();
        $game->setVariant('Brandubh');
        $game->setTimeControl('10+5');
        $game->setStatus('PLAYING');
        $game->setAttacker($attacker);
        $game->setDefender($defender);
        $game->setGameBoard($board);
        $game->setBoardState($layout);
        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return [$game, $attacker];
    }

    public function testPlayEndpointSuccess(): void
    {
        [$game, $attacker] = $this->createGameInDb();

        $this->client->loginUser($attacker);

        $this->client->request('POST', '/api/games/' . $game->getId() . '/play', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'from' => [0, 0],
                'to'   => [0, 1]
            ]
        ]);

        $this->assertResponseIsSuccessful();

        // Maintenant que le Roi est là, la partie continue et le statut reste PLAYING
        $this->assertJsonContains(['status' => 'PLAYING']);
    }

    public function testPlayEndpointBadRequest(): void
    {
        [$game, $attacker] = $this->createGameInDb();
        $this->client->loginUser($attacker);

        $this->client->request('POST', '/api/games/' . $game->getId() . '/play', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => [
                'from' => [0, 0]
                // "to" manque délibérément pour provoquer l'erreur 400
            ]
        ]);

        $this->assertResponseStatusCodeSame(400);
    }
}
