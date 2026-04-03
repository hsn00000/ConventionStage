<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\Organisation;
use App\Entity\Student;
use App\Entity\Tutor;
use App\Entity\User;
use App\Form\InitiateContractType;
use App\Repository\InternshipScheduleRepository;
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
        InternshipScheduleRepository $internshipScheduleRepository,
    ): Response
    {
        /** @var Student $student */
        $student = $this->getUser();
        $level = $student?->getLevel();

        if (!$level) {
            return $this->render('student_contract/init.html.twig', [
                'form' => null,
                'blocking_error' => 'Aucune classe n\'est rattachée à votre compte. Impossible de vous proposer un planning de stage.',
            ]);
        }

        $internshipSchedules = $internshipScheduleRepository->findActiveForLevel($level);

        if ($internshipSchedules === []) {
            return $this->render('student_contract/init.html.twig', [
                'form' => null,
                'blocking_error' => 'Aucun planning de stage actif n\'est ouvert pour votre classe. Contactez la DDF pour qu\'elle en crée un.',
            ]);
        }

        $internshipSchedule = $internshipSchedules[0];

        $form = $this->createForm(InitiateContractType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

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
            $contract->setInternshipSchedule($internshipSchedule);

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
                return $this->render('student_contract/init.html.twig', [
                    'form' => $form->createView(),
                    'blocking_error' => 'Aucun professeur n\'est assigné à votre classe. Contactez l\'administration avant de refaire une demande.',
                ]);
            }

            // On assigne le prof trouvé
            $contract->setCoordinator($coordinator);
            // -------------------------------------------------------------

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
            'blocking_error' => null,
        ]);
    }

    #[Route('/demande-envoyee', name: 'app_student_contract_success')]
    public function success(): Response
    {
        return $this->render('student_contract/success.html.twig');
    }
}
