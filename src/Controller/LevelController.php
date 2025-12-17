<?php

namespace App\Controller; // Notez le sous-namespace Admin pour l'organisation

use App\Entity\Level;
use App\Form\LevelType;
use App\Repository\LevelRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/classes')]
#[IsGranted('ROLE_ADMIN')] // Sécurité : Seul un admin accède à cette classe
class LevelController extends AbstractController
{
    #[Route('/', name: 'app_admin_level_index', methods: ['GET'])]
    public function index(LevelRepository $levelRepository): Response
    {
        return $this->render('admin/level/index.html.twig', [
            'levels' => $levelRepository->findAll(),
        ]);
    }

    #[Route('/ajouter', name: 'app_admin_level_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $level = new Level();
        $form = $this->createForm(LevelType::class, $level);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($level);
            $entityManager->flush();

            $this->addFlash('success', 'La classe a été ajoutée avec succès.');

            return $this->redirectToRoute('app_admin_level_index');
        }

        return $this->render('admin/level/new.html.twig', [
            'level' => $level,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_level_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Level $level, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LevelType::class, $level);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'La classe a été modifiée avec succès.');

            return $this->redirectToRoute('app_admin_level_index');
        }

        return $this->render('admin/level/edit.html.twig', [
            'level' => $level,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_level_delete', methods: ['POST'])]
    public function delete(Request $request, Level $level, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$level->getId(), $request->request->get('_token'))) {
            $entityManager->remove($level);
            $entityManager->flush();
            $this->addFlash('success', 'La classe a été supprimée.');
        }

        return $this->redirectToRoute('app_admin_level_index');
    }
}
