<?php

namespace App\Entity;

use App\Repository\ContractRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity(repositoryClass: ContractRepository::class)]
class Contract
{
    public const STATUS_COLLECTION_SENT = 'collection_sent';
    public const STATUS_FILLED_BY_COMPANY = 'filled_by_company';
    public const STATUS_VALIDATED_BY_STUDENT = 'validated_by_student';
    public const STATUS_VALIDATED_BY_PROF = 'validated_by_prof';
    public const STATUS_VALIDATED_BY_DDF = 'validated_by_ddf';
    public const STATUS_SIGNATURE_REQUESTED = 'signature_requested';
    public const STATUS_SIGNED = 'signed';
    public const STATUS_REFUSED = 'refused';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $status = null;

    #[ORM\Column]
    private ?bool $deplacement = null;

    #[ORM\Column]
    private ?bool $transportFreeTaken = null;

    #[ORM\Column]
    private ?bool $lunchTaken = null;

    #[ORM\Column]
    private ?bool $hostTaken = null;

    #[ORM\Column]
    private ?bool $bonus = null;

    // --- MODIFICATION ICI : Passage en JSON pour le tableau d'horaires ---
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private array $workHours = [];

    #[ORM\Column(type: Types::TEXT)]
    private ?string $plannedActivities = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $professorRejectionReason = null;

    #[ORM\Column(length: 255)]
    private ?string $sharingToken = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $tokenExpDate = null;

    #[ORM\Column(length: 255)]
    private ?string $pdfUnsigned = null;

    #[ORM\Column(length: 255)]
    private ?string $pdfSigned = null;

    #[ORM\Column(nullable: true)]
    private ?float $bonusAmount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $yousignDocumentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $yousignSignatureRequestId = null;

    #[ORM\ManyToOne(inversedBy: 'contracts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Student $student = null;

    #[ORM\ManyToOne(inversedBy: 'contracts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Organisation $organisation = null;

    #[ORM\ManyToOne(inversedBy: 'contracts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Tutor $tutor = null;

    #[ORM\ManyToOne(inversedBy: 'contracts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Professor $coordinator = null;

    #[ORM\ManyToOne(inversedBy: 'contracts')]
    #[ORM\JoinColumn(name: 'stage_campaign_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?InternshipSchedule $internshipSchedule = null;

