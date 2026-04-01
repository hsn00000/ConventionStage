<?php

namespace App\Controller;

use App\Entity\InternshipDate;
use App\Entity\InternshipSchedule;
use App\Form\InternshipScheduleType;
use App\Repository\ContractRepository;
use App\Repository\InternshipScheduleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ddf/campaigns')]
#[IsGranted('ROLE_ADMIN')]
class DdfInternshipScheduleController extends AbstractController
{
    #[Route('', name: 'app_ddf_campaign_index', methods: ['GET'])]
    public function index(InternshipScheduleRepository $internshipScheduleRepository): Response
    {
        return $this->render('ddf/campaign_index.html.twig', [
            'campaigns' => $internshipScheduleRepository->findAllOrdered(),
        ]);
    }

    #[Route('/new', name: 'app_ddf_campaign_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $internshipSchedule = new InternshipSchedule();
        $internshipSchedule->addInternshipDate(new InternshipDate());

        return $this->handleForm($request, $internshipSchedule, $entityManager);
    }

    #[Route('/{id}/edit', name: 'app_ddf_campaign_edit', methods: ['GET', 'POST'])]
    public function edit(InternshipSchedule $internshipSchedule, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($internshipSchedule->getInternshipDates()->isEmpty()) {
            $internshipSchedule->addInternshipDate(new InternshipDate());
        }

        return $this->handleForm($request, $internshipSchedule, $entityManager);
    }

    private function handleForm(Request $request, InternshipSchedule $internshipSchedule, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(InternshipScheduleType::class, $internshipSchedule);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($internshipSchedule);
            $entityManager->flush();

            $this->addFlash('success', 'Le planning de stage a ete enregistre.');

            return $this->redirectToRoute('app_ddf_campaign_index');
        }

        return $this->render('ddf/campaign_form.html.twig', [
            'form' => $form->createView(),
            'campaign' => $internshipSchedule,
        ]);
    }

    #[Route('/{id}/sync-contracts', name: 'app_ddf_campaign_sync_contracts', methods: ['POST'])]
    public function syncContracts(
        InternshipSchedule $internshipSchedule,
        Request $request,
        ContractRepository $contractRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('ddf_sync_campaign_contracts' . $internshipSchedule->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_ddf_campaign_index');
        }

        foreach ($contractRepository->findBy(['internshipSchedule' => $internshipSchedule]) as $contract) {
            if ($contract->getStatus() === \App\Entity\Contract::STATUS_SIGNATURE_REQUESTED || $contract->getStatus() === \App\Entity\Contract::STATUS_SIGNED) {
                continue;
            }

            $contract->setPdfUnsigned('');
        }

        $entityManager->flush();

        $this->addFlash('success', 'Les conventions ouvertes relieront desormais les dates mises a jour via ce planning.');

        return $this->redirectToRoute('app_ddf_campaign_index');
    }

    #[Route('/{id}', name: 'app_ddf_campaign_delete', methods: ['POST'])]
    public function delete(InternshipSchedule $internshipSchedule, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('ddf_delete_campaign' . $internshipSchedule->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_ddf_campaign_index');
        }

        foreach ($internshipSchedule->getContracts() as $contract) {
            $contract->setInternshipSchedule(null);
        }

        $entityManager->remove($internshipSchedule);
        $entityManager->flush();

        $this->addFlash('success', 'Le planning de stage a ete supprime.');

        return $this->redirectToRoute('app_ddf_campaign_index');
    }
}
