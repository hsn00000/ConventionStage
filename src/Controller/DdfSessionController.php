<?php

namespace App\Controller;

use App\Entity\Session;
use App\Entity\SessionDate;
use App\Form\SessionType;
use App\Repository\ContractRepository;
use App\Repository\SessionRepository;
use App\Service\SessionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ddf/campaigns')]
#[IsGranted('ROLE_ADMIN')]
class DdfSessionController extends AbstractController
{
    #[Route('', name: 'app_ddf_campaign_index', methods: ['GET'])]
    public function index(SessionRepository $sessionRepository): Response
    {
        return $this->render('ddf/campaign_index.html.twig', [
            'campaigns' => $sessionRepository->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'app_ddf_campaign_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = new Session();
        $session->addSessionDate(new SessionDate());

        return $this->handleForm($request, $session, $entityManager);
    }

    #[Route('/{id}/edit', name: 'app_ddf_campaign_edit', methods: ['GET', 'POST'])]
    public function edit(Session $session, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($session->getSessionDates()->isEmpty()) {
            $session->addSessionDate(new SessionDate());
        }

        return $this->handleForm($request, $session, $entityManager);
    }

    private function handleForm(Request $request, Session $session, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SessionType::class, $session);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($session);
            $entityManager->flush();

            $this->addFlash('success', 'La campagne de stage a ete enregistree.');

            return $this->redirectToRoute('app_ddf_campaign_index');
        }

        return $this->render('ddf/campaign_form.html.twig', [
            'form' => $form->createView(),
            'campaign' => $session,
        ]);
    }

    #[Route('/{id}/sync-contracts', name: 'app_ddf_campaign_sync_contracts', methods: ['POST'])]
    public function syncContracts(
        Session $session,
        Request $request,
        SessionService $sessionService,
        ContractRepository $contractRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('ddf_sync_campaign_contracts' . $session->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_ddf_campaign_index');
        }

        $sessionService->synchronizeOpenContracts($session, $contractRepository->findBy(['session' => $session]));
        $entityManager->flush();

        $this->addFlash('success', 'Les conventions non encore envoyees en signature ont ete resynchronisees avec la campagne.');

        return $this->redirectToRoute('app_ddf_campaign_index');
    }

    #[Route('/{id}', name: 'app_ddf_campaign_delete', methods: ['POST'])]
    public function delete(Session $session, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('ddf_delete_campaign' . $session->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_ddf_campaign_index');
        }

        foreach ($session->getContracts() as $contract) {
            $contract->setSession(null);
        }

        $entityManager->remove($session);
        $entityManager->flush();

        $this->addFlash('success', 'La campagne de stage a ete supprimee.');

        return $this->redirectToRoute('app_ddf_campaign_index');
    }
}
