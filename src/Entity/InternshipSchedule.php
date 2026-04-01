<?php

namespace App\Entity;

use App\Repository\InternshipScheduleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InternshipScheduleRepository::class)]
#[ORM\Table(name: 'stage_campaign')]
class InternshipSchedule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'level_id', referencedColumnName: 'id', nullable: false)]
    private ?Level $level = null;

    /**
     * @var Collection<int, InternshipDate>
     */
    #[Assert\Count(min: 1, minMessage: 'Ajoutez au moins une période au planning.')]
    #[ORM\OneToMany(targetEntity: InternshipDate::class, mappedBy: 'internshipSchedule', cascade: ['persist'], orphanRemoval: true)]
    private Collection $internshipDates;

    /**
     * @var Collection<int, Contract>
     */
    #[ORM\OneToMany(targetEntity: Contract::class, mappedBy: 'internshipSchedule')]
    private Collection $contracts;

    public function __construct()
    {
        $this->internshipDates = new ArrayCollection();
        $this->contracts = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getLevel(): ?Level
    {
        return $this->level;
    }

    public function setLevel(?Level $level): static
    {
        $this->level = $level;

        return $this;
    }

    /**
     * @return Collection<int, InternshipDate>
     */
    public function getInternshipDates(): Collection
    {
        return $this->internshipDates;
    }

    public function addInternshipDate(InternshipDate $internshipDate): static
    {
        if (!$this->internshipDates->contains($internshipDate)) {
            $this->internshipDates->add($internshipDate);
            $internshipDate->setInternshipSchedule($this);
        }

        return $this;
    }

    public function removeInternshipDate(InternshipDate $internshipDate): static
    {
        if ($this->internshipDates->removeElement($internshipDate)) {
            if ($internshipDate->getInternshipSchedule() === $this) {
                $internshipDate->setInternshipSchedule(null);
            }
        }

        return $this;
    }

    public function getPeriodsLabel(): string
    {
        $labels = [];

        foreach ($this->internshipDates as $internshipDate) {
            if (!$internshipDate->getStartDate() || !$internshipDate->getEndDate()) {
                continue;
            }

            $labels[] = sprintf(
                '%s au %s',
                $internshipDate->getStartDate()->format('d/m/Y'),
                $internshipDate->getEndDate()->format('d/m/Y')
            );
        }

        return implode(' ; ', $labels);
    }

    /**
     * @return Collection<int, Contract>
     */
    public function getContracts(): Collection
    {
        return $this->contracts;
    }

    public function addContract(Contract $contract): static
    {
        if (!$this->contracts->contains($contract)) {
            $this->contracts->add($contract);
            $contract->setInternshipSchedule($this);
        }

        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            if ($contract->getInternshipSchedule() === $this) {
                $contract->setInternshipSchedule(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        $levelName = $this->level?->getLevelName();

        if ($levelName) {
            return sprintf('%s - %s', $this->name ?? 'Planning', $levelName);
        }

        return (string) $this->name;
    }
}
