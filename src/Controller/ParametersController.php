<?php

namespace App\Controller;

use App\Entity\Parameters;
use App\Form\ParametersType;
use App\Repository\ParametersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/parametres')]
#[IsGranted('ROLE_ADMIN')]
class ParametersController extends AbstractController
{
    #[Route('', name: 'app_admin_parameters_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ParametersRepository $parametersRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $parameters = $parametersRepository->findCurrent();

        if (!$parameters) {
            $parameters = new Parameters();
        }

        $form = $this->createForm(ParametersType::class, $parameters);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($parameters);
            $entityManager->flush();

            $this->addFlash('success', 'Les paramètres ont été enregistrés.');

            return $this->redirectToRoute('app_admin_parameters_edit');
        }

        return $this->render('admin/parameters/edit.html.twig', [
            'form' => $form,
        ]);
    }
}
