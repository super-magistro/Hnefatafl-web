<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Controller\GamePlayController;
use App\Controller\GameResignController;
use App\Repository\GameRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/api/games',
            description: 'Lister les parties'
        ),
        new Get(
            uriTemplate: '/api/games/{id}',
            description: 'Voir une partie, rafraichir le plateau'
        ),
        new Post(
            uriTemplate: 'api/games',
            description: 'Créer une partie'
        ),
        //Actions en temps réel
        new Post(
            uriTemplate: '/games/{id}/play',
            stateless: false,
            controller: GamePlayController::class,
            description: 'Jouer un coup',
            read: true,
            write: false,
            name: 'play_turn'
        ),
        new Post(
            uriTemplate: '/games/{id}/resign',
            stateless: false,
            controller: GameResignController::class,
            description: 'Abandonner',
            read: true,
            write: false,
            name: 'game_resign'
        )
    ]
)]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'gamesAsAttacker')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $attacker = null;

    #[ORM\ManyToOne(inversedBy: 'gamesAsDefender')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $defender = null;

    #[ORM\ManyToOne]
    private ?User $winner = null;

    #[ORM\Column(length: 50)]
    private ?string $variant = null; // 'Hnefatafl', 'Tablut', etc.

    #[ORM\Column(length: 20)]
    private ?string $timeControl = null; // Ex: '10+5'

    // On utilise JSON pour stocker l'état du plateau (tableau de tableaux ou strings)
    #[ORM\Column(type: Types::JSON)]
    private array $boardState = [];

    #[ORM\Column(length: 50)]
    private ?string $status = 'PENDING'; // 'PENDING', 'PLAYING', 'FINISHED'

    /**
     * Stocke la liste des coups. Ex: ["A1-A5", "E5-E8"]
     */
    #[ORM\Column(type: Types::JSON)]
    private array $moves = [];

    #[ORM\Column(nullable: true)]
    private ?int $attackerTimeLeft = null;

    #[ORM\Column(nullable: true)]
    private ?int $defenderTimeLeft = null;

    #[ORM\ManyToOne(inversedBy: 'games')]
    #[ORM\JoinColumn(nullable: false)]
    private ?GameBoard $gameBoard = null;


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAttacker(): ?User
    {
        return $this->attacker;
    }

    public function setAttacker(?User $attacker): static
    {
        $this->attacker = $attacker;
        return $this;
    }

    public function getDefender(): ?User
    {
        return $this->defender;
    }

    public function setDefender(?User $defender): static
    {
        $this->defender = $defender;
        return $this;
    }

    public function getWinner(): ?User
    {
        return $this->winner;
    }

    public function setWinner(?User $winner): static
    {
        $this->winner = $winner;
        return $this;
    }

    public function getBoardState(): array
    {
        return $this->boardState;
    }

    public function setBoardState(array $boardState): static
    {
        $this->boardState = $boardState;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function setVariant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function getTimeControl(): ?string
    {
        return $this->timeControl;
    }

    public function setTimeControl(?string $timeControl): static
    {
        $this->timeControl = $timeControl;
        return $this;
    }

    public function getMoves(): array
    {
        return $this->moves;
    }

    public function setMoves(array $moves): static
    {
        $this->moves = $moves;
        return $this;
    }

    public function getAttackerTimeLeft(): ?int
    {
        return $this->attackerTimeLeft;
    }

    public function setAttackerTimeLeft(?int $attackerTimeLeft): static
    {
        $this->attackerTimeLeft = $attackerTimeLeft;
        return $this;
    }

    public function getDefenderTimeLeft(): ?int
    {
        return $this->defenderTimeLeft;
    }

    public function setDefenderTimeLeft(?int $defenderTimeLeft): static
    {
        $this->defenderTimeLeft = $defenderTimeLeft;
        return $this;
    }

    public function getGameBoard(): ?GameBoard
    {
        return $this->gameBoard;
    }

    public function setGameBoard(?GameBoard $gameBoard): static
    {
        $this->gameBoard = $gameBoard;

        return $this;
    }
}
