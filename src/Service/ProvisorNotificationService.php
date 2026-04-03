<?php

namespace App\Service;

use App\Entity\Contract;
use App\Repository\ParametersRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ProvisorNotificationService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly ParametersRepository $parametersRepository,
    ) {
    }

    public function sendSignatureReadyNotification(Contract $contract): void
    {
        $parameters = $this->parametersRepository->findCurrent();

        if (!$parameters?->getProvisorEmail() || !$parameters->getProvisorName()) {
            throw new \RuntimeException('Le proviseur doit etre renseigne dans les parametres avant l envoi en signature.');
        }

        $this->mailer->send(
            (new TemplatedEmail())
                ->from(new Address('no-reply@lycee-faure.fr', 'Conventio'))
                ->to(new Address($parameters->getProvisorEmail(), $parameters->getProvisorName()))
                ->subject('Convention prete a etre signee')
                ->htmlTemplate('emails/provisor_signature_ready.html.twig')
                ->context([
                    'contract' => $contract,
                    'provisorName' => $parameters->getProvisorName(),
                ])
        );
    }
}
