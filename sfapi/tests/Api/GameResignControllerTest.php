<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Entity\Game;
use App\Entity\GameBoard;

class GameResignControllerTest extends ApiTestCase
{
    private $entityManager;
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
    }

    private function createGameInDb(): array
    {
        // 1. Création des joueurs
        $attacker = new User();
        $attacker->setEmail('p1-resign-' . uniqid() . '@test.com');
        $attacker->setPassword('pwd');
        $this->entityManager->persist($attacker);

        $defender = new User();
        $defender->setEmail('p2-resign-' . uniqid() . '@test.com');
        $defender->setPassword('pwd');
        $this->entityManager->persist($defender);

        // 2. Création du plateau (C'est ici que ça plantait : manque de Name)
        $board = new GameBoard();
        $board->setName('Plateau Resign'); // <--- CORRECTION 1 : Le nom est obligatoire
        $board->setBoardSize(7);

        // Mise en place des pièces (Roi obligatoire pour ne pas finir la partie tout de suite)
        $layout = array_fill(0, 7, array_fill(0, 7, 0));
        $layout[0][0] = 1;
        $layout[3][3] = 3; // <--- CORRECTION 2 : Le Roi

        $board->setInitialLayout($layout);
        $board->setTerrainLayout($layout);
        $board->setRules([]);
        $this->entityManager->persist($board);

        // 3. Création de la partie
        $game = new Game();
        $game->setVariant('Brandubh');     // <--- CORRECTION 3 : Obligatoire
        $game->setTimeControl('10+5');     // <--- CORRECTION 4 : Obligatoire
        $game->setStatus('PLAYING');
        $game->setAttacker($attacker);
        $game->setDefender($defender);
        $game->setGameBoard($board);
        $game->setBoardState($layout);

        $this->entityManager->persist($game);
        $this->entityManager->flush();

        return [$game, $attacker, $defender];
    }

    public function testResignAsAttacker(): void
    {
        [$game, $attacker] = $this->createGameInDb();

        $this->client->loginUser($attacker);

        $this->client->request('POST', '/api/games/' . $game->getId() . '/resign', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => []
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'FINISHED']);
    }

    public function testResignUnauthorized(): void
    {
        [$game] = $this->createGameInDb();

        // On crée un 3ème joueur qui n'est pas dans la partie
        $outsider = new User();
        $outsider->setEmail('outsider-' . uniqid() . '@test.com');
        $outsider->setPassword('pwd');
        $this->entityManager->persist($outsider);
        $this->entityManager->flush();

        $this->client->loginUser($outsider);

        $this->client->request('POST', '/api/games/' . $game->getId() . '/resign', [
            'headers' => ['Content-Type' => 'application/ld+json'],
            'json' => []
        ]);

        // Il doit recevoir une erreur 400 (Bad Request) ou 403 (Forbidden) selon votre contrôleur
        // Votre contrôleur renvoie BadRequestHttpException donc 400
        $this->assertResponseStatusCodeSame(400);
    }
}
