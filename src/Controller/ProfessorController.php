<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Entity\Professor;
use App\Form\ProfessorType;
use App\Repository\ProfessorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\Registry;

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

    /**
     * TABLEAU DE BORD PROFESSEUR
     * Affiche les statistiques et les conventions à valider.
     */
    #[Route('/{id}', name: 'app_professor_show', methods: ['GET'])]
    #[IsGranted('ROLE_PROFESSOR')]
    public function show(Professor $professor): Response
    {
        // SÉCURITÉ : Vérifier que le prof connecté regarde son propre profil
        if ($this->getUser() === null || $this->getUser()->getId() !== $professor->getId()) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à accéder à ce profil.");
        }

        // Récupération des étudiants suivis (Méthode issue du main)
        // Si cette méthode n'existe pas encore dans ton entité Professor, utilise $professor->getStudents() ou une liste vide []
        $students = method_exists($professor, 'getStudentsReferred') ? $professor->getStudentsReferred() : [];

        // Récupération des contrats via la relation
        $allCoordinatedContracts = $professor->getContracts();

        // 1. Filtrer les conventions à valider (Status Workflow : 'filled_by_company')
        $contractsToValidate = $allCoordinatedContracts->filter(function (Contract $contract) {
            return $contract->getStatus() === 'filled_by_company';
        });

        // 2. Filtrer les conventions actives/validées
        $activeContracts = $allCoordinatedContracts->filter(function (Contract $contract) {
            return $contract->getStatus() === 'validated_by_prof';
        });

        // 3. Filtrer les conventions terminées/archivées
        $pastContracts = $allCoordinatedContracts->filter(function (Contract $contract) {
            return in_array($contract->getStatus(), ['completed', 'archived', 'refused']);
        });

        return $this->render('professor/show.html.twig', [
            'professor' => $professor,
            'students_count' => count($students),
            'pending_validation_count' => $contractsToValidate->count(),
            'active_contracts_count' => $activeContracts->count(),
            'past_contracts_count' => $pastContracts->count(),
            'contracts_to_validate' => $contractsToValidate,
            'all_coordinated_contracts' => $allCoordinatedContracts,
        ]);
    }

    /**
     * VALIDATION DE CONVENTION (Via Workflow)
     * Cette méthode gère le lien reçu par email ET le bouton du dashboard.
     */
    #[Route('/contract/{id}/validate', name: 'app_professor_validate_contract', methods: ['GET', 'POST'])]
    public function validateContract(
        Contract $contract,
        Request $request,
        EntityManagerInterface $entityManager,
        Registry $workflowRegistry
    ): Response
    {
        // On récupère le workflow
        $workflow = $workflowRegistry->get($contract);

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            try {
                if ($action === 'validate' && $workflow->can($contract, 'validate_by_prof')) {

                    $workflow->apply($contract, 'validate_by_prof');
                    $this->addFlash('success', 'Le sujet de stage a été validé avec succès !');

                } elseif ($action === 'refuse' && $workflow->can($contract, 'refuse_subject')) {

                    $workflow->apply($contract, 'refuse_subject');
                    $this->addFlash('warning', 'Le sujet de stage a été refusé.');

                } else {
                    $this->addFlash('danger', 'Action impossible pour le statut actuel (' . $contract->getStatus() . ').');
                }
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue : ' . $e->getMessage());
            }

            $entityManager->flush();

            // Si le prof est connecté, on le renvoie vers son dashboard
            if ($this->getUser()) {
                return $this->redirectToRoute('app_professor_show', ['id' => $this->getUser()->getId()]);
            }

            // Sinon (accès via mail sans être connecté), on reste sur la page ou on va à l'accueil
            return $this->redirectToRoute('app_home');
        }

        // Si c'est un GET (clic sur le lien email), on affiche la page de confirmation
        return $this->render('professor/validate.html.twig', [
            'contract' => $contract,
        ]);
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
