<?php

namespace App\Service;

use App\Entity\Contract;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class YouSignService
{
    private const YOUSIGN_API_URL = 'https://api-sandbox.yousign.app/v3';
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
        // Grâce à cet attribut, Symfony va chercher directement dans le fichier .env !
        #[Autowire('%env(YOUSIGN_API_KEY)%')] string $apiKey
    ) {
        $this->apiKey = $apiKey;
    }

    /**
     * Uploade le PDF généré vers YouSign et retourne l'ID du document.
     * @param string $pdfContent Le contenu binaire du PDF
     * @param string $filename Le nom que portera le fichier sur YouSign
     * @return string L'ID du document généré par YouSign
     */
    public function uploadDocument(string $pdfContent, string $filename): string
    {
        try {
            $response = $this->httpClient->request('POST', self::YOUSIGN_API_URL . '/documents', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
                'body' => [
                    'file' => $pdfContent,
                    'name' => $filename,
                    'nature' => 'signable_document',
                ]
            ]);

            // YouSign retourne généralement un code 201 (Created)
            if ($response->getStatusCode() !== 201 && $response->getStatusCode() !== 200) {
                throw new \Exception('Erreur YouSign lors de l\'upload : ' . $response->getContent(false));
            }

            $data = $response->toArray();
            return $data['id'];

        } catch (\Exception $e) {
            $this->logger->error('YouSign Upload Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Crée une demande de signature avec les intervenants liés à la convention.
     * @param Contract $contract L'entité Contract contenant les informations
     * @param string $documentId L'ID du document (obtenu via uploadDocument)
     * @return string L'ID de la demande de signature (signature_request_id)
     */
    public function createSignatureRequest(Contract $contract, string $documentId): string
    {
        try {
            // Création de la liste des signataires
            $signers = [
                // 1. L'Étudiant
                [
                    'info' => [
                        'first_name' => $contract->getStudent()->getFirstname(),
                        'last_name' => $contract->getStudent()->getLastname(),
                        'email' => $contract->getStudent()->getEmail(),
                        'locale' => 'fr'
                    ],
                    'fields' => [
                        [
                            'type' => 'signature',
                            'document_id' => $documentId,
                            'page' => 1, // À ajuster selon la mise en page de ton PDF
                            'width' => 150,
                            'height' => 50,
                            'x' => 50,   // Coordonnées X à ajuster
                            'y' => 50    // Coordonnées Y à ajuster
                        ]
                    ],
                    'signature_level' => 'electronic_signature'
                ],
                // 2. Le Tuteur (Entreprise)
                [
                    'info' => [
                        'first_name' => $contract->getTutor()->getFirstname(),
                        'last_name' => $contract->getTutor()->getLastname(),
                        'email' => $contract->getTutor()->getEmail(),
                        'locale' => 'fr'
                    ],
                    'fields' => [
                        [
                            'type' => 'signature',
                            'document_id' => $documentId,
                            'page' => 1,
                            'width' => 150,
                            'height' => 50,
                            'x' => 250,  // Placé plus loin sur l'axe X
                            'y' => 50
                        ]
                    ],
                    'signature_level' => 'electronic_signature'
                ]
                // Note : Tu pourras ajouter ici la DDF ou le professeur coordinateur
                // en copiant/collant un des blocs ci-dessus.
            ];

            // Construction du payload principal
            $payload = [
                'name' => 'Convention de stage - ' . $contract->getStudent()->getLastname(),
                'delivery_mode' => 'email',
                'documents' => [$documentId],
                'signers' => $signers,
            ];

            // Envoi de la requête à l'API YouSign
            $response = $this->httpClient->request('POST', self::YOUSIGN_API_URL . '/signature_requests', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json'
                ],
                'json' => $payload
            ]);

            if ($response->getStatusCode() !== 201) {
                throw new \Exception('Erreur YouSign lors de la création de la requête : ' . $response->getContent(false));
            }

            $data = $response->toArray();

            // Retourne l'ID de la demande de signature.
            // C'est cet ID que tu devras sauvegarder dans ton entité Contract (ex: $contract->setYousignSignatureId($data['id']))
            return $data['id'];

        } catch (\Exception $e) {
            $this->logger->error('YouSign Signature Request Error: ' . $e->getMessage());
            throw $e;
        }
    }
}
