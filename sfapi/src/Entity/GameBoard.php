<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\GameBoardRepository;
use App\Security\SecureRules;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameBoardRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/game_boards',
            description: 'Lister les variantes disponibles',
            security: SecureRules::PUBLIC_ACCESS
        ),
        new Get(
            uriTemplate: '/game_boards/{id}',
            description: 'Récupérer les layouts initiaux et dessiner le plateau',
            security: SecureRules::PUBLIC_ACCESS
        ),

        // --- ADMINISTRATION (Création de variantes) ---
        new Post(
            uriTemplate: '/game_boards',
            description: 'Créer une nouvelle variante de plateau',
            security: SecureRules::ADMIN_ONLY,
            securityMessage: SecureRules::MSG_ADMIN_ONLY
        ),
        new Patch(
            uriTemplate: '/game_boards/{id}',
            security: SecureRules::ADMIN_ONLY,
            securityMessage: SecureRules::MSG_ADMIN_ONLY
        ),
        new Delete(
            uriTemplate: '/game_boards/{id}',
            security: SecureRules::ADMIN_ONLY,
            securityMessage: SecureRules::MSG_ADMIN_ONLY
        ),
    ]
)]
class GameBoard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $boardSize = null;

    #[ORM\Column]
    private array $initialLayout = [];

    #[ORM\Column]
    private array $terrainLayout = [];

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'gameBoard')]
    private Collection $games;

    /**
     * Configuration des règles spécifiques de la variante (Format JSON).
     *
     * Ce tableau définit comment le moteur de jeu doit interpréter les captures et la victoire.
     *
     * 1. CONDITIONS DE VICTOIRE
     * - "winCondition" (string) :
     * * "CORNER" : Le Roi doit atteindre un des 4 coins (ex: Hnefatafl, Brandubh).
     * * "EDGE"   : Le Roi doit atteindre n'importe quelle case du bord (ex: Tablut).
     *
     * 2. RÈGLES DU ROI (CAPTURE & COMBAT)
     * - "kingCapture" (string) :
     * * "4_SIDES" : Roi "Fort". Il faut l'encercler sur les 4 côtés (ou 3 + Trône/Bord).
     * * "2_SIDES" : Roi "Faible". Il est capturé en sandwich comme un soldat normal.
     * - "kingWeapon" (string) :
     * * "ARMED" : Le Roi participe aux captures (il sert d'enclume).
     * * "WEAK"  : Le Roi ne peut pas capturer (il ne sert pas d'enclume).
     *
     * 3. HOSTILITÉ DU TERRAIN (Pour les captures)
     * - "cornerIsHostile" (bool) : Un coin vide compte-t-il comme un allié pour capturer (enclume) ?
     * - "throneIsHostile" (bool) : Le trône vide compte-t-il comme un allié pour capturer ?
     *
     * 4. RÈGLES SPÉCIALES (Viking Moderne / Copenhagen)
     * - "shieldWall" (bool) : Active la capture multiple sur les bords (Mur de boucliers).
     * - "exitForts" (bool)  : Permet au Roi de forcer la sortie d'un encerclement sur le bord.
     *
     * @example {"winCondition": "CORNER", "kingCapture": "4_SIDES", "cornerIsHostile": true, "kingWeapon": "ARMED"}
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rules = null;

    public function __construct()
    {
        $this->games = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBoardSize(): ?int
    {
        return $this->boardSize;
    }

    public function setBoardSize(int $boardSize): static
    {
        $this->boardSize = $boardSize;

        return $this;
    }

    public function getInitialLayout(): array
    {
        return $this->initialLayout;
    }

    public function setInitialLayout(array $initialLayout): static
    {
        $this->initialLayout = $initialLayout;

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(Game $game): static
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
            $game->setGameBoard($this);
        }

        return $this;
    }

    public function removeGame(Game $game): static
    {
        if ($this->games->removeElement($game)) {
            // set the owning side to null (unless already changed)
            if ($game->getGameBoard() === $this) {
                $game->setGameBoard(null);
            }
        }

        return $this;
    }

    public function getTerrainLayout(): array
    {
        return $this->terrainLayout;
    }

    public function setTerrainLayout(array $terrainLayout): static
    {
        $this->terrainLayout = $terrainLayout;

        return $this;
    }

    public function getRules(): ?array
    {
        return $this->rules;
    }

    public function setRules(?array $rules): static
    {
        $this->rules = $rules;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }
}
