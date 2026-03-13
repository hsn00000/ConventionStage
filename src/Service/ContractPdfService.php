<?php

namespace App\Service;

use App\Entity\Contract;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

class ContractPdfService
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        private readonly Filesystem $filesystem,
        private readonly HttpClientInterface $httpClient,
        private readonly Environment $twig,
        #[Autowire('%env(GOTENBERG_URL)%')] private readonly string $gotenbergUrl,
    ) {
    }

    public function generateUnsignedPdf(Contract $contract): string
    {
        $directory = $this->projectDir . '/var/contracts/unsigned';
        $this->filesystem->mkdir($directory);

        $path = sprintf('%s/contract-%d.pdf', $directory, $contract->getId());
        $html = $this->twig->render('pdf/contract.html.twig', [
            'contract' => $contract,
        ]);

        $formData = new FormDataPart([
            'files' => new DataPart($html, 'index.html', 'text/html'),
            'paperWidth' => '8.27',
            'paperHeight' => '11.69',
            'marginTop' => '0.4',
            'marginBottom' => '0.6',
            'marginLeft' => '0.5',
            'marginRight' => '0.5',
            'printBackground' => 'true',
        ]);

        $response = $this->httpClient->request('POST', rtrim($this->gotenbergUrl, '/') . '/forms/chromium/convert/html', [
            'headers' => array_merge($formData->getPreparedHeaders()->toArray(), [
                'Gotenberg-Output-Filename' => sprintf('contract-%d', $contract->getId()),
            ]),
            'body' => $formData->bodyToIterable(),
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Erreur Gotenberg lors de la generation du PDF : ' . $response->getContent(false));
        }

        $this->filesystem->dumpFile($path, $response->getContent());

        return $path;
    }
}
