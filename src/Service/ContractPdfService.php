<?php

namespace App\Service;

use App\Entity\Contract;
use App\Entity\InternshipDate;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Twig\Environment;

class ContractPdfService
{
    private const DOCX_TEMPLATE = 'Conventions de stage-template-fr.docx';

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
        $templatePath = $this->projectDir . '/' . self::DOCX_TEMPLATE;

        if (is_file($templatePath)) {
            return $this->generatePdfFromDocxTemplate($contract, $templatePath, $path);
        }

        return $this->generatePdfFromHtml($contract, $path);
    }

    private function generatePdfFromHtml(Contract $contract, string $path): string
    {
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

        try {
            $response = $this->httpClient->request('POST', rtrim($this->gotenbergUrl, '/') . '/forms/chromium/convert/html', [
                'headers' => array_merge($formData->getPreparedHeaders()->toArray(), [
                    'Gotenberg-Output-Filename' => sprintf('contract-%d', $contract->getId()),
                ]),
                'body' => $formData->bodyToIterable(),
            ]);
        } catch (TransportException $exception) {
            throw $this->createUnavailableGotenbergException($exception);
        }

        try {
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Erreur Gotenberg lors de la generation du PDF : ' . $response->getContent(false));
            }

            $this->filesystem->dumpFile($path, $response->getContent());
        } catch (TransportException $exception) {
            throw $this->createUnavailableGotenbergException($exception);
        }

        return $path;
    }

    private function generatePdfFromDocxTemplate(Contract $contract, string $templatePath, string $pdfPath): string
    {
        $docxPath = sprintf('%s/var/contracts/unsigned/contract-%d.docx', $this->projectDir, $contract->getId());
        $this->fillDocxTemplate($templatePath, $docxPath, $this->buildDocxReplacements($contract));

        $formData = new FormDataPart([
            'files' => DataPart::fromPath($docxPath, basename($docxPath), 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ]);

        try {
            $response = $this->httpClient->request('POST', rtrim($this->gotenbergUrl, '/') . '/forms/libreoffice/convert', [
                'headers' => array_merge($formData->getPreparedHeaders()->toArray(), [
                    'Gotenberg-Output-Filename' => sprintf('contract-%d', $contract->getId()),
                ]),
                'body' => $formData->bodyToIterable(),
            ]);
        } catch (TransportException $exception) {
            throw $this->createUnavailableGotenbergException($exception);
        }

        try {
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Erreur Gotenberg lors de la conversion du DOCX : ' . $response->getContent(false));
            }

            $this->filesystem->dumpFile($pdfPath, $response->getContent());
        } catch (TransportException $exception) {
            throw $this->createUnavailableGotenbergException($exception);
        }

        return $pdfPath;
    }

    private function createUnavailableGotenbergException(TransportException $exception): \RuntimeException
    {
        return new \RuntimeException(sprintf(
            'Service Gotenberg indisponible sur %s. Demarrez Gotenberg ou configurez GOTENBERG_URL. Detail: %s',
            rtrim($this->gotenbergUrl, '/'),
            $exception->getMessage()
        ), previous: $exception);
    }

    /**
     * @param array<string, string> $replacements
     */
    private function fillDocxTemplate(string $templatePath, string $outputPath, array $replacements): void
    {
        $this->filesystem->copy($templatePath, $outputPath, true);

        $zip = new \ZipArchive();
        if ($zip->open($outputPath) !== true) {
            throw new \RuntimeException('Impossible d ouvrir le modele DOCX.');
        }

        $documentXml = $zip->getFromName('word/document.xml');
        if (!is_string($documentXml)) {
            $zip->close();
            throw new \RuntimeException('Le contenu du modele DOCX est invalide.');
        }

        $updatedDocumentXml = $this->replacePlaceholdersInXml($documentXml, $replacements);
        $zip->addFromString('word/document.xml', $updatedDocumentXml);
        $zip->close();
    }

    /**
     * @param array<string, string> $replacements
     */
    private function replacePlaceholdersInXml(string $xml, array $replacements): string
    {
        foreach ($replacements as $placeholder => $value) {
            $escapedValue = $this->escapeXml($value);

            $xml = preg_replace(
                $this->buildBrokenPlaceholderPattern($placeholder),
                $escapedValue,
                $xml
            ) ?? $xml;

            $xml = str_replace('${' . $placeholder . '}', $escapedValue, $xml);
        }

        return $xml;
    }

    private function buildBrokenPlaceholderPattern(string $placeholder): string
    {
        $characters = preg_split('//u', '${' . $placeholder . '}', -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $pattern = '';

        foreach ($characters as $character) {
            $pattern .= preg_quote($character, '#') . '(?:</w:t></w:r><w:r[^>]*><w:rPr>.*?</w:rPr><w:t(?: [^>]*)?>)?';
        }

        return '#' . $pattern . '#s';
    }

    /**
     * @return array<string, string>
     */
    private function buildDocxReplacements(Contract $contract): array
    {
        $student = $contract->getStudent();
        $organisation = $contract->getOrganisation();
        $tutor = $contract->getTutor();
        $coordinator = $contract->getCoordinator();
        $level = $student?->getLevel();
        [$respLastname, $respFirstname] = $this->splitName($organisation?->getRespName());

        return [
            'levelCode' => (string) ($level?->getLevelCode() ?? ''),
            'levelName' => (string) ($level?->getLevelName() ?? ''),
            'organisationName' => (string) ($organisation?->getName() ?? ''),
            'respLastname' => $respLastname,
            'respFirstname' => $respFirstname,
            'addressHq' => (string) ($organisation?->getAddressHq() ?? ''),
            'postalCodeHq' => (string) ($organisation?->getPostalCodeHq() ?? ''),
            'cityHq' => (string) ($organisation?->getCityHq() ?? ''),
            'countryHq' => 'France',
            'respPhone' => (string) ($organisation?->getRespPhone() ?? ''),
            'respEmail' => (string) ($organisation?->getRespEmail() ?? ''),
            'internshipPlaceName' => (string) ($organisation?->getName() ?? ''),
            'addressInternship' => (string) ($organisation?->getAddressInternship() ?? ''),
            'postalCodeInternship' => (string) ($organisation?->getPostalCodeInternship() ?? ''),
            'cityInternship' => (string) ($organisation?->getCityInternship() ?? ''),
            'countryInternship' => 'France',
            'siret' => '',
            'insuranceName' => (string) ($organisation?->getInsuranceName() ?? ''),
            'insuranceContract' => (string) ($organisation?->getInsuranceContract() ?? ''),
            'tutorName' => trim((string) ($tutor?->getLastname() ?? '') . ' ' . (string) ($tutor?->getFirstname() ?? '')),
            'tutorFunction' => (string) ($organisation?->getRespFunction() ?? ''),
            'tutorPhone' => (string) ($tutor?->getTelMobile() ?: $tutor?->getTelOther() ?: ''),
            'tutorEmail' => (string) ($tutor?->getEmail() ?? ''),
            'studentLastname' => (string) ($student?->getLastname() ?? ''),
            'studentFirstname' => (string) ($student?->getFirstname() ?? ''),
            'internshipDates' => $this->formatInternshipDates($contract),
            'nbOpenedDays' => (string) $this->countOpenedDays($contract),
            'nbWeeks' => (string) $this->countWeeks($contract),
            'profLastname' => (string) ($coordinator?->getLastname() ?? ''),
            'profFirstname' => (string) ($coordinator?->getFirstname() ?? ''),
            'nbWeeklyHours' => $this->formatWeeklyHours($contract),
            'plannedActivities' => $this->normalizeMultilineText((string) ($contract->getPlannedActivities() ?? '')),
        ];
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(?string $fullName): array
    {
        $fullName = trim((string) $fullName);
        if ($fullName === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $fullName) ?: [];
        if (count($parts) === 1) {
            return [$parts[0], ''];
        }

        $lastname = array_pop($parts);
        $firstname = implode(' ', $parts);

        return [$lastname, $firstname];
    }

    private function formatInternshipDates(Contract $contract): string
    {
        $parts = [];

        foreach ($contract->getInternshipDates()->toArray() as $internshipDate) {
            if (!$internshipDate instanceof InternshipDate || !$internshipDate->getStartDate() || !$internshipDate->getEndDate()) {
                continue;
            }

            $parts[] = sprintf(
                'du %s au %s',
                $internshipDate->getStartDate()->format('d/m/Y'),
                $internshipDate->getEndDate()->format('d/m/Y')
            );
        }

        return implode(', ', $parts);
    }

    private function countOpenedDays(Contract $contract): int
    {
        $count = 0;

        foreach ($contract->getWorkHours() as $schedule) {
            if (!is_array($schedule)) {
                continue;
            }

            $hasMorning = !empty($schedule['m_start']) && !empty($schedule['m_end']);
            $hasAfternoon = !empty($schedule['am_start']) && !empty($schedule['am_end']);

            if ($hasMorning || $hasAfternoon) {
                ++$count;
            }
        }

        return $count;
    }

    private function countWeeks(Contract $contract): int
    {
        $days = 0;

        foreach ($contract->getInternshipDates()->toArray() as $internshipDate) {
            if (!$internshipDate instanceof InternshipDate || !$internshipDate->getStartDate() || !$internshipDate->getEndDate()) {
                continue;
            }

            $interval = $internshipDate->getStartDate()->diff($internshipDate->getEndDate());
            $days += $interval->days + 1;
        }

        return $days > 0 ? (int) ceil($days / 7) : 0;
    }

    private function formatWeeklyHours(Contract $contract): string
    {
        $totalMinutes = 0;

        foreach ($contract->getWorkHours() as $schedule) {
            if (!is_array($schedule)) {
                continue;
            }

            $totalMinutes += $this->durationInMinutes($schedule['m_start'] ?? null, $schedule['m_end'] ?? null);
            $totalMinutes += $this->durationInMinutes($schedule['am_start'] ?? null, $schedule['am_end'] ?? null);
        }

        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        return $minutes > 0 ? sprintf('%dh%02d', $hours, $minutes) : sprintf('%dh', $hours);
    }

    private function durationInMinutes(?string $start, ?string $end): int
    {
        if (!$start || !$end) {
            return 0;
        }

        $startDate = \DateTimeImmutable::createFromFormat('H:i', $start);
        $endDate = \DateTimeImmutable::createFromFormat('H:i', $end);

        if (!$startDate || !$endDate || $endDate <= $startDate) {
            return 0;
        }

        return (int) (($endDate->getTimestamp() - $startDate->getTimestamp()) / 60);
    }

    private function normalizeMultilineText(string $value): string
    {
        return preg_replace("/\r\n|\r|\n/", ' ', trim($value)) ?? trim($value);
    }
}
