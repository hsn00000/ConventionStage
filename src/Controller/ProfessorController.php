<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Repository\ContractRepository;
use App\Entity\Professor;
use App\Form\ProfessorType;
use App\Repository\ProfessorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
    public function show(Professor $professor, ContractRepository $contractRepository): Response
    {
        // SÉCURITÉ : Vérifier que le prof connecté regarde son propre profil
        if ($this->getUser() === null || $this->getUser()->getId() !== $professor->getId()) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à accéder à ce profil.");
        }

        // Récupération des étudiants suivis
        $students = method_exists($professor, 'getStudentsReferred') ? $professor->getStudentsReferred() : [];

        $allCoordinatedContracts = $professor->getContracts();
        $contractsToValidate = $contractRepository->findPendingProfessorValidation($professor);
        $validatedCollections = $allCoordinatedContracts->filter(function (Contract $contract) {
            return in_array($contract->getStatus(), [
                Contract::STATUS_VALIDATED_BY_PROF,
                Contract::STATUS_VALIDATED_BY_DDF,
                Contract::STATUS_SIGNED,
            ], true);
        });
        $activeContracts = $allCoordinatedContracts->filter(function (Contract $contract) {
            return in_array($contract->getStatus(), [
                Contract::STATUS_VALIDATED_BY_PROF,
                Contract::STATUS_VALIDATED_BY_DDF,
                Contract::STATUS_SIGNATURE_REQUESTED,
            ], true);
        });
        $pastContracts = $allCoordinatedContracts->filter(function (Contract $contract) {
            return in_array($contract->getStatus(), [
                Contract::STATUS_SIGNED,
                Contract::STATUS_REFUSED,
            ], true);
        });

        return $this->render('professor/show.html.twig', [
            'professor' => $professor,
            'students_count' => is_countable($students) ? count($students) : 0,
            'pending_validation_count' => count($contractsToValidate),
            'validated_collections_count' => $validatedCollections->count(),
            'active_contracts_count' => $activeContracts->count(),
            'past_contracts_count' => $pastContracts->count(),
            'contracts_to_validate' => $contractsToValidate,
            'validated_collections' => $validatedCollections,
            'all_coordinated_contracts' => $allCoordinatedContracts,
        ]);
    }

    #[Route('/contracts/{id}/pdf', name: 'app_professor_contract_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_PROFESSOR')]
    public function viewContractPdf(Contract $contract): Response
    {
        if ($this->getUser() === null || $this->getUser()->getId() !== $contract->getCoordinator()?->getId()) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à accéder à ce document.");
        }

        $pdfPath = $contract->getPdfSigned() ?: $contract->getPdfUnsigned();

        if (!$pdfPath || !is_file($pdfPath)) {
            throw $this->createNotFoundException("Le PDF de cette convention n'est pas encore disponible.");
        }

        return $this->buildInlinePdfResponse($pdfPath);
    }

    /**
     * VALIDATION DE CONVENTION (Via Workflow)
     * ÉTAPE 3 : Le professeur valide ou refuse la convention
     * Cette méthode gère le lien reçu par email ET le bouton du dashboard.
     */
    #[Route('/contract/{id}/validate', name: 'app_professor_validate_contract', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_PROFESSOR')]
    public function validateContract(
        Contract $contract,
        Request $request,
        EntityManagerInterface $entityManager,
        Registry $workflowRegistry
    ): Response
    {
        if ($this->getUser() !== $contract->getCoordinator()) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorise a valider cette convention.");
        }

        // On récupère le workflow associé à l'entité Contract
        $workflow = $workflowRegistry->get($contract);

        if ($request->isMethod('POST')) {
            // SÉCURITÉ CSRF : Assure-toi que ton formulaire envoie bien un token
            if (!$this->isCsrfTokenValid('validate_contract'.$contract->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Jeton de sécurité invalide.');
                return $this->redirectToRoute('app_home');
            }

            $action = $request->request->get('action');
            $rejectionReason = trim((string) $request->request->get('rejection_reason', ''));

            try {
                if ($action === 'validate' && $workflow->can($contract, 'validate_by_prof')) {
                    $contract->setProfessorRejectionReason(null);
                    $workflow->apply($contract, 'validate_by_prof');
                    $this->addFlash('success', 'La convention a été validée pédagogiquement avec succès ! Elle part à la DDF.');
                } elseif ($action === 'refuse' && $workflow->can($contract, 'refuse_subject')) {
                    $contract->setProfessorRejectionReason($rejectionReason !== '' ? $rejectionReason : 'Le professeur référent a refusé la collecte. Merci de vérifier les informations saisies.');
                    $workflow->apply($contract, 'refuse_subject');
                    $this->addFlash('warning', 'La collecte a été refusée. L\'étudiant verra le rejet et le motif sur sa convention.');
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

            // Sinon on va à l'accueil
            return $this->redirectToRoute('app_home');
        }

        // Si c'est un GET, on affiche la page de confirmation (template: professor/validate.html.twig)
        return $this->render('professor/validate.html.twig', [
            'contract' => $contract,
            'can_validate' => $workflow->can($contract, 'validate_by_prof'),
            'can_refuse' => $workflow->can($contract, 'refuse_subject'),
        ]);
    }

    private function buildInlinePdfResponse(string $pdfPath): BinaryFileResponse
    {
        return (new BinaryFileResponse($pdfPath))
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($pdfPath));
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
