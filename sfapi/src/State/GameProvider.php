<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Config\GameRules;
use App\Entity\Game;
use App\Bot\GameBoardHelper;
use App\Service\EloCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class GameProvider implements ProviderInterface
{
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.item_provider')]
        private ProviderInterface $itemProvider,
        private GameBoardHelper $boardHelper,
        private EloCalculator $eloCalculator,
        private EntityManagerInterface $entityManager
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?Game
    {
        /** @var Game|null $game */
        $game = $this->itemProvider->provide($operation, $uriVariables, $context);

        if (!$game || $game->getStatus() !== 'PLAYING') {
            return $game;
        }

        $gameBoard = $game->getGameBoard();
        if (!$gameBoard) {
            return $game;
        }

        $board = $game->getBoardState() ?: $gameBoard->getInitialLayout();
        $terrain = $gameBoard->getTerrainLayout();
        $boardSize = $gameBoard->getBoardSize();
        $rules = $gameBoard->getRules() ?: GameRules::getRulesForVariant($game->getVariant() ?? '');

        $movesCount = count($game->getMoves());
        $isAttackerTurn = ($movesCount % 2 === 0);

        $victory = $this->boardHelper->checkVictory($board, $terrain, $boardSize, $rules, $isAttackerTurn);
        if ($victory !== null) {
            $game->setStatus('FINISHED');
            $game->setWinner($victory === 'ATTACKER' ? $game->getAttacker() : $game->getDefender());
            $this->eloCalculator->updateEloForFinishedGame($game);
            $this->entityManager->flush();
        }

        return $game;
    }
}
