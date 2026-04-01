<?php

namespace App\Controller;

use App\Entity\Student;
use App\Entity\Contract;
use App\Form\StudentType;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/student')]
final class StudentController extends AbstractController
{
    #[Route(name: 'app_student_index', methods: ['GET'])]
    // #[IsGranted('ROLE_ADMIN')] // Décommenter si index doit être protégé
    public function index(StudentRepository $studentRepository): Response
    {
        return $this->render('student/index.html.twig', [
            'students' => $studentRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_student_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $student = new Student();
        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($student);
            $entityManager->flush();

            return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('student/new.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    /**
     * Affiche le tableau de bord de l'étudiant (remplace l'ancienne vue show)
     */
    #[Route('/{id}', name: 'app_student_show', methods: ['GET'])]
    #[IsGranted('ROLE_STUDENT')] // L'utilisateur doit être connecté et avoir le rôle étudiant
    public function show(Student $student): Response
    {
        // SECURITE: S'assurer que l'utilisateur connecté accède à son propre profil
        // Correction : L'entité Student hérite de User, donc on compare l'ID de l'utilisateur connecté
        // avec l'ID de l'objet Student (qui est aussi un User)
        if ($this->getUser() === null || $this->getUser()->getId() !== $student->getId()) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à accéder à ce tableau de bord.");
        }

        // --- LOGIQUE DU DASHBOARD ÉTUDIANT ---
        $contracts = $student->getContracts()->toArray();
        usort($contracts, static fn (Contract $left, Contract $right): int => $right->getId() <=> $left->getId());

        $totalDaysRemaining = 0;
        $pastContractsCount = 0;
        $currentStatus = 'Aucun stage actif ni en préparation';
        $latestContract = $contracts[0] ?? null;
        $now = new \DateTimeImmutable('today');

        foreach ($contracts as $contract) {
            [$startDate, $endDate] = $this->getContractPeriod($contract);

            if ($contract->getStatus() === Contract::STATUS_SIGNED && $endDate instanceof \DateTimeInterface && $endDate < $now) {
                ++$pastContractsCount;
            }
        }

        if ($latestContract instanceof Contract) {
            [$startDate, $endDate] = $this->getContractPeriod($latestContract);
            $currentStatus = $this->buildStudentDashboardStatus($latestContract, $startDate, $endDate, $now);

            if ($latestContract->getStatus() === Contract::STATUS_SIGNED && $endDate instanceof \DateTimeInterface && $endDate >= $now) {
                $interval = $now->diff(\DateTimeImmutable::createFromInterface($endDate));
                $totalDaysRemaining = $interval->invert === 0 ? $interval->days : 0;
            }
        }

        return $this->render('student/show.html.twig', [
            'student' => $student,
            'remaining_days' => $totalDaysRemaining,
            'contract_status' => $currentStatus,
            'past_contracts_count' => $pastContractsCount,
            'contracts' => $contracts,
        ]);
    }

    /**
     * @return array{0: ?\DateTimeInterface, 1: ?\DateTimeInterface}
     */
    private function getContractPeriod(Contract $contract): array
    {
        $startDate = null;
        $endDate = null;

        foreach ($contract->getInternshipDates() as $internshipDate) {
            $currentStartDate = $internshipDate->getStartDate();
            $currentEndDate = $internshipDate->getEndDate();

            if ($currentStartDate && ($startDate === null || $currentStartDate < $startDate)) {
                $startDate = $currentStartDate;
            }

            if ($currentEndDate && ($endDate === null || $currentEndDate > $endDate)) {
                $endDate = $currentEndDate;
            }
        }

        return [$startDate, $endDate];
    }

    private function buildStudentDashboardStatus(
        Contract $contract,
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        \DateTimeImmutable $now,
    ): string {
        return match ($contract->getStatus()) {
            Contract::STATUS_COLLECTION_SENT => 'Collecte envoyee a l\'entreprise',
            Contract::STATUS_FILLED_BY_COMPANY => 'Collecte remplie, validation etudiant attendue',
            Contract::STATUS_VALIDATED_BY_STUDENT => 'Validation professeur en attente',
            Contract::STATUS_VALIDATED_BY_PROF => 'Validation DDF en attente',
            Contract::STATUS_SIGNATURE_REQUESTED => 'Signature en cours',
            Contract::STATUS_REFUSED => 'Collecte rejetee par le professeur',
            Contract::STATUS_SIGNED => $this->buildSignedStatusLabel($startDate, $endDate, $now),
            default => $contract->getStatusLabel(),
        };
    }

    private function buildSignedStatusLabel(
        ?\DateTimeInterface $startDate,
        ?\DateTimeInterface $endDate,
        \DateTimeImmutable $now,
    ): string {
        if ($startDate && $endDate) {
            if ($startDate <= $now && $endDate >= $now) {
                return sprintf('Stage en cours du %s au %s', $startDate->format('d/m/Y'), $endDate->format('d/m/Y'));
            }

            if ($startDate > $now) {
                return sprintf('Convention signee pour le stage du %s au %s', $startDate->format('d/m/Y'), $endDate->format('d/m/Y'));
            }

            if ($endDate < $now) {
                return sprintf('Stage termine le %s', $endDate->format('d/m/Y'));
            }
        }

        return 'Convention signee';
    }

    #[Route('/{id}/edit', name: 'app_student_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Student $student, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(StudentType::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_student_show', ['id' => $student->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('student/edit.html.twig', [
            'student' => $student,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_student_delete', methods: ['POST'])]
    public function delete(Request $request, Student $student, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$student->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($student);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_student_index', [], Response::HTTP_SEE_OTHER);
    }
}
