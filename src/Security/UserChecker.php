<?php

namespace App\Security;

use App\Entity\Professor;
use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException(
                'Votre compte n\'est pas encore activé. Veuillez vérifier vos emails.'
            );
        }

        if ($user instanceof Professor && !$user->isApprovedByAdmin()) {
            throw new CustomUserMessageAuthenticationException(
                'Votre compte est en attente de validation par un administrateur.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Pas de vérification post-authentification nécessaire ici
    }
}
