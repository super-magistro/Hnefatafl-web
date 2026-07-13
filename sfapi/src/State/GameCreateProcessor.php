<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Game;
use App\Service\GameEngine;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GameCreateProcessor implements ProcessorInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private GameEngine $gameEngine,
        private EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        // 1. Sauvegarde initiale de la partie en base
        $result = $this->persistProcessor->process($data, $operation, $uriVariables, $context);

        if (!$result instanceof Game) {
            return $result;
        }

        // 2. Vérification si le Bot doit jouer le premier coup (s'il est attaquant)
        if ($result->getStatus() === 'PLAYING') {
            $movesCount = count($result->getMoves());
            $isAttackerTurn = ($movesCount % 2 === 0);
            $nextPlayer = $isAttackerTurn ? $result->getAttacker() : $result->getDefender();

            $botEmails = ['bot@hnefatafl.com', 'easy-bot@hnefatafl.com'];
            if ($nextPlayer && in_array($nextPlayer->getEmail(), $botEmails, true)) {
                try {
                    $this->gameEngine->makeBotMove($result, $nextPlayer->getEmail());
                    $this->entityManager->flush();
                } catch (\Exception $e) {
                    // Optionnellement logger l'erreur
                }
            }
        }

        return $result;
    }
}
