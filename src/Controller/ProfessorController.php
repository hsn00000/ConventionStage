<?php

namespace App\Controller;

use App\Entity\Professor;
use App\Entity\Contract;
use App\Form\ProfessorType;
use App\Repository\ProfessorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\Registry; // <--- Ne pas oublier cet import

#[Route('/professor')]
final class ProfessorController extends AbstractController
{
    #[Route(name: 'app_professor_index', methods: ['GET'])]
    public function index(ProfessorRepository $professorRepository): Response
    {
        return $this->render('professor/index.html.twig', [
            'professors' => $professorRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_professor_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $professor = new Professor();
        $form = $this->createForm(ProfessorType::class, $professor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($professor);
            $entityManager->flush();

            return $this->redirectToRoute('app_professor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('professor/new.html.twig', [
            'professor' => $professor,
            'form' => $form,
        ]);
    }

    // --- NOUVELLE MÉTHODE POUR LA VALIDATION DU SUJET ---
    #[Route('/contract/{id}/validate', name: 'app_professor_validate_contract', methods: ['GET', 'POST'])]
    public function validateContract(
        Contract $contract,
        Request $request,
        EntityManagerInterface $entityManager,
        Registry $workflowRegistry // Injection du service Workflow
    ): Response
    {
        // On récupère le workflow associé à l'entité Contract
        $workflow = $workflowRegistry->get($contract);

        if ($request->isMethod('POST')) {
            // On récupère l'action du bouton cliqué (validate ou refuse)
            $action = $request->request->get('action');

            try {
                if ($action === 'validate' && $workflow->can($contract, 'validate_by_prof')) {

                    $workflow->apply($contract, 'validate_by_prof');
                    $this->addFlash('success', 'Le sujet de stage a été validé avec succès !');

                } elseif ($action === 'refuse' && $workflow->can($contract, 'refuse_subject')) {

                    $workflow->apply($contract, 'refuse_subject');
                    $this->addFlash('warning', 'Le sujet de stage a été refusé.');

                } else {
                    $this->addFlash('danger', 'Action impossible pour le statut actuel de la convention.');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors du changement de statut.');
            }

            // Sauvegarde en base de données
            $entityManager->flush();

            // Redirection vers la liste des profs (ou un dashboard si tu en as un)
            return $this->redirectToRoute('app_professor_index');
        }

        // Affichage de la vue de validation
        return $this->render('professor/validate.html.twig', [
            'contract' => $contract,
        ]);
    }
    // ----------------------------------------------------

    #[Route('/{id}', name: 'app_professor_show', methods: ['GET'])]
    #[IsGranted('ROLE_PROFESSOR')]
    // Note: Le nom de la route est maintenant 'app_professor_show'
    public function show(Professor $professor): Response
    {
        // SECURITE: S'assurer que l'utilisateur connecté accède à son propre dashboard
        if ($this->getUser() === null || $this->getUser()->getId() !== $professor->getId()) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à accéder à ce profil.");
        }

        // CORRECTION DE L'ERREUR: Utiliser la méthode getStudentsReferred()
        $students = $professor->getStudentsReferred();

        // On utilise la relation ManyToOne pour récupérer tous les contrats dont ce professeur est coordinateur
        $allCoordinatedContracts = $professor->getContracts();

        // Filtration des conventions qui requièrent une action du professeur
        $contractsToValidate = $allCoordinatedContracts->filter(function (Contract $contract) {
            // Statut à ajuster selon votre flux réel (ici, 'A valider Prof' est utilisé comme exemple)
            return $contract->getStatus() === 'A valider Prof' || $contract->getStatus() === 'En attente entreprise';
        });

        // Calculs pour les statistiques
        $studentsCount = count($students);
        $pendingValidationCount = $contractsToValidate->filter(function (Contract $contract) {
            return $contract->getStatus() === 'A valider Prof';
        })->count();

        $totalActiveContracts = $allCoordinatedContracts->filter(function (Contract $contract) {
            return $contract->getStatus() === 'Validated' || $contract->getStatus() === 'Active';
        })->count();

        $totalPastContracts = $allCoordinatedContracts->filter(function (Contract $contract) {
            return $contract->getStatus() === 'Completed' || $contract->getStatus() === 'Archived';
        })->count();

        // Rendu dans le template 'show.html.twig'
        return $this->render('professor/show.html.twig', [
            'professor' => $professor,
            'students_count' => $studentsCount,
            'pending_validation_count' => $pendingValidationCount,
            'active_contracts_count' => $totalActiveContracts,
            'past_contracts_count' => $totalPastContracts,
            'contracts_to_validate' => $contractsToValidate, // La liste des conventions à valider/suivre
            'all_coordinated_contracts' => $allCoordinatedContracts,
        ]);
    }

    /**
     * Action pour valider une convention.
     */
    #[Route('/contract/{id}/validate', name: 'app_professor_contract_validate', methods: ['POST'])]
    #[IsGranted('ROLE_PROFESSOR')]
    public function validateContract(Request $request, Contract $contract, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() !== $contract->getCoordinator()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas le professeur référent de cette convention.');
        }

        if (!$this->isCsrfTokenValid('validate' . $contract->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        if ($contract->getStatus() === 'A valider Prof') {
            $contract->setStatus('Validée par Professeur');
            $entityManager->flush();
            $this->addFlash('success', 'La convention de ' . $contract->getStudent()->getFirstname() . ' a été validée.');
        } else {
            $this->addFlash('warning', 'La convention n\'est pas dans l\'état "A valider Prof". Statut actuel : ' . $contract->getStatus());
        }

        // Redirection vers le profil (maintenant le tableau de bord)
        return $this->redirectToRoute('app_professor_show', ['id' => $this->getUser()->getId()]);
    }

    /**
     * Action pour refuser une convention.
     */
    #[Route('/contract/{id}/refuse', name: 'app_professor_contract_refuse', methods: ['POST'])]
    #[IsGranted('ROLE_PROFESSOR')]
    public function refuseContract(Request $request, Contract $contract, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() !== $contract->getCoordinator()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas le professeur référent de cette convention.');
        }

        if (!$this->isCsrfTokenValid('refuse' . $contract->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton de sécurité invalide.');
        }

        $contract->setStatus('Refusée par Professeur');
        $entityManager->flush();
        $this->addFlash('error', 'La convention de ' . $contract->getStudent()->getFirstname() . ' a été refusée.');

        // Redirection vers le profil (maintenant le tableau de bord)
        return $this->redirectToRoute('app_professor_show', ['id' => $this->getUser()->getId()]);
    }

    #[Route('/{id}/edit', name: 'app_professor_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Professor $professor, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProfessorType::class, $professor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_professor_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('professor/edit.html.twig', [
            'professor' => $professor,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_professor_delete', methods: ['POST'])]
    public function delete(Request $request, Professor $professor, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$professor->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($professor);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_professor_index', [], Response::HTTP_SEE_OTHER);
    }
}
