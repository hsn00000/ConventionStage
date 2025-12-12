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
        $contracts = $student->getContracts(); // Récupère la collection de contrats

        $activeContract = null;
        $totalDaysRemaining = 0;
        $pastContractsCount = 0;
        $currentStatus = 'Aucune convention trouvée';

        $now = new \DateTimeImmutable();

        foreach ($contracts as $contract) {
            /** @var Contract $contract */

            // On suppose que l'entité Contract a les méthodes getStartDate, getEndDate et getStatus
            // NOTE : Il manque les relations de Contract avec les dates dans les entités fournies.
            // J'ai besoin de savoir comment vous stockez les dates de stage (StartDate/EndDate)
            // pour calculer le nombre de jours restants. Si c'est dans ContractDate,
            // il faudrait itérer sur $contract->getContractDates() pour trouver les dates.
            // Pour l'instant, j'utilise les hypothèses pour que la logique puisse fonctionner
            // si les dates sont dans l'entité Contract elle-même.

            // Hypothèses de méthodes sur l'entité Contract
            $startDate = $contract->getTokenExpDate(); // UTILISER LA VRAIE DATE DE DEBUT
            $endDate = $contract->getTokenExpDate();   // UTILISER LA VRAIE DATE DE FIN
            $status = $contract->getStatus();

            // 1. Conventions passées (terminées ou date de fin dans le passé)
            if ($status === 'completed' || ($endDate instanceof \DateTimeInterface && $endDate < $now)) {
                $pastContractsCount++;
            }

            // 2. Convention active (en cours : date de début passée/actuelle ET date de fin future/actuelle)
            if ($startDate instanceof \DateTimeInterface && $endDate instanceof \DateTimeInterface) {
                if (($status === 'active' || $status === 'validated') && $startDate <= $now && $endDate >= $now) {
                    $activeContract = $contract;

                    // Statut : En cours
                    $currentStatus = 'En cours (du ' . $startDate->format('d/m/Y') . ' au ' . $endDate->format('d/m/Y') . ')';

                    // Calcul des jours civils restants
                    $interval = $now->diff($endDate);
                    if ($interval->invert === 0) {
                        $totalDaysRemaining = $interval->days;
                    } else {
                        $totalDaysRemaining = 0;
                    }
                    // Si un contrat actif est trouvé, on priorise et on arrête le statut.
                    break;
                }
            }

            // 3. Statut de convention future ou en cours de validation (uniquement si pas de contrat actif trouvé)
            if ($activeContract === null && $startDate instanceof \DateTimeInterface && $startDate > $now && $status !== 'completed' && $status !== 'cancelled') {
                if ($status === 'Brouillon') {
                    $currentStatus = 'Brouillon en cours';
                } elseif ($status === 'En attente entreprise') {
                    $currentStatus = 'Soumise, en attente de l\'entreprise';
                } elseif ($status === 'En attente') {
                    $currentStatus = 'En attente de validation Professeur';
                } else {
                    $currentStatus = 'Convention en préparation ou en attente';
                }
            }
        }

        // Finalisation du statut si rien n'a été trouvé
        if ($activeContract === null && $currentStatus === 'Aucune convention trouvée') {
            $currentStatus = 'Aucun stage actif ni en préparation';
        }

        // Si on a compté des contrats passés, et qu'il n'y a pas de contrat actif ou en préparation, on déduit que tous sont passés.
        if ($pastContractsCount > 0 && $currentStatus === 'Aucun stage actif ni en préparation') {
            $currentStatus = 'Historique disponible';
        }


        return $this->render('student/show.html.twig', [
            'student' => $student,
            'remaining_days' => $totalDaysRemaining,
            'contract_status' => $currentStatus,
            'past_contracts_count' => $pastContractsCount,
            'contracts' => $contracts,
        ]);
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
