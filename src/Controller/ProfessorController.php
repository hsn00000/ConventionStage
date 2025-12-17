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
    public function show(Professor $professor): Response
    {
        return $this->render('professor/show.html.twig', [
            'professor' => $professor,
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