    public function __construct()
    {
        // --- INITIALISATION DU TABLEAU D'HORAIRES ---
        // Cela permet d'avoir la structure prête pour le formulaire
        $this->workHours = [
            'lundi'    => ['m_start' => null, 'm_end' => null, 'am_start' => null, 'am_end' => null],
            'mardi'    => ['m_start' => null, 'm_end' => null, 'am_start' => null, 'am_end' => null],
            'mercredi' => ['m_start' => null, 'm_end' => null, 'am_start' => null, 'am_end' => null],
            'jeudi'    => ['m_start' => null, 'm_end' => null, 'am_start' => null, 'am_end' => null],
            'vendredi' => ['m_start' => null, 'm_end' => null, 'am_start' => null, 'am_end' => null],
            'samedi'   => ['m_start' => null, 'm_end' => null, 'am_start' => null, 'am_end' => null],
        ];

        // Statut par défaut
        $this->status = self::STATUS_COLLECTION_SENT;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_COLLECTION_SENT => 'Collecte envoyée',
            self::STATUS_FILLED_BY_COMPANY => 'Complétée par l’entreprise',
            self::STATUS_VALIDATED_BY_STUDENT => 'Validée par l’étudiant',
            self::STATUS_VALIDATED_BY_PROF => 'Validée par le professeur',
            self::STATUS_VALIDATED_BY_DDF => 'Validée par la DDF',
            self::STATUS_SIGNATURE_REQUESTED => 'Signature en cours',
            self::STATUS_SIGNED => 'Signée',
            self::STATUS_REFUSED => 'Refusée',
            default => (string) $this->status,
        };
    }

    public function getDeplacement(): ?bool
    {
        return $this->deplacement;
    }

    public function setDeplacement(bool $deplacement): static
    {
        $this->deplacement = $deplacement;

        return $this;
    }

    public function getTransportFreeTaken(): ?bool
    {
        return $this->transportFreeTaken;
    }

    public function setTransportFreeTaken(bool $transportFreeTaken): static
    {
        $this->transportFreeTaken = $transportFreeTaken;

        return $this;
    }

    public function getLunchTaken(): ?bool
    {
        return $this->lunchTaken;
    }

    public function setLunchTaken(bool $lunchTaken): static
    {
        $this->lunchTaken = $lunchTaken;

        return $this;
    }

    public function getHostTaken(): ?bool
    {
        return $this->hostTaken;
    }

    public function setHostTaken(bool $hostTaken): static
    {
        $this->hostTaken = $hostTaken;

        return $this;
    }

    public function getBonus(): ?bool
    {
        return $this->bonus;
    }

    public function setBonus(bool $bonus): static
    {
        $this->bonus = $bonus;

        return $this;
    }

    // --- MODIFICATION DES GETTER/SETTER POUR WORKHOURS ---

    public function getWorkHours(): array
    {
        $normalizedWorkHours = [];

        foreach ($this->workHours as $day => $schedule) {
            if (!is_array($schedule)) {
                $normalizedWorkHours[$day] = [
                    'm_start' => null,
                    'm_end' => null,
                    'am_start' => null,
                    'am_end' => null,
                ];

                continue;
            }

            $normalizedWorkHours[$day] = [
                'm_start' => $this->normalizeWorkHourValue($schedule['m_start'] ?? null),
                'm_end' => $this->normalizeWorkHourValue($schedule['m_end'] ?? null),
                'am_start' => $this->normalizeWorkHourValue($schedule['am_start'] ?? null),
                'am_end' => $this->normalizeWorkHourValue($schedule['am_end'] ?? null),
            ];
        }

        return $normalizedWorkHours;
    }

    public function setWorkHours(array $workHours): static
    {
        $normalizedWorkHours = [];

        foreach ($workHours as $day => $schedule) {
            if (!is_array($schedule)) {
                continue;
            }

            $normalizedWorkHours[$day] = [
                'm_start' => $this->normalizeWorkHourValue($schedule['m_start'] ?? null),
                'm_end' => $this->normalizeWorkHourValue($schedule['m_end'] ?? null),
                'am_start' => $this->normalizeWorkHourValue($schedule['am_start'] ?? null),
                'am_end' => $this->normalizeWorkHourValue($schedule['am_end'] ?? null),
            ];
        }

        $this->workHours = $normalizedWorkHours;

        return $this;
    }

    private function normalizeWorkHourValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('H:i');
        }

        if (is_array($value)) {
            $dateValue = $value['date'] ?? null;
            if (!is_string($dateValue) || trim($dateValue) === '') {
                return null;
            }

            try {
                return (new \DateTimeImmutable($dateValue))->format('H:i');
            } catch (\Throwable) {
                return null;
            }
        }

        if (is_string($value)) {
            $trimmedValue = trim($value);
            if ($trimmedValue === '') {
                return null;
            }

            if (preg_match('/^\d{2}:\d{2}$/', $trimmedValue) === 1) {
                return $trimmedValue;
            }

            try {
                return (new \DateTimeImmutable($trimmedValue))->format('H:i');
            } catch (\Throwable) {
                return $trimmedValue;
            }
        }

        return null;
    }

    // -----------------------------------------------------

    public function getPlannedActivities(): ?string
    {
        return $this->plannedActivities;
    }

    public function setPlannedActivities(?string $plannedActivities): static
    {
        $this->plannedActivities = $plannedActivities ?? '';

        return $this;
    }

    public function getProfessorRejectionReason(): ?string
    {
        return $this->professorRejectionReason;
    }

    public function setProfessorRejectionReason(?string $professorRejectionReason): static
    {
        $this->professorRejectionReason = $professorRejectionReason;

        return $this;
    }

    public function getSharingToken(): ?string
    {
        return $this->sharingToken;
    }

    public function setSharingToken(string $sharingToken): static
    {
        $this->sharingToken = $sharingToken;

        return $this;
    }

    public function getTokenExpDate(): ?\DateTimeInterface
    {
        return $this->tokenExpDate;
    }

    public function setTokenExpDate(?\DateTimeInterface $tokenExpDate): static
    {
        $this->tokenExpDate = $tokenExpDate;

        return $this;
    }

    public function getPdfUnsigned(): ?string
    {
        return $this->pdfUnsigned;
    }

    public function setPdfUnsigned(string $pdfUnsigned): static
    {
        $this->pdfUnsigned = $pdfUnsigned;

        return $this;
    }

    public function getPdfSigned(): ?string
    {
        return $this->pdfSigned;
    }

    public function setPdfSigned(string $pdfSigned): static
    {
        $this->pdfSigned = $pdfSigned;

        return $this;
    }

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): static
    {
        $this->student = $student;

        return $this;
    }

    public function getOrganisation(): ?Organisation
    {
        return $this->organisation;
    }

    public function setOrganisation(?Organisation $organisation): static
    {
        $this->organisation = $organisation;

        return $this;
    }

    public function getTutor(): ?Tutor
    {
        return $this->tutor;
    }

    public function setTutor(?Tutor $tutor): static
    {
        $this->tutor = $tutor;

        return $this;
    }

    public function getCoordinator(): ?Professor
    {
        return $this->coordinator;
    }

    public function setCoordinator(?Professor $coordinator): static
    {
        $this->coordinator = $coordinator;

        return $this;
    }

    public function getInternshipSchedule(): ?InternshipSchedule
    {
        return $this->internshipSchedule;
    }

    public function setInternshipSchedule(?InternshipSchedule $internshipSchedule): static
    {
        $this->internshipSchedule = $internshipSchedule;

        return $this;
    }

    /**
     * @return Collection<int, InternshipDate>
     */
    public function getInternshipDates(): Collection
    {
        if ($this->internshipSchedule instanceof InternshipSchedule) {
            return $this->internshipSchedule->getInternshipDates();
        }

        return new ArrayCollection();
    }

    public function getStageStartDate(): ?\DateTimeInterface
    {
        $startDate = null;

        foreach ($this->getInternshipDates() as $internshipDate) {
            $currentStartDate = $internshipDate->getStartDate();

            if ($currentStartDate && ($startDate === null || $currentStartDate < $startDate)) {
                $startDate = $currentStartDate;
            }
        }

        return $startDate;
    }

    public function getStageEndDate(): ?\DateTimeInterface
    {
        $endDate = null;

        foreach ($this->getInternshipDates() as $internshipDate) {
            $currentEndDate = $internshipDate->getEndDate();

            if ($currentEndDate && ($endDate === null || $currentEndDate > $endDate)) {
                $endDate = $currentEndDate;
            }
        }

        return $endDate;
    }

    public function getBonusAmount(): ?float
    {
        return $this->bonusAmount;
    }

    public function setBonusAmount(?float $bonusAmount): static
    {
        $this->bonusAmount = $bonusAmount;
        return $this;
    }

    public function getYousignDocumentId(): ?string
    {
        return $this->yousignDocumentId;
    }

    public function setYousignDocumentId(?string $yousignDocumentId): static
    {
        $this->yousignDocumentId = $yousignDocumentId;

        return $this;
    }

    public function getYousignSignatureRequestId(): ?string
    {
        return $this->yousignSignatureRequestId;
    }

    public function setYousignSignatureRequestId(?string $yousignSignatureRequestId): static
    {
        $this->yousignSignatureRequestId = $yousignSignatureRequestId;

        return $this;
    }
}
