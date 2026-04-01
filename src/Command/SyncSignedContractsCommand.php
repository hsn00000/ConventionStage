<?php

namespace App\Command;

use App\Repository\ContractRepository;
use App\Service\ContractSignatureService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:contracts:sync-signed')]
class SyncSignedContractsCommand extends Command
{
    public function __construct(
        private readonly ContractRepository $contractRepository,
        private readonly ContractSignatureService $contractSignatureService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $contracts = $this->contractRepository->findSignatureInProgress();

        if ($contracts === []) {
            $io->success('Aucune convention en attente de synchronisation.');

            return Command::SUCCESS;
        }

        $syncedCount = 0;

        foreach ($contracts as $contract) {
            try {
                if ($this->contractSignatureService->synchronizeSignedDocument($contract)) {
                    ++$syncedCount;
                }
            } catch (\Throwable $exception) {
                $io->warning(sprintf('Convention #%d: %s', $contract->getId(), $exception->getMessage()));
            }
        }

        $io->success(sprintf('%d convention(s) synchronisee(s).', $syncedCount));

        return Command::SUCCESS;
    }
}
