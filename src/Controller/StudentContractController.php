<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\Organisation;
use App\Entity\Student;
use App\Entity\Tutor;
use App\Entity\User;
use App\Form\InitiateContractType;
use App\Repository\SessionRepository;
use App\Service\SessionService;
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
        UserPasswordHasherInterface $passwordHasher,
        SessionRepository $sessionRepository,
        SessionService $sessionService,
    ): Response
    {
        /** @var Student $student */
        $student = $this->getUser();
        $level = $student?->getLevel();

        if (!$level) {
            $this->addFlash('error', 'Aucune classe n est rattachee a votre compte. Impossible de vous proposer une campagne.');

            return $this->redirectToRoute('app_student_show', ['id' => $student->getId()]);
        }

        $campaigns = $sessionRepository->findActiveForLevel($level);

        if ($campaigns === []) {
            $this->addFlash('error', 'Aucune campagne de stage active n est ouverte pour votre classe. Veuillez contacter la DDF.');

            return $this->redirectToRoute('app_student_show', ['id' => $student->getId()]);
        }

        $form = $this->createForm(InitiateContractType::class, null, [
            'campaign_choices' => $campaigns,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $campaign = $data['campaign'];

            // --- 1. Gestion du Tuteur ---
            $tutorEmail = $data['tutorEmail'];
            $existingUser = $em->getRepository(User::class)->findOneBy(['email' => $tutorEmail]);
            $tutor = null;

            if ($existingUser) {
                if ($existingUser instanceof Tutor) {
                    $tutor = $existingUser;
                } else {
                    $this->addFlash('error', 'Cette adresse email est déjà utilisée par un compte qui n\'est pas un tuteur.');
                    return $this->redirectToRoute('app_student_contract_init');
                }
            } else {
                $tutor = new Tutor();
                $tutor->setEmail($tutorEmail);
                $tutor->setLastname('');
                $tutor->setFirstname('');
                $tutor->setRoles(['ROLE_TUTOR']);
                $randomPassword = bin2hex(random_bytes(10));
                $tutor->setPassword($passwordHasher->hashPassword($tutor, $randomPassword));
                $em->persist($tutor);
            }

            // --- 2. Organisation ---
            $org = new Organisation();
            $org->setName($data['companyName']);
            $org->setAddressHq('');
            $org->setPostalCodeHq('');
            $org->setCityHq('');
            $org->setAddressInternship('');
            $org->setPostalCodeInternship('');
            $org->setCityInternship('');
            $org->setRespName('');
            $org->setRespFunction('');
            $org->setRespEmail($tutorEmail);
            $org->setRespPhone('');
            $org->setInsuranceName('');
            $org->setInsuranceContract('');
            $em->persist($org);

            // --- 3. Contrat ---
            $contract = new Contract();
            $contract->setStudent($student);
            $contract->setTutor($tutor);
            $contract->setOrganisation($org);
            $contract->setStatus(Contract::STATUS_COLLECTION_SENT);
            $contract->setSession($campaign);

            $contract->setDeplacement(false);
            $contract->setTransportFreeTaken(false);
            $contract->setLunchTaken(false);
            $contract->setHostTaken(false);
            $contract->setBonus(false);
            $contract->setPlannedActivities('');
            $contract->setWorkHours([]);
            $contract->setPdfUnsigned('');
            $contract->setPdfSigned('');

            $token = bin2hex(random_bytes(32));
            $contract->setSharingToken($token);

            // --- CORRECTION : Attribution du Coordinateur (Professeur) ---

            // a. On essaie le prof référent direct de l'étudiant
            $coordinator = $student->getProfReferent();

            // b. Si pas de référent, on tente le Prof Principal de sa classe
            if (!$coordinator && $student->getLevel()) {
                $coordinator = $student->getLevel()->getMainProfessor();
            }

            // c. Si toujours personne, on bloque l'enregistrement pour éviter l'erreur SQL
            if (!$coordinator) {
                // Modification du message d'erreur pour l'utilisateur
                $this->addFlash('error', 'Aucun professeur n\'est assigné à votre classe pour le moment. Veuillez patienter avant de refaire votre demande ou contacter l\'administration.');

                return $this->redirectToRoute('app_student_contract_init');
            }

            // On assigne le prof trouvé
            $contract->setCoordinator($coordinator);
            // -------------------------------------------------------------

            $sessionService->applySessionToContract($campaign, $contract);

            $em->persist($contract);
            $em->flush();

            // --- 4. Email ---
            $email = (new TemplatedEmail())
                ->from('convention@lycee-faure.fr')
                ->to($tutorEmail)
                ->subject('Lycée Gabriel Fauré - Demande de Convention de Stage')
                ->htmlTemplate('emails/company_request.html.twig')
                ->context([
                    'student' => $student,
                    'contract' => $contract,
                    'token' => $token,
                ]);

            $mailer->send($email);

            // --- REDIRECTION VERS LA PAGE DE SUCCÈS ---
            return $this->redirectToRoute('app_student_contract_success');
        }

        return $this->render('student_contract/init.html.twig', [
            'form' => $form->createView(),
            'campaigns' => $campaigns,
        ]);
    }

    #[Route('/demande-envoyee', name: 'app_student_contract_success')]
    public function success(): Response
    {
        return $this->render('student_contract/success.html.twig');
    }
}
