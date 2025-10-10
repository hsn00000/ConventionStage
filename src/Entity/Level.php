<?php

namespace App\Entity;

use App\Repository\LevelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LevelRepository::class)]
class Level
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private ?string $levelCode = null;

    #[ORM\Column(length: 150)]
    private ?string $levelName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLevelCode(): ?string
    {
        return $this->levelCode;
    }

    public function setLevelCode(string $levelCode): static
    {
        $this->levelCode = $levelCode;

        return $this;
    }

    public function getLevelName(): ?string
    {
        return $this->levelName;
    }

    public function setLevelName(string $levelName): static
    {
        $this->levelName = $levelName;

        return $this;
    }
}
