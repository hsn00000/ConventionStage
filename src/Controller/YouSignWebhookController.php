<?php

namespace App\Controller;

use App\Service\ContractSignatureService;
use App\Service\YouSignService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/webhooks/yousign')]
class YouSignWebhookController extends AbstractController
{
    #[Route('', name: 'app_yousign_webhook', methods: ['POST'])]
    public function handle(
        Request $request,
        YouSignService $youSignService,
        ContractSignatureService $contractSignatureService,
    ): Response {
        $payload = $request->getContent();
        $signature = $request->headers->get('X-Yousign-Signature-256');
        $timestamp = $request->headers->get('X-Yousign-Webhook-Timestamp');

        if (!$youSignService->isValidWebhookSignature($payload, $signature, $timestamp)) {
            return new JsonResponse(['error' => 'invalid signature'], Response::HTTP_UNAUTHORIZED);
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            return new JsonResponse(['error' => 'invalid payload'], Response::HTTP_BAD_REQUEST);
        }

        if (($event['event_name'] ?? null) !== 'signature_request.done') {
            return new JsonResponse(['status' => 'ignored'], Response::HTTP_ACCEPTED);
        }

        $signatureRequestId = $event['data']['signature_request']['id'] ?? null;
        if (!$signatureRequestId) {
            return new JsonResponse(['error' => 'missing signature request id'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $contractSignatureService->synchronizeBySignatureRequestId($signatureRequestId);
        } catch (\Throwable $exception) {
            return new JsonResponse(['error' => $exception->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return new JsonResponse(['status' => 'processed']);
    }
}
