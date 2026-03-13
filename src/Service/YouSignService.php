<?php

namespace App\Service;

use App\Entity\Contract;
use App\Repository\ParametersRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class YouSignService
{
    private const YOUSIGN_API_URL = 'https://api-sandbox.yousign.app/v3';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly ParametersRepository $parametersRepository,
        #[Autowire('%env(YOUSIGN_API_KEY)%')] private readonly string $apiKey,
        #[Autowire('%env(default::YOUSIGN_WEBHOOK_SECRET)%')] private readonly ?string $webhookSecret = null,
    ) {
    }

    /**
     * @return array{signature_request_id: string, document_id: string}
     */
    public function initiateSignatureRequest(Contract $contract, string $pdfPath): array
    {
        $coordinator = $contract->getCoordinator();
        if (!$coordinator?->getEmail()) {
            throw new \RuntimeException('Le professeur referent doit avoir un email avant l envoi en signature.');
        }

        $parameters = $this->parametersRepository->findCurrent();
        if (!$parameters?->getProvisorEmail() || !$parameters->getProvisorName()) {
            throw new \RuntimeException('Le proviseur doit etre renseigne dans les parametres avant l envoi en signature.');
        }

        $signatureRequestId = $this->createSignatureRequestDraft($contract);
        $documentId = $this->uploadDocument($signatureRequestId, $pdfPath);

        $this->addSigner($signatureRequestId, [
            'first_name' => $contract->getStudent()->getFirstname(),
            'last_name' => $contract->getStudent()->getLastname(),
            'email' => $contract->getStudent()->getEmail(),
        ]);

        $this->addSigner($signatureRequestId, [
            'first_name' => $contract->getTutor()->getFirstname(),
            'last_name' => $contract->getTutor()->getLastname(),
            'email' => $contract->getTutor()->getEmail(),
        ]);

        $this->addSigner($signatureRequestId, [
            'first_name' => $coordinator->getFirstname(),
            'last_name' => $coordinator->getLastname(),
            'email' => $coordinator->getEmail(),
        ]);

        [$provisorLastName, $provisorFirstName] = $this->splitFullName($parameters->getProvisorName());
        $this->addSigner($signatureRequestId, [
            'first_name' => $provisorFirstName,
            'last_name' => $provisorLastName,
            'email' => $parameters->getProvisorEmail(),
        ]);

        $this->activateSignatureRequest($signatureRequestId);

        return [
            'signature_request_id' => $signatureRequestId,
            'document_id' => $documentId,
        ];
    }

    public function fetchSignatureRequest(string $signatureRequestId): array
    {
        try {
            $response = $this->httpClient->request('GET', self::YOUSIGN_API_URL . '/signature_requests/' . $signatureRequestId, [
                'headers' => $this->jsonHeaders(),
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Erreur YouSign lors de la recuperation de la signature request : ' . $response->getContent(false));
            }

            return $response->toArray();
        } catch (\Throwable $e) {
            $this->logger->error('YouSign Fetch Signature Request Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function downloadSignedDocument(string $signatureRequestId, string $documentId): string
    {
        try {
            $response = $this->httpClient->request('GET', self::YOUSIGN_API_URL . '/signature_requests/' . $signatureRequestId . '/documents/' . $documentId . '/download', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'accept' => 'application/pdf',
                ],
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Erreur YouSign lors du telechargement du document signe : ' . $response->getContent(false));
            }

            return $response->getContent();
        } catch (\Throwable $e) {
            $this->logger->error('YouSign Download Signed Document Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function isSignatureRequestDone(array $signatureRequest): bool
    {
        return ($signatureRequest['status'] ?? null) === 'done';
    }

    public function isValidWebhookSignature(string $payload, ?string $providedSignature, ?string $timestamp): bool
    {
        if (!$this->webhookSecret || !$providedSignature || !$timestamp) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $computedSignature = 'sha256=' . hash_hmac('sha256', $signedPayload, $this->webhookSecret);

        return hash_equals($computedSignature, $providedSignature);
    }

    private function createSignatureRequestDraft(Contract $contract): string
    {
        try {
            $response = $this->httpClient->request('POST', self::YOUSIGN_API_URL . '/signature_requests', [
                'headers' => $this->jsonHeaders(),
                'json' => [
                    'name' => 'Convention de stage - ' . $contract->getStudent()->getLastname(),
                    'delivery_mode' => 'email',
                    'ordered_signers' => true,
                ],
            ]);

            if ($response->getStatusCode() !== 201) {
                throw new \RuntimeException('Erreur YouSign lors de la creation de la signature request : ' . $response->getContent(false));
            }

            return $response->toArray()['id'];
        } catch (\Throwable $e) {
            $this->logger->error('YouSign Create Signature Request Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function uploadDocument(string $signatureRequestId, string $pdfPath): string
    {
        try {
            $formData = new FormDataPart([
                'nature' => 'signable_document',
                'parse_anchors' => 'true',
                'file' => DataPart::fromPath($pdfPath, basename($pdfPath), 'application/pdf'),
            ]);

            $response = $this->httpClient->request('POST', self::YOUSIGN_API_URL . '/signature_requests/' . $signatureRequestId . '/documents', [
                'headers' => array_merge($this->authHeaders(), $formData->getPreparedHeaders()->toArray()),
                'body' => $formData->bodyToIterable(),
            ]);

            if ($response->getStatusCode() !== 201) {
                throw new \RuntimeException('Erreur YouSign lors de l\'upload du document : ' . $response->getContent(false));
            }

            return $response->toArray()['id'];
        } catch (\Throwable $e) {
            $this->logger->error('YouSign Upload Document Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @param array{first_name: ?string, last_name: ?string, email: ?string} $signer
     */
    private function addSigner(string $signatureRequestId, array $signer): void
    {
        try {
            $response = $this->httpClient->request('POST', self::YOUSIGN_API_URL . '/signature_requests/' . $signatureRequestId . '/signers', [
                'headers' => $this->jsonHeaders(),
                'json' => [
                    'info' => [
                        'first_name' => $signer['first_name'],
                        'last_name' => $signer['last_name'],
                        'email' => $signer['email'],
                        'locale' => 'fr',
                    ],
                    'signature_level' => 'electronic_signature',
                    'signature_authentication_mode' => 'no_otp',
                ],
            ]);

            if ($response->getStatusCode() !== 201) {
                throw new \RuntimeException('Erreur YouSign lors de l\'ajout du signataire : ' . $response->getContent(false));
            }
        } catch (\Throwable $e) {
            $this->logger->error('YouSign Add Signer Error: ' . $e->getMessage());
            throw $e;
        }
    }

    private function activateSignatureRequest(string $signatureRequestId): void
    {
        try {
            $response = $this->httpClient->request('POST', self::YOUSIGN_API_URL . '/signature_requests/' . $signatureRequestId . '/activate', [
                'headers' => $this->jsonHeaders(),
            ]);

            if ($response->getStatusCode() !== 201) {
                throw new \RuntimeException('Erreur YouSign lors de l\'activation de la signature request : ' . $response->getContent(false));
            }
        } catch (\Throwable $e) {
            $this->logger->error('YouSign Activate Signature Request Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * @return array<string, string>
     */
    private function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->apiKey,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function jsonHeaders(): array
    {
        return array_merge($this->authHeaders(), [
            'accept' => 'application/json',
            'content-type' => 'application/json',
        ]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];

        if (count($parts) <= 1) {
            return [$fullName, $fullName];
        }

        $lastName = array_pop($parts);
        $firstName = implode(' ', $parts);

        return [$lastName, $firstName];
    }
}
