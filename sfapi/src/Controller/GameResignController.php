<?php

namespace App\Controller;

use App\Entity\Game;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response; // <--- Import nécessaire
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[AsController]
class GameResignController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    // Changement du type de retour : Game -> Response
    public function __invoke(Request $request, Game $data): Response
    {
        if ($data->getStatus() === 'FINISHED') {
            throw new BadRequestHttpException("La partie est déjà terminée.");
        }

        $user = $this->getUser();
        if (!$user) {
            throw new BadRequestHttpException("Vous devez être connecté pour abandonner.");
        }

        if ($user === $data->getAttacker()) {
            $data->setWinner($data->getDefender());
        } elseif ($user === $data->getDefender()) {
            $data->setWinner($data->getAttacker());
        } else {
            throw new BadRequestHttpException("Vous ne participez pas à cette partie.");
        }

        $data->setStatus('FINISHED');

        $this->entityManager->flush();

        // On retourne une réponse JSON propre
        return $this->json($data);
    }
}
