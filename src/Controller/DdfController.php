<?php

namespace App\Controller;

use App\Entity\Contract;
use App\Repository\ContractRepository;
use App\Service\ContractSignatureService;
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
    public function index(ContractRepository $contractRepository): Response
    {
        return $this->render('ddf/index.html.twig', [
            'contracts_to_validate' => $contractRepository->findPendingDdfValidation(),
            'signature_in_progress' => $contractRepository->findSignatureInProgress(),
            'signed_contracts' => $contractRepository->findSignedContracts(),
        ]);
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
            $contractSignatureService->validateByDdfAndRequestSignature($contract);
            $this->addFlash('success', 'Convention validee par la DDF, PDF genere et demande de signature envoyee.');
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
