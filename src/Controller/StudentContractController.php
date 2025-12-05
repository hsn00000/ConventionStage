<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\Organisation;
use App\Entity\Student;
use App\Entity\Tutor;
use App\Entity\User;
use App\Form\InitiateContractType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/etudiant/convention')]
#[IsGranted('ROLE_STUDENT')]
class StudentContractController extends AbstractController
{
    #[Route('/nouveau', name: 'app_student_contract_init')]
    public function init(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $form = $this->createForm(InitiateContractType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ... (Tout votre code de création Tuteur/Organisation/Contrat reste identique) ...
            // ...
            // ...

            // --- COPIEZ LE CODE DE CRÉATION EXISTANT ICI (Lignes 38 à 138 environ) ---
            // Pour gagner de la place, je ne le remets pas, mais gardez-le tel quel !
            // -------------------------------------------------------------------------

            // Juste après l'envoi du mail :

            // Au lieu de rediriger vers 'app_home', on redirige vers la page de succès
            return $this->redirectToRoute('app_student_contract_success');
        }

        return $this->render('student_contract/init.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/succes', name: 'app_student_contract_success')]
    public function success(): Response
    {
        return $this->render('student_contract/success.html.twig');
    }
}
