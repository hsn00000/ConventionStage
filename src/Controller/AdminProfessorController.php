<?php

namespace App\Controller;

use App\Entity\Professor;
use App\Repository\ProfessorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/professors')]
#[IsGranted('ROLE_ADMIN')]
class AdminProfessorController extends AbstractController
{
    #[Route('', name: 'app_admin_professor_index', methods: ['GET'])]
    public function index(ProfessorRepository $professorRepository): Response
    {
        return $this->render('admin/professor/index.html.twig', [
            'pending'   => $professorRepository->findPendingApproval(),
            'approved'  => $professorRepository->findApproved(),
        ]);
    }

    #[Route('/{id}/approve', name: 'app_admin_professor_approve', methods: ['POST'])]
    public function approve(
        Professor $professor,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('approve_professor' . $professor->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_admin_professor_index');
        }

        $professor->setIsApprovedByAdmin(true);
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            'Le compte de %s %s a été approuvé.',
            $professor->getFirstname(),
            $professor->getLastname()
        ));

        return $this->redirectToRoute('app_admin_professor_index');
    }
}
