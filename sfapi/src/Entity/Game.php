<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\GameRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRepository::class)]
#[ApiResource]
class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'gamesAsAttacker')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $playerAttacker = null;

    #[ORM\ManyToOne(inversedBy: 'gamesAsDefender')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $playerDefender = null;

    #[ORM\ManyToOne]
    private ?User $winner = null;

    #[ORM\Column]
    private array $boardState = [];

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    private ?string $variant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayerAttacker(): ?User
    {
        return $this->playerAttacker;
    }

    public function setPlayerAttacker(?User $playerAttacker): static
    {
        $this->playerAttacker = $playerAttacker;

        return $this;
    }

    public function getPlayerDefender(): ?User
    {
        return $this->playerDefender;
    }

    public function setPlayerDefender(?User $playerDefender): static
    {
        $this->playerDefender = $playerDefender;

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
}
