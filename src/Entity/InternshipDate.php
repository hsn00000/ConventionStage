<?php

namespace App\Entity;

use App\Repository\InternshipDateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InternshipDateRepository::class)]
#[ORM\Table(name: 'stage_campaign_period')]
class InternshipDate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $endDate = null;

    #[ORM\ManyToOne(inversedBy: 'internshipDates')]
    #[ORM\JoinColumn(name: 'campaign_id', referencedColumnName: 'id', nullable: false)]
    private ?InternshipSchedule $internshipSchedule = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartDate(): ?\DateTime
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTime $startDate): static
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): ?\DateTime
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTime $endDate): static
    {
        $this->endDate = $endDate;

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
}
