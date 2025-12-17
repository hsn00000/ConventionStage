<?php

namespace App\Security;

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

        // Si l'utilisateur n'a pas cliqué sur le lien email (isVerified == false)
        if (!$user->isVerified()) {
            // On bloque la connexion avec un message d'erreur
            throw new CustomUserMessageAuthenticationException(
                'Votre compte n\'est pas encore activé. Veuillez vérifier vos emails.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Pas de vérification post-authentification nécessaire ici
    }
}
