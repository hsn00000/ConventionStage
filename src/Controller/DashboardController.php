<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\User;

#[Route('/mon-espace')]
// Assure que seul un utilisateur authentifié peut accéder
#[IsGranted('ROLE_USER')]
class DashboardController extends AbstractController
{
    #[Route('/profil', name: 'app_user_profile')]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // Le contrôleur passe l'objet utilisateur à la vue
        return $this->render('dashboard/index.html.twig', [
            'user' => $user,
        ]);
    }
}
