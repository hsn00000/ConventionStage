<?php

namespace App\Controller;

use App\Entity\Professor;
use App\Entity\Student;
use App\Entity\User;
use App\Form\RegistrationProfessorType;
use App\Form\RegistrationStudentType;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register/student', name: 'app_register_student')]
    public function registerStudent(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new Student(); // C'est ici que la restriction @lycee-faure.fr s'active
        $form = $this->createForm(RegistrationStudentType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hachage du mot de passe
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // Envoi de l'email de confirmation
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@lycee-faure.fr', 'Lycée Fauré'))
                    ->to((string) $user->getEmail())
                    ->subject('Veuillez confirmer votre email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash('success', 'Un email de confirmation vous a été envoyé. Veuillez cliquer sur le lien pour activer votre compte.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_student.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/register/professor', name: 'app_register_professor')]
    public function registerProfessor(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new Professor(); // C'est ici que la restriction @ac-grenoble.fr s'active
        $form = $this->createForm(RegistrationProfessorType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hachage du mot de passe
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // On s'assure qu'il a le rôle prof
            $user->setRoles(['ROLE_PROFESSOR']);

            $entityManager->persist($user);
            $entityManager->flush();

            // Envoi de l'email de confirmation
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@lycee-faure.fr', 'Lycée Fauré'))
                    ->to((string) $user->getEmail())
                    ->subject('Veuillez confirmer votre email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash('success', 'Un email de confirmation vous a été envoyé. Veuillez cliquer sur le lien pour activer votre compte.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_professor.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Validation du lien email, passe isVerified=true
        try {
            /** @var User $user */
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register_student'); // Redirection en cas d'erreur
        }

        $this->addFlash('success', 'Votre adresse email a bien été vérifiée !');

        return $this->redirectToRoute('app_login'); // Redirection après succès vers le login
    }
}
