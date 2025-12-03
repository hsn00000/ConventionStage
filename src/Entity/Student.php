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
    #[ORM\JoinColumn(nullable: true)] // <--- 2. Autorise le vide en DB (pour que les profs existent)
    #[Assert\NotNull(message: "Un étudiant doit obligatoirement avoir un niveau.")] // <--- 3. INTERDIT le vide dans l'appli
    private ?Level $level = null;

    #[ORM\ManyToOne(inversedBy: 'studentsReferred')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    #[Assert\NotNull(message: "L'étudiant doit avoir un professeur référent.")] // 2. Sécurité pour l'application
    private ?Professor $prof_referent = null;

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

    public function getProfReferent(): ?Professor
    {
        return $this->prof_referent;
    }

    public function setProfReferent(?Professor $prof_referent): static
    {
        $this->prof_referent = $prof_referent;

        return $this;
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
