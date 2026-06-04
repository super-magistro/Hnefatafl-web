<?php

namespace App\DataFixtures;

use App\Config\GameRules;
use App\Entity\GameBoard;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }
    public function load(ObjectManager $manager): void
    {
        $this->loadBrandubh($manager);
        $this->loadTablut($manager);
        $this->loadCopenhagen($manager);
        $this->loadTawlbwrdd($manager);
        $this->loadFetlar($manager);
        $this->loadAleaEvangelii($manager);
        $this->loadUsers($manager);

        $manager->flush();
    }

    /**
     * Variante irlandaise 7x7
     * Victoire aux coins. Rapide et brutal.
     */
    private function loadBrandubh(ObjectManager $manager): void
    {
        $variant = new GameBoard();
        $variant->setName('Brandubh');
        $variant->setBoardSize(7);
        $variant->setDescription('Variante irlandaise rapide et brutale. Les attaquants encerclent étroitement les défenseurs, rendant chaque coup crucial dès le départ.');

        // 0=Vide, 1=Attaquant, 2=Défenseur, 3=Roi
        $variant->setInitialLayout([
            [0, 0, 0, 1, 0, 0, 0],
            [0, 0, 0, 1, 0, 0, 0],
            [0, 0, 0, 2, 0, 0, 0],
            [1, 1, 2, 3, 2, 1, 1],
            [0, 0, 0, 2, 0, 0, 0],
            [0, 0, 0, 1, 0, 0, 0],
            [0, 0, 0, 1, 0, 0, 0],
        ]);

        // 0=Sol, 1=Trône, 2=Coin (Victoire)
        $variant->setTerrainLayout([
            [2, 0, 0, 0, 0, 0, 2],
            [0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 1, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0],
            [2, 0, 0, 0, 0, 0, 2],
        ]);

        // Utilisation des constantes GameRules
        $variant->setRules([
            GameRules::KEY_WIN_CONDITION    => GameRules::WIN_CORNER,
            GameRules::KEY_KING_CAPTURE     => GameRules::CAPTURE_2_SIDES, // Spécifique Brandubh (Roi faible ?)
            GameRules::KEY_KING_WEAPON      => GameRules::KING_ARMED,
            GameRules::KEY_THRONE_HOSTILITY => GameRules::THRONE_ALWAYS_HOSTILE,
        ]);

        $manager->persist($variant);
    }

    /**
     * Variante Saami 9x9
     * Le classique historique. Victoire sur les bords.
     */
    private function loadTablut(ObjectManager $manager): void
    {
        $variant = new GameBoard();
        $variant->setName('Tablut');
        $variant->setBoardSize(9);
        $variant->setDescription('La célèbre variante Saami documentée par le botaniste Carl von Linné en Laponie. Très équilibrée et parfaite pour l\'apprentissage.');

        $variant->setInitialLayout([
            [0, 0, 0, 1, 1, 1, 0, 0, 0],
            [0, 0, 0, 0, 1, 0, 0, 0, 0],
            [0, 0, 0, 0, 2, 0, 0, 0, 0],
            [1, 0, 0, 0, 2, 0, 0, 0, 1],
            [1, 1, 2, 2, 3, 2, 2, 1, 1],
            [1, 0, 0, 0, 2, 0, 0, 0, 1],
            [0, 0, 0, 0, 2, 0, 0, 0, 0],
            [0, 0, 0, 0, 1, 0, 0, 0, 0],
            [0, 0, 0, 1, 1, 1, 0, 0, 0],
        ]);

        $variant->setTerrainLayout([
            [0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 1, 0, 0, 0, 0], // Juste le trône
            [0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0],
        ]);

        $variant->setRules([
            GameRules::KEY_WIN_CONDITION    => GameRules::WIN_EDGE,
            GameRules::KEY_KING_CAPTURE     => GameRules::CAPTURE_4_SIDES,
            GameRules::KEY_KING_WEAPON      => GameRules::KING_ARMED,
            GameRules::KEY_THRONE_HOSTILITY => GameRules::THRONE_HOSTILE_EMPTY, // Souvent EMPTY en Tablut
        ]);

        $manager->persist($variant);
    }

    /**
     * Variante Moderne 11x11 (Tournoi)
     * Très stratégique. Victoire aux coins.
     */
    private function loadCopenhagen(ObjectManager $manager): void
    {
        $variant = new GameBoard();
        $variant->setName('Copenhagen');
        $variant->setBoardSize(11);
        $variant->setDescription('Variante moderne conçue pour la compétition. Elle intègre des règles avancées comme la capture en mur de boucliers (Shieldwall).');

        $variant->setInitialLayout([
            [0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0],
            [0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [1, 0, 0, 0, 0, 2, 0, 0, 0, 0, 1],
            [1, 0, 0, 0, 2, 2, 2, 0, 0, 0, 1],
            [1, 1, 0, 2, 2, 3, 2, 2, 0, 1, 1],
            [1, 0, 0, 0, 2, 2, 2, 0, 0, 0, 1],
            [1, 0, 0, 0, 0, 2, 0, 0, 0, 0, 1],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0],
        ]);

        $variant->setTerrainLayout([
            [2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2],
        ]);

        $variant->setRules([
            GameRules::KEY_WIN_CONDITION    => GameRules::WIN_CORNER,
            GameRules::KEY_KING_CAPTURE     => GameRules::CAPTURE_4_SIDES,
            GameRules::KEY_KING_WEAPON      => GameRules::KING_ARMED,
            GameRules::KEY_THRONE_HOSTILITY => GameRules::THRONE_HOSTILE_EMPTY,
            'shieldWall' => true, // Pas encore dans GameRules, on laisse en string
            'exitForts' => true,
        ]);

        $manager->persist($variant);
    }

    /**
     * Tawlbwrdd (11x11) - Galloise
     */
    private function loadTawlbwrdd(ObjectManager $manager): void
    {
        $variant = new GameBoard();
        $variant->setName('Tawlbwrdd');
        $variant->setBoardSize(11);
        $variant->setDescription('Une variante galloise historique mentionnée dans les écrits du roi Howel Dda. Le trône central reste toujours hostile à tous sauf au Roi.');

        $variant->setInitialLayout([
            [0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0],
            [0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0],
            [1, 1, 0, 0, 2, 2, 2, 0, 0, 1, 1],
            [1, 0, 1, 2, 2, 3, 2, 2, 1, 0, 1],
            [1, 1, 0, 0, 2, 2, 2, 0, 0, 1, 1],
            [0, 0, 0, 0, 0, 2, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 1, 0, 1, 0, 0, 0, 0],
            [0, 0, 0, 0, 1, 1, 1, 0, 0, 0, 0],
        ]);

        // Terrain standard (pas de coins hostiles)
        $terrain = array_fill(0, 11, array_fill(0, 11, 0));
        $terrain[5][5] = 1; // Trône
        $variant->setTerrainLayout($terrain);

        $variant->setRules([
            GameRules::KEY_WIN_CONDITION    => GameRules::WIN_EDGE,
            GameRules::KEY_KING_CAPTURE     => GameRules::CAPTURE_2_SIDES,
            GameRules::KEY_KING_WEAPON      => GameRules::KING_ARMED,
            GameRules::KEY_THRONE_HOSTILITY => GameRules::THRONE_ALWAYS_HOSTILE,
        ]);

        $manager->persist($variant);
    }

    /**
     * Fetlar (11x11)
     */
    private function loadFetlar(ObjectManager $manager): void
    {
        $variant = new GameBoard();
        $variant->setName('Fetlar Hnefatafl');
        $variant->setBoardSize(11);
        $variant->setDescription('Variante originaire de l\'île de Fetlar dans l\'archipel des Shetland. Elle utilise des dispositions de pièces similaires au Copenhagen.');

        $variant->setInitialLayout([
            [0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0],
            [0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [1, 0, 0, 0, 0, 2, 0, 0, 0, 0, 1],
            [1, 0, 0, 0, 2, 2, 2, 0, 0, 0, 1],
            [1, 1, 0, 2, 2, 3, 2, 2, 0, 1, 1],
            [1, 0, 0, 0, 2, 2, 2, 0, 0, 0, 1],
            [1, 0, 0, 0, 0, 2, 0, 0, 0, 0, 1],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 0, 1, 1, 1, 1, 1, 0, 0, 0],
        ]);

        $variant->setTerrainLayout([
            [2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 1, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
            [2, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2],
        ]);

        $variant->setRules([
            GameRules::KEY_WIN_CONDITION    => GameRules::WIN_CORNER,
            GameRules::KEY_KING_CAPTURE     => GameRules::CAPTURE_4_SIDES,
            GameRules::KEY_KING_WEAPON      => GameRules::KING_ARMED,
            GameRules::KEY_THRONE_HOSTILITY => GameRules::THRONE_ALWAYS_HOSTILE, // Coins aussi hostiles souvent
            // Optionnel : 'corner_hostility' => true (Si tu veux gérer ça aussi)
        ]);

        $manager->persist($variant);
    }

    /**
     * Alea Evangelii (19x19)
     */
    private function loadAleaEvangelii(ObjectManager $manager): void
    {
        $variant = new GameBoard();
        $variant->setName('Alea Evangelii');
        $variant->setBoardSize(19);
        $variant->setDescription('Une gigantesque variante anglo-saxonne reconstituée à partir d\'un manuscrit du Xe siècle. Une véritable reconstitution de siège.');

        $initial = array_fill(0, 19, array_fill(0, 19, 0));

        // Placement du Roi
        $initial[9][9] = 3;

        // Placement des Défenseurs (24 gardes)
        $defenders = [
            [9,7], [9,11], [7,9], [11,9], // Croix proche
            [9,6], [9,12], [6,9], [12,9], // Croix loin
            [8,8], [8,10], [10,8], [10,10], // Diagonales proches
            [6,6], [6,12], [12,6], [12,12], // Coins du carré interne
            [8,6], [6,8], [10,6], [6,10], // Remplissage
            [8,12], [12,8], [10,12], [12,10]
        ];
        foreach($defenders as $coord) { $initial[$coord[0]][$coord[1]] = 2; }

        // Placement des Attaquants (48 attaquants)
        $attackers = [
            // Haut
            [0,2],[0,5],[0,13],[0,16],
            [1,2],[1,5],[1,13],[1,16],
            [2,0],[2,1],[2,2],[2,5],[2,13],[2,16],[2,17],[2,18],
            [5,0],[5,1],[5,2], [13,0],[13,1],[13,2],
            [5,16],[5,17],[5,18], [13,16],[13,17],[13,18],
            // Bas
            [16,0],[16,1],[16,2],[16,5],[16,13],[16,16],[16,17],[16,18],
            [17,2],[17,5],[17,13],[17,16],
            [18,2],[18,5],[18,13],[18,16]
        ];

        $moreAttackers = [
            [0,8],[0,10],[1,9],[2,9], // T Haut
            [18,8],[18,10],[17,9],[16,9], // T Bas
            [8,0],[10,0],[9,1],[9,2], // T Gauche
            [8,18],[10,18],[9,17],[9,16] // T Droite
        ];

        foreach($attackers as $coord) { $initial[$coord[0]][$coord[1]] = 1; }
        foreach($moreAttackers as $coord) { $initial[$coord[0]][$coord[1]] = 1; }

        $variant->setInitialLayout($initial);

        // Terrain
        $terrain = array_fill(0, 19, array_fill(0, 19, 0));
        $terrain[0][0] = 2; $terrain[0][18] = 2;
        $terrain[18][0] = 2; $terrain[18][18] = 2;
        $terrain[9][9] = 1; // Trône
        $variant->setTerrainLayout($terrain);

        $variant->setRules([
            GameRules::KEY_WIN_CONDITION    => GameRules::WIN_CORNER,
            GameRules::KEY_KING_CAPTURE     => GameRules::CAPTURE_4_SIDES,
            GameRules::KEY_KING_WEAPON      => GameRules::KING_UNARMED,
            GameRules::KEY_THRONE_HOSTILITY => GameRules::THRONE_ALWAYS_HOSTILE,
        ]);

        $manager->persist($variant);
    }

    private function loadUsers(ObjectManager $manager): void
    {
        $user1 = new User();
        $user1->setEmail('jarl@hnefatafl.com');
        $user1->setPassword($this->passwordHasher->hashPassword($user1, 'Password123!'));
        $user1->setElo(1500);
        $manager->persist($user1);

        $user2 = new User();
        $user2->setEmail('ragnar@hnefatafl.com');
        $user2->setPassword($this->passwordHasher->hashPassword($user2, 'Password123!'));
        $user2->setElo(1200);
        $manager->persist($user2);

        $user3 = new User();
        $user3->setEmail('lagertha@hnefatafl.com');
        $user3->setPassword($this->passwordHasher->hashPassword($user3, 'Password123!'));
        $user3->setElo(1350);
        $manager->persist($user3);
    }
}
