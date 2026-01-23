<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\UserRepository;
use App\Security\SecureRules;
use App\State\UserPasswordHasher;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ApiResource(
    // 1. Groupes de sérialisation par défaut
    operations: [
        new GetCollection(
            security: SecureRules::USER_READ,
            securityMessage: SecureRules::MSG_USER_READ
        ),
        new Get(
            security: SecureRules::USER_READ,
            securityMessage: SecureRules::MSG_USER_READ
        ),

        // --- ÉCRITURE (Inscription) ---
        new Post(
            uriTemplate: '/users',
            security: SecureRules::PUBLIC_ACCESS, // Tout le monde peut s'inscrire
            validationContext: ['groups' => ['Default', 'user:create']],
            processor: UserPasswordHasher::class // <--- C'est lui qui crypte le mot de passe !
        ),

        // --- MODIFICATION (Profil) ---
        new Patch(
            security: SecureRules::USER_EDIT, // Seul le propriétaire peut modifier
            securityMessage: SecureRules::MSG_USER_EDIT,
            processor: UserPasswordHasher::class
        ),

        // --- SUPPRESSION (Admin) ---
        new Delete(
            security: SecureRules::ADMIN_ONLY,
            securityMessage: SecureRules::MSG_ADMIN_ONLY
        )
    ],
    normalizationContext: ['groups' => ['user:read']],

    denormalizationContext: ['groups' => ['user:write']]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read'])] // L'ID est visible
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write', 'user:create'])] // Email visible et modifiable
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user:read'])] // On voit les rôles, mais on ne les modifie pas directement
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    // PAS DE GROUPS ICI ! On ne montre jamais le hash.
    private ?string $password = null;

    /**
     * Champ virtuel pour le mot de passe en clair (Inscription / Modif)
     */
    #[Groups(['user:write', 'user:create'])] // On peut l'envoyer pour créer le compte
    private ?string $plainPassword = null;

    #[ORM\Column]
    #[Groups(['user:read'])]
    private ?int $elo = null;

    /**
     * @var Collection<int, Game>
     */
    // Correction ici : 'attacker' (le vrai nom dans Game.php)
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'attacker')]
    private Collection $gamesAsAttacker;

    /**
     * @var Collection<int, Game>
     */
    // Correction ici : 'defender'
    #[ORM\OneToMany(targetEntity: Game::class, mappedBy: 'defender')]
    private Collection $gamesAsDefender;

    public function __construct()
    {
        $this->gamesAsAttacker = new ArrayCollection();
        $this->gamesAsDefender = new ArrayCollection();
        $this->elo = 1200;
        $this->setRoles(['ROLE_USER', 'ROLE_USER_EDIT']);
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

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    // --- Gestion du plainPassword ---

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    // --- Getters Elo & Games ---

    public function getElo(): ?int
    {
        return $this->elo;
    }

    public function setElo(int $elo): static
    {
        $this->elo = $elo;
        return $this;
    }

    public function getGamesAsAttacker(): Collection
    {
        return $this->gamesAsAttacker;
    }

    public function getGamesAsDefender(): Collection
    {
        return $this->gamesAsDefender;
    }
}
