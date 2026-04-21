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
    private const SIGNATURE_ANCHOR_WIDTH = 150;
    private const SIGNATURE_ANCHOR_HEIGHT = 65;

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
        $this->fillDocxTemplate($templatePath, $docxPath, $contract);

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

    private function fillDocxTemplate(string $templatePath, string $outputPath, Contract $contract): void
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

        $updatedDocumentXml = $this->fillScheduleTableInXml($documentXml, $contract);
        $updatedDocumentXml = $this->replacePlaceholdersInXml($updatedDocumentXml, $this->buildDocxReplacements($contract));
        $updatedDocumentXml = $this->normalizeSignatureAnchors($updatedDocumentXml);
        $zip->addFromString('word/document.xml', $updatedDocumentXml);
        $zip->close();
    }

    private function fillScheduleTableInXml(string $xml, Contract $contract): string
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;

        if (@$dom->loadXML($xml) === false) {
            return $xml;
        }

        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        foreach ($xpath->query('//w:tbl') ?: [] as $table) {
            if (!$table instanceof \DOMElement || !$this->isScheduleTable($xpath, $table)) {
                continue;
            }

            $this->fillScheduleRows($dom, $xpath, $table, $contract->getWorkHours());

            return $dom->saveXML() ?: $xml;
        }

        return $xml;
    }

    private function isScheduleTable(\DOMXPath $xpath, \DOMElement $table): bool
    {
        $texts = [];

        foreach ($xpath->query('.//w:t', $table) ?: [] as $textNode) {
            $texts[] = $textNode->textContent;
        }

        $tableText = implode(' ', $texts);

        return str_contains($tableText, 'Matin')
            && str_contains($tableText, 'Après-midi')
            && str_contains($tableText, 'Lundi')
            && str_contains($tableText, 'Samedi');
    }

    /**
     * @param array<string, array<string, string|null>> $workHours
     */
    private function fillScheduleRows(\DOMDocument $dom, \DOMXPath $xpath, \DOMElement $table, array $workHours): void
    {
        $dayLabels = [
            'lundi' => 'Lundi',
            'mardi' => 'Mardi',
            'mercredi' => 'Mercredi',
            'jeudi' => 'Jeudi',
            'vendredi' => 'Vendredi',
            'samedi' => 'Samedi',
        ];

        foreach ($xpath->query('./w:tr', $table) ?: [] as $row) {
            if (!$row instanceof \DOMElement) {
                continue;
            }

            $cells = iterator_to_array($xpath->query('./w:tc', $row) ?: []);
            if (count($cells) < 3 || !$cells[0] instanceof \DOMElement || !$cells[1] instanceof \DOMElement || !$cells[2] instanceof \DOMElement) {
                continue;
            }

            $dayKey = array_search($this->cellText($xpath, $cells[0]), $dayLabels, true);
            if (!is_string($dayKey)) {
                continue;
            }

            $schedule = $workHours[$dayKey] ?? [];
            $this->setCellText($dom, $xpath, $cells[1], $this->formatTimeRange($schedule['m_start'] ?? null, $schedule['m_end'] ?? null));
            $this->setCellText($dom, $xpath, $cells[2], $this->formatTimeRange($schedule['am_start'] ?? null, $schedule['am_end'] ?? null));
        }
    }

    private function cellText(\DOMXPath $xpath, \DOMElement $cell): string
    {
        $texts = [];

        foreach ($xpath->query('.//w:t', $cell) ?: [] as $textNode) {
            $texts[] = $textNode->textContent;
        }

        return trim(implode('', $texts));
    }

    private function setCellText(\DOMDocument $dom, \DOMXPath $xpath, \DOMElement $cell, string $value): void
    {
        foreach ($xpath->query('.//w:p', $cell) ?: [] as $paragraph) {
            if ($paragraph->parentNode === $cell) {
                $cell->removeChild($paragraph);
            }
        }

        $paragraph = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:p');
        $paragraphProperties = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:pPr');
        $justification = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:jc');
        $justification->setAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:val', 'center');
        $paragraphProperties->appendChild($justification);
        $paragraph->appendChild($paragraphProperties);

        $run = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:r');
        $runProperties = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:rPr');
        $fontSize = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:sz');
        $fontSize->setAttributeNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:val', '20');
        $runProperties->appendChild($fontSize);
        $run->appendChild($runProperties);
        $run->appendChild($dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:t', $value));
        $paragraph->appendChild($run);

        $cell->appendChild($paragraph);
    }

    private function formatTimeRange(?string $start, ?string $end): string
    {
        if (!$start || !$end) {
            return '';
        }

        return sprintf('%s - %s', $start, $end);
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

    private function normalizeSignatureAnchors(string $xml): string
    {
        return preg_replace_callback(
            '/\{\{s([1-9]\d*)\|signature\|\d+\|\d+\}\}/',
            fn (array $matches): string => sprintf(
                '{{s%s|signature|%d|%d}}',
                $matches[1],
                self::SIGNATURE_ANCHOR_WIDTH,
                self::SIGNATURE_ANCHOR_HEIGHT
            ),
            $xml
        ) ?? $xml;
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
