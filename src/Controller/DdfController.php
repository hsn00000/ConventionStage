<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Repository\ContractRepository;
use App\Service\ContractPdfService;
use App\Service\ContractSignatureService;
use App\Service\YouSignService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/ddf/contracts')]
#[IsGranted('ROLE_ADMIN')]
class DdfController extends AbstractController
{
    #[Route('', name: 'app_ddf_contract_index', methods: ['GET'])]
    public function index(ContractRepository $contractRepository, YouSignService $youSignService): Response
    {
        $signatureInProgress = $contractRepository->findSignatureInProgress();
        $signatureStatuses = [];

        foreach ($signatureInProgress as $contract) {
            try {
                $signatureStatuses[$contract->getId()] = $youSignService->buildSignatureStatusSummary($contract);
            } catch (\Throwable $exception) {
                $signatureStatuses[$contract->getId()] = [
                    'request_status' => null,
                    'request_status_label' => 'Indisponible',
                    'signers' => [],
                    'missing_signers' => [],
                    'error' => $exception->getMessage(),
                ];
            }
        }

        return $this->render('ddf/index.html.twig', [
            'contracts_to_validate' => $contractRepository->findPendingDdfValidation(),
            'validated_contracts' => $contractRepository->findValidatedByDdf(),
            'signature_in_progress' => $signatureInProgress,
            'signature_statuses' => $signatureStatuses,
            'signed_contracts' => $contractRepository->findSignedContracts(),
        ]);
    }

    #[Route('/{id}/generate-pdf', name: 'app_ddf_contract_generate_pdf', methods: ['POST'])]
    public function generatePdf(
        Contract $contract,
        Request $request,
        ContractPdfService $contractPdfService,
        EntityManagerInterface $entityManager,
    ): Response {
        if (!$this->isCsrfTokenValid('ddf_generate_pdf_contract' . $contract->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_ddf_contract_index');
        }

        try {
            $contract->setPdfUnsigned($contractPdfService->generateUnsignedPdf($contract));
            $entityManager->flush();
            $this->addFlash('success', 'Le PDF de la convention a ete genere.');
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Impossible de generer le PDF : ' . $exception->getMessage());
        }

        return $this->redirectToRoute('app_ddf_contract_index');
    }

    #[Route('/{id}/validate', name: 'app_ddf_contract_validate', methods: ['POST'])]
    public function validate(
        Contract $contract,
        Request $request,
        ContractSignatureService $contractSignatureService,
    ): Response {
        if (!$this->isCsrfTokenValid('ddf_validate_contract' . $contract->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_ddf_contract_index');
        }

        try {
            $contractSignatureService->validateByDdf($contract);
            $this->addFlash('success', 'Les informations de la convention ont ete validees par la DDF.');
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Echec de la validation DDF : ' . $exception->getMessage());
        }

        return $this->redirectToRoute('app_ddf_contract_index');
    }

    #[Route('/{id}/generate-and-send', name: 'app_ddf_contract_generate_and_send', methods: ['POST'])]
    public function generateAndSend(
        Contract $contract,
        Request $request,
        ContractSignatureService $contractSignatureService,
    ): Response {
        if (!$this->isCsrfTokenValid('ddf_generate_and_send_contract' . $contract->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_ddf_contract_index');
        }

        try {
            $contractSignatureService->generatePdfAndRequestSignature($contract);
            $this->addFlash('success', 'Convention generee puis envoyee a la signature. Le mail part d abord a l etudiant, puis a l organisme, puis a la proviseure.');
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Echec du traitement DDF: ' . $exception->getMessage());
        }

        return $this->redirectToRoute('app_ddf_contract_index');
    }

    #[Route('/{id}/sync-signed-document', name: 'app_ddf_contract_sync_signed_document', methods: ['POST'])]
    public function syncSignedDocument(
        Contract $contract,
        Request $request,
        ContractSignatureService $contractSignatureService,
    ): Response {
        if (!$this->isCsrfTokenValid('ddf_sync_contract' . $contract->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton CSRF invalide.');

            return $this->redirectToRoute('app_ddf_contract_index');
        }

        try {
            if ($contractSignatureService->synchronizeSignedDocument($contract)) {
                $this->addFlash('success', 'Document signe recupere avec succes.');
            } else {
                $this->addFlash('warning', 'La signature n est pas encore terminee.');
            }
        } catch (\Throwable $exception) {
            $this->addFlash('error', 'Impossible de synchroniser la signature : ' . $exception->getMessage());
        }

        return $this->redirectToRoute('app_ddf_contract_index');
    }

    #[Route('/{id}/pdf', name: 'app_ddf_contract_pdf', methods: ['GET'])]
    public function viewPdf(Contract $contract): Response
    {
        $pdfPath = $contract->getPdfSigned() ?: $contract->getPdfUnsigned();

        if (!$pdfPath || !is_file($pdfPath)) {
            throw $this->createNotFoundException('Le PDF de cette convention n a pas encore ete genere.');
        }

        return (new BinaryFileResponse($pdfPath))
            ->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($pdfPath));
    }
}
