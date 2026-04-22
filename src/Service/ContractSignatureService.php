<?php

namespace App\Service;

use App\Entity\Contract;
use App\Repository\ContractRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Workflow\Registry;

class ContractSignatureService
{
    public function __construct(
        private readonly ContractPdfService $contractPdfService,
        private readonly YouSignService $youSignService,
        private readonly ProvisorNotificationService $provisorNotificationService,
        private readonly Registry $workflowRegistry,
        private readonly EntityManagerInterface $entityManager,
        private readonly ContractRepository $contractRepository,
        private readonly Filesystem $filesystem,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    public function validateByDdfAndRequestSignature(Contract $contract): void
    {
        $this->validateByDdf($contract);
        $this->generatePdfAndRequestSignature($contract);
    }

    public function validateByDdf(Contract $contract): void
    {
        $workflow = $this->workflowRegistry->get($contract);

        if (!$workflow->can($contract, 'validate_by_ddf')) {
            throw new \RuntimeException('Cette convention ne peut pas etre traitee par la DDF dans son etat actuel.');
        }

        $workflow->apply($contract, 'validate_by_ddf');
        $this->entityManager->flush();
    }

    public function refuseByDdf(Contract $contract, string $reason): void
    {
        $workflow = $this->workflowRegistry->get($contract);

        if (!$workflow->can($contract, 'refuse_by_ddf')) {
            throw new \RuntimeException('Cette convention ne peut pas etre refusee par la DDF dans son etat actuel.');
        }

        $contract->setDdfRejectionReason($reason);
        $workflow->apply($contract, 'refuse_by_ddf');
        $this->entityManager->flush();
    }

    public function generatePdfAndRequestSignature(Contract $contract): void
    {
        $workflow = $this->workflowRegistry->get($contract);

        if ($contract->getStatus() !== Contract::STATUS_VALIDATED_BY_DDF) {
            throw new \RuntimeException('La convention doit etre validee par la DDF avant generation et envoi en signature.');
        }

        $pdfPath = $this->contractPdfService->generateUnsignedPdf($contract);
        $contract->setPdfUnsigned($pdfPath);
        $this->entityManager->flush();

        $signatureData = $this->youSignService->initiateSignatureRequest($contract, $pdfPath);
        $contract->setYousignDocumentId($signatureData['document_id']);
        $contract->setYousignSignatureRequestId($signatureData['signature_request_id']);

        if (!$workflow->can($contract, 'request_signature')) {
            throw new \RuntimeException('La demande de signature ne peut pas etre declenchee apres validation DDF.');
        }

        $workflow->apply($contract, 'request_signature');
        $this->entityManager->flush();
        $this->provisorNotificationService->sendSignatureReadyNotification($contract);
    }

    public function synchronizeBySignatureRequestId(string $signatureRequestId): bool
    {
        $contract = $this->contractRepository->findOneBySignatureRequestId($signatureRequestId);

        if (!$contract) {
            throw new \RuntimeException('Aucune convention ne correspond a cette signature request.');
        }

        return $this->synchronizeSignedDocument($contract);
    }

    public function resendSignatureNotifications(Contract $contract): int
    {
        if (!$contract->getYousignSignatureRequestId()) {
            throw new \RuntimeException('Aucune demande de signature YouSign n est associee a cette convention.');
        }

        return $this->youSignService->sendManualReminders($contract->getYousignSignatureRequestId());
    }

    public function synchronizeSignedDocument(Contract $contract): bool
    {
        if (!$contract->getYousignSignatureRequestId() || !$contract->getYousignDocumentId()) {
            throw new \RuntimeException('Informations YouSign manquantes sur la convention.');
        }

        $signatureRequest = $this->youSignService->fetchSignatureRequest($contract->getYousignSignatureRequestId());

        if (!$this->youSignService->isSignatureRequestDone($signatureRequest)) {
            return false;
        }

        if ($contract->getStatus() === Contract::STATUS_SIGNED && $contract->getPdfSigned()) {
            return true;
        }

        $signedPdf = $this->youSignService->downloadSignedDocument(
            $contract->getYousignSignatureRequestId(),
            $contract->getYousignDocumentId()
        );

        $directory = $this->projectDir . '/var/contracts/signed';
        $this->filesystem->mkdir($directory);

        $path = sprintf('%s/contract-%d-signed.pdf', $directory, $contract->getId());
        $this->filesystem->dumpFile($path, $signedPdf);
        $contract->setPdfSigned($path);

        $workflow = $this->workflowRegistry->get($contract);
        if ($workflow->can($contract, 'mark_signed')) {
            $workflow->apply($contract, 'mark_signed');
        }

        $this->entityManager->flush();

        return true;
    }
}
