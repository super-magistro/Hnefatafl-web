<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/api/users',
            description: 'Inscription (Créer un compte)'
        ),
        new Get(
            uriTemplate: '/api/users/{id}',
            description: 'Voir le profil(Elo, Pseudo)'
        ),
        new Get(
            uriTemplate: '/api/users/me',
            description: 'Voir son profil (avec email)'
        ),
        new Post(
            uriTemplate: '/api/login',
            description: 'Se connecter'
        ),
    ]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'playerAttacker')]
    private Collection $gamesAsAttacker;

    /**
     * @var Collection<int, Game>
     */
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'playerDefender')]
    private Collection $gamesAsDefender;

    #[ORM\Column]
    private ?int $elo = null;

    public function __construct()
    {
        $this->gamesAsAttacker = new ArrayCollection();
        $this->gamesAsDefender = new ArrayCollection();
        $this->elo = 1200;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGamesAsAttacker(): Collection
    {
        return $this->gamesAsAttacker;
    }

    public function addGamesAsAttacker(Game $gamesAsAttacker): static
    {
        if (!$this->gamesAsAttacker->contains($gamesAsAttacker)) {
            $this->gamesAsAttacker->add($gamesAsAttacker);
            $gamesAsAttacker->setPlayerAttacker($this);
        }

        return $this;
    }

    public function removeGamesAsAttacker(Game $gamesAsAttacker): static
    {
        if ($this->gamesAsAttacker->removeElement($gamesAsAttacker)) {
            // set the owning side to null (unless already changed)
            if ($gamesAsAttacker->getPlayerAttacker() === $this) {
                $gamesAsAttacker->setPlayerAttacker(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Game>
     */
    public function getGamesAsDefender(): Collection
    {
        return $this->gamesAsDefender;
    }

    public function addGamesAsDefender(Game $gamesAsDefender): static
    {
        if (!$this->gamesAsDefender->contains($gamesAsDefender)) {
            $this->gamesAsDefender->add($gamesAsDefender);
            $gamesAsDefender->setPlayerDefender($this);
        }

        return $this;
    }

    public function removeGamesAsDefender(Game $gamesAsDefender): static
    {
        if ($this->gamesAsDefender->removeElement($gamesAsDefender)) {
            // set the owning side to null (unless already changed)
            if ($gamesAsDefender->getPlayerDefender() === $this) {
                $gamesAsDefender->setPlayerDefender(null);
            }
        }

        return $this;
    }

    public function getElo(): ?int
    {
        return $this->elo;
    }

    public function setElo(int $elo): static
    {
        $this->elo = $elo;

        return $this;
    }
}
