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

    #[ORM\Column(type: Types::TEXT)]
    private ?string $workHours = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $plannedActivities = null;

    #[ORM\Column(length: 255)]
    private ?string $sharingToken = null;

    // --- CORRECTION ---
    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $tokenExpDate = null;
    // --- FIN CORRECTION ---

    #[ORM\Column(length: 255)]
    private ?string $pdfUnsigned = null;

    #[ORM\Column(length: 255)]
    private ?string $pdfSigned = null;

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

    /**
     * @var Collection<int, ContractDate>
     */
    #[ORM\OneToMany(targetEntity: ContractDate::class, mappedBy: 'contract')]
    private Collection $contractDates;

    public function __construct()
    {
        $this->contractDates = new ArrayCollection();
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

    public function getDeplacement(): ?bool
    {
        return $this->deplacement;
    }

    public function setDeplacement(bool $deplacement): static
    {
        $this->deplacement = $deplacement;

        return $this;
    }

    public function isTransportFreeTaken(): ?bool
    {
        return $this->transportFreeTaken;
    }

    public function setTransportFreeTaken(bool $transportFreeTaken): static
    {
        $this->transportFreeTaken = $transportFreeTaken;

        return $this;
    }

    public function isLunchTaken(): ?bool
    {
        return $this->lunchTaken;
    }

    public function setLunchTaken(bool $lunchTaken): static
    {
        $this->lunchTaken = $lunchTaken;

        return $this;
    }

    public function isHostTaken(): ?bool
    {
        return $this->hostTaken;
    }

    public function setHostTaken(bool $hostTaken): static
    {
        $this->hostTaken = $hostTaken;

        return $this;
    }

    public function isBonus(): ?bool
    {
        return $this->bonus;
    }

    public function setBonus(bool $bonus): static
    {
        $this->bonus = $bonus;

        return $this;
    }

    public function getWorkHours(): ?string
    {
        return $this->workHours;
    }

    public function setWorkHours(string $workHours): static
    {
        $this->workHours = $workHours;

        return $this;
    }

    public function getPlannedActivities(): ?string
    {
        return $this->plannedActivities;
    }

    public function setPlannedActivities(string $plannedActivities): static
    {
        $this->plannedActivities = $plannedActivities;

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

    // --- CORRECTION ---
    public function getTokenExpDate(): ?\DateTimeInterface
    {
        return $this->tokenExpDate;
    }

    public function setTokenExpDate(?\DateTimeInterface $tokenExpDate): static
    {
        $this->tokenExpDate = $tokenExpDate;

        return $this;
    }
    // --- FIN CORRECTION ---

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

    /**
     * @return Collection<int, ContractDate>
     */
    public function getContractDates(): Collection
    {
        return $this->contractDates;
    }

    public function addContractDate(ContractDate $contractDate): static
    {
        if (!$this->contractDates->contains($contractDate)) {
            $this->contractDates->add($contractDate);
            $contractDate->setContract($this);
        }

        return $this;
    }

    public function removeContractDate(ContractDate $contractDate): static
    {
        if ($this->contractDates->removeElement($contractDate)) {
            // set the owning side to null (unless already changed)
            if ($contractDate->getContract() === $this) {
                $contractDate->setContract(null);
            }
        }

        return $this;
    }
}
