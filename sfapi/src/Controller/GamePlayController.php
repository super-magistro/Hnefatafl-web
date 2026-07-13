<?php

namespace App\Controller;

use App\Entity\Game;
use App\Service\GameEngine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response; // <--- Import nécessaire
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
class GamePlayController extends AbstractController
{
    public function __construct(
        private GameEngine $gameEngine,
        private EntityManagerInterface $entityManager
    ) {}

    // Changement du type de retour : Game -> Response
    public function __invoke(Request $request, Game $data): Response
    {
        $content = json_decode($request->getContent(), true);

        if (!isset($content['from']) || !isset($content['to'])) {
            throw new BadRequestHttpException('Données manquantes. Format attendu: {"from": [x,y], "to": [x,y]}');
        }

        $from = $content['from'];
        $to = $content['to'];

        try {
            $this->gameEngine->playMove($data, $from, $to, $this->getUser());
        } catch (\Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $this->entityManager->flush();

        // Si le joueur suivant est le Bot, on le fait jouer immédiatement dans la foulée
        $movesCount = count($data->getMoves());
        $isAttackerTurn = ($movesCount % 2 === 0);
        $nextPlayer = $isAttackerTurn ? $data->getAttacker() : $data->getDefender();

        $botEmails = ['bot@hnefatafl.com', 'easy-bot@hnefatafl.com'];
        if ($nextPlayer && in_array($nextPlayer->getEmail(), $botEmails, true)) {
            try {
                $this->gameEngine->makeBotMove($data, $nextPlayer->getEmail());
                $this->entityManager->flush();
            } catch (\Exception $e) {
                // Optionnellement logger l'erreur
            }
        }

        // On retourne une réponse JSON propre
        return $this->json($data);
    }
}
