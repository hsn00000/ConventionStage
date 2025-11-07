<?php

namespace App\Entity;

use App\Repository\TutorRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TutorRepository::class)]
class Tutor extends User
{
    #[ORM\Column(length: 30)]
    private ?string $telMobile = null;

    #[ORM\Column(length: 30)]
    private ?string $telOther = null;

    public function getTelMobile(): ?string
    {
        return $this->telMobile;
    }

    public function setTelMobile(string $telMobile): static
    {
        $this->telMobile = $telMobile;

        return $this;
    }

    public function getTelOther(): ?string
    {
        return $this->telOther;
    }

    public function setTelOther(string $telOther): static
    {
        $this->telOther = $telOther;

        return $this;
    }
}
