<?php

namespace App\Entity;

use App\Repository\StudentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StudentRepository::class)]
class Student extends User
{
    #[ORM\Column(length: 255)]
    private ?string $personalEmail = null;

    #[ORM\ManyToOne(inversedBy: 'students')]
    #[ORM\JoinColumn(nullable: true)]
    #[Assert\NotNull(message: "Un étudiant doit obligatoirement avoir un niveau.")]
    private ?Level $level = null;

    // --- CORRECTION : AJOUT DE LA PROPRIÉTÉ MANQUANTE ---
    #[ORM\ManyToOne(targetEntity: Professor::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Professor $profReferent = null;
    // ----------------------------------------------------

    /**
     * @var Collection<int, Contract>
     */
    #[ORM\OneToMany(targetEntity: Contract::class, mappedBy: 'student')]
    private Collection $contracts;

    public function __construct()
    {
        parent::__construct();
        $this->contracts = new ArrayCollection();
        $this->setRoles(['ROLE_STUDENT']);
    }

    public function getPersonalEmail(): ?string
    {
        return $this->personalEmail;
    }

    public function setPersonalEmail(string $personalEmail): static
    {
        $this->personalEmail = $personalEmail;

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

    // --- CORRECTION DES GETTERS/SETTERS (CamelCase) ---
    public function getProfReferent(): ?Professor
    {
        return $this->profReferent; // Utilise la propriété déclarée plus haut
    }

    public function setProfReferent(?Professor $profReferent): static
    {
        $this->profReferent = $profReferent;

        return $this;
    }
    // --------------------------------------------------

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
            $contract->setStudent($this);
        }

        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            // set the owning side to null (unless already changed)
            if ($contract->getStudent() === $this) {
                $contract->setStudent(null);
            }
        }

        return $this;
    }
}
