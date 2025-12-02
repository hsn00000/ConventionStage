<?php

namespace App\Entity;

use App\Repository\ParametersRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ParametersRepository::class)]
class Parameters
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $provisorName = null;

    #[ORM\Column(length: 255)]
    private ?string $provisorEmail = null;

    #[ORM\Column(length: 255)]
    private ?string $ddfptName = null;

    #[ORM\Column(length: 255)]
    private ?string $ddfptPhone = null;

    #[ORM\Column(length: 255)]
    private ?string $ddfptEmail = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProvisorName(): ?string
    {
        return $this->provisorName;
    }

    public function setProvisorName(string $provisorName): static
    {
        $this->provisorName = $provisorName;

        return $this;
    }

    public function getProvisorEmail(): ?string
    {
        return $this->provisorEmail;
    }

    public function setProvisorEmail(string $provisorEmail): static
    {
        $this->provisorEmail = $provisorEmail;

        return $this;
    }

    public function getDdfptName(): ?string
    {
        return $this->ddfptName;
    }

    public function setDdfptName(string $ddfptName): static
    {
        $this->ddfptName = $ddfptName;

        return $this;
    }

    public function getDdfptPhone(): ?string
    {
        return $this->ddfptPhone;
    }

    public function setDdfptPhone(string $ddfptPhone): static
    {
        $this->ddfptPhone = $ddfptPhone;

        return $this;
    }

    public function getDdfptEmail(): ?string
    {
        return $this->ddfptEmail;
    }

    public function setDdfptEmail(string $ddfptEmail): static
    {
        $this->ddfptEmail = $ddfptEmail;

        return $this;
    }
}
