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
            $data = $form->getData();
            /** @var Student $student */
            $student = $this->getUser();

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
                $tutor->setLastname('A compléter');
                $tutor->setFirstname('A compléter');
                $tutor->setRoles(['ROLE_TUTOR']);
                $randomPassword = bin2hex(random_bytes(10));
                $tutor->setPassword($passwordHasher->hashPassword($tutor, $randomPassword));
                $em->persist($tutor);
            }

            // --- 2. Organisation ---
            $org = new Organisation();
            $org->setName($data['companyName']);
            $org->setAddressHq('A compléter');
            $org->setPostalCodeHq('00000');
            $org->setCityHq('A compléter');
            $org->setAddressInternship('A compléter');
            $org->setPostalCodeInternship('00000');
            $org->setCityInternship('A compléter');
            $org->setRespName('A compléter');
            $org->setRespFunction('A compléter');
            $org->setRespEmail($tutorEmail);
            $org->setRespPhone('0000000000');
            $org->setInsuranceName('A compléter');
            $org->setInsuranceContract('A compléter');
            $em->persist($org);

            // --- 3. Contrat ---
            $contract = new Contract();
            $contract->setStudent($student);
            $contract->setTutor($tutor);
            $contract->setOrganisation($org);
            $contract->setStatus('En attente entreprise');

            $contract->setDeplacement(false);
            $contract->setTransportFreeTaken(false);
            $contract->setLunchTaken(false);
            $contract->setHostTaken(false);
            $contract->setBonus(false);
            $contract->setPlannedActivities('A compléter par l\'entreprise');
            $contract->setWorkHours([]);
            $contract->setPdfUnsigned('');
            $contract->setPdfSigned('');

            $token = bin2hex(random_bytes(32));
            $contract->setSharingToken($token);

            if ($student->getProfReferent()) {
                $contract->setCoordinator($student->getProfReferent());
            }

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
        ]);
    }

    #[Route('/demande-envoyee', name: 'app_student_contract_success')]
    public function success(): Response
    {
        return $this->render('student_contract/success.html.twig');
    }
}
