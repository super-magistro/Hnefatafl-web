<?php

namespace App\Tests\Api;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use App\Entity\User;
use App\Entity\Game;
use App\Entity\GameBoard;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class GamePlayControllerTest extends ApiTestCase
{
    // On utilise le Trait qu'on vient de créer pour gérer le JWT
    use AuthenticationTestTrait;

    private $entityManager;
    private $passwordHasher;

    protected function setUp(): void
    {
        $this->entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->passwordHasher = self::getContainer()->get('security.user_password_hasher');
    }

    // Helper pour créer une partie rapidement en base
    private function createGameInDb(): array
    {
        // 1. Création des utilisateurs avec MOT DE PASSE HACHÉ
        // (Sinon le login via API échouera car il compare le hash)
        $password = 'password123';

        $attacker = new User();
        $attackerEmail = 'p1-' . uniqid() . '@test.com';
        $attacker->setEmail($attackerEmail);
        $attacker->setPassword($this->passwordHasher->hashPassword($attacker, $password));
        $this->entityManager->persist($attacker);

        $defender = new User();
        $defender->setEmail('p2-' . uniqid() . '@test.com');
        $defender->setPassword($this->passwordHasher->hashPassword($defender, $password));
        $this->entityManager->persist($defender);

        // 2. Création du Plateau
        $board = new GameBoard();
        $board->setName('Plateau de Test');
        $board->setBoardSize(7);

        // Initialisation du plateau (Attaquant en 0,0 et Roi en 3,3)
        $layout = array_fill(0, 7, array_fill(0, 7, 0));
        $layout[0][0] = 1; // Attaquant
        $layout[3][3] = 3; // Roi

        $board->setInitialLayout($layout);
        $board->setTerrainLayout($layout);
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

        return [$game, $attackerEmail, $password];
    }

    public function testPlayEndpointSuccess(): void
    {
        [$game, $email, $password] = $this->createGameInDb();

        $client = $this->createClientWithCredentials($email, $password);

        $client->request('POST', '/api/games/' . $game->getId() . '/play', [
            'json' => [
                'from' => [0, 0],
                'to'   => [0, 1]
            ]
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['status' => 'PLAYING']);
    }

    public function testPlayEndpointBadRequest(): void
    {
        [$game, $email, $password] = $this->createGameInDb();

        // Connexion JWT
        $client = $this->createClientWithCredentials($email, $password);

        $client->request('POST', '/api/games/' . $game->getId() . '/play', [
            'json' => [
                'from' => [0, 0]
                // "to" manque pour provoquer l'erreur
            ]
        ]);

        $this->assertResponseStatusCodeSame(400);
    }
}
