<?php

namespace App\Service;

use App\Entity\Contract;
use App\Entity\ContractDate;
use App\Entity\Session;
use Doctrine\ORM\EntityManagerInterface;

class SessionService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function applySessionToContract(Session $session, Contract $contract): void
    {
        foreach ($contract->getContractDates()->toArray() as $contractDate) {
            $contract->removeContractDate($contractDate);
            $this->entityManager->remove($contractDate);
        }

        foreach ($session->getSessionDates() as $sessionDate) {
            $contractDate = new ContractDate();
            $contractDate->setStartDate(\DateTime::createFromInterface($sessionDate->getStartDate()));
            $contractDate->setEndDate(\DateTime::createFromInterface($sessionDate->getEndDate()));
            $contract->addContractDate($contractDate);
            $this->entityManager->persist($contractDate);
        }

        $contract->setSession($session);
    }

    public function synchronizeOpenContracts(Session $session, iterable $contracts): void
    {
        foreach ($contracts as $contract) {
            if (!$contract instanceof Contract) {
                continue;
            }

            if (in_array($contract->getStatus(), [Contract::STATUS_SIGNATURE_REQUESTED, Contract::STATUS_SIGNED], true)) {
                continue;
            }

            $this->applySessionToContract($session, $contract);
        }
    }
}
