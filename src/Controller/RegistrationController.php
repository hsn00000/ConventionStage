<?php

namespace App\Controller;

use App\Entity\Professor;
use App\Entity\Student;
use App\Form\RegistrationProfessorType;
use App\Form\RegistrationStudentType;
use App\Repository\UserRepository;
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
        $user = new Student();
        $form = $this->createForm(RegistrationStudentType::class, $user);
        $form->handleRequest($request);

        // Logique spécifique pour assigner le prof référent selon la classe
        if ($form->isSubmitted()) {
            $level = $user->getLevel();
            if ($level && $level->getMainProfessor()) {
                $user->setProfReferent($level->getMainProfessor());
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            // Envoi de l'email
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@lycee-faure.fr', 'Lycée Fauré'))
                    ->to((string) $user->getEmail())
                    ->subject('Veuillez confirmer votre email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash('success', 'Un email de confirmation vous a été envoyé. Vérifiez votre boîte mail.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_student.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/register/professor', name: 'app_register_professor')]
    public function registerProfessor(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $user = new Professor();
        $form = $this->createForm(RegistrationProfessorType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $user->setRoles(['ROLE_PROFESSOR']);

            $entityManager->persist($user);
            $entityManager->flush();

            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('no-reply@lycee-faure.fr', 'Lycée Fauré'))
                    ->to((string) $user->getEmail())
                    ->subject('Veuillez confirmer votre email')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
            );

            $this->addFlash('success', 'Un email de confirmation vous a été envoyé. Vérifiez votre boîte mail.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_professor.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator, UserRepository $userRepository): Response
    {
        // 1. Récupération de l'ID depuis l'URL
        $id = $request->query->get('id');

        // Si l'ID est absent, lien invalide -> Login
        if (null === $id) {
            return $this->redirectToRoute('app_login');
        }

        // 2. Recherche de l'utilisateur
        $user = $userRepository->find($id);

        // Si l'utilisateur n'existe pas -> Login
        if (null === $user) {
            return $this->redirectToRoute('app_login');
        }

        // 3. Validation sécurisée
        try {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));
            return $this->redirectToRoute('app_login');
        }

        // 4. Succès -> Login avec message vert
        $this->addFlash('success', 'Votre email a été vérifié avec succès ! Vous pouvez maintenant vous connecter.');

        return $this->redirectToRoute('app_login');
    }
}
