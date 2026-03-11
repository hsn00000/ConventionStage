<?php

namespace App\Controller;

use App\Entity\Contract;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/contract')]
class ContractController extends AbstractController
{
    // ... tes autres méthodes (index, show, new, edit, etc.)

    #[Route('/{id}/validate-by-student', name: 'app_contract_validate_by_student', methods: ['POST'])]
    public function validateByStudent(
        Request $request,
        Contract $contract,
        WorkflowInterface $contractWorkflowStateMachine,
        EntityManagerInterface $entityManager
    ): Response {

        // 1. Sécurité : On s'assure que c'est bien l'étudiant concerné qui valide
        // (À adapter si tu as une hiérarchie de rôles ou une logique d'accès spécifique)
        if ($this->getUser() !== $contract->getStudent()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à valider ce contrat.');
        }

        // 2. Sécurité CSRF : On vérifie le token envoyé par le formulaire
        if ($this->isCsrfTokenValid('validate_by_student'.$contract->getId(), $request->request->get('_token'))) {

            // 3. Workflow : On vérifie si la transition est possible
            if ($contractWorkflowStateMachine->can($contract, 'validate_by_student')) {

                // On applique la transition (le statut passera à 'validated_by_student')
                $contractWorkflowStateMachine->apply($contract, 'validate_by_student');

                // On sauvegarde en base de données
                $entityManager->flush();

                // TODO : Ajouter l'envoi d'email au professeur ici

                $this->addFlash('success', 'Le contrat a été validé avec succès et transmis à votre professeur.');
            } else {
                $this->addFlash('error', 'Impossible de valider ce contrat. Vérifiez son statut actuel.');
            }
        } else {
            $this->addFlash('error', 'Jeton CSRF invalide.');
        }

        // Redirection vers la page de détails du contrat ou le tableau de bord de l'étudiant
        return $this->redirectToRoute('app_contract_show', ['id' => $contract->getId()], Response::HTTP_SEE_OTHER);
    }
}
