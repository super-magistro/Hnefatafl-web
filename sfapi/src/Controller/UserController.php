<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;


class UserController extends AbstractController
{
    public function __invoke(#[CurrentUser] ?User $user): User
    {
        if (!$user) {
            throw $this->createAccessDeniedException();
        }
        return $user;
    }
}
