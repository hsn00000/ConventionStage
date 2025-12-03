<?php

namespace App\Entity;

use App\Repository\ProfessorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProfessorRepository::class)]
class Professor extends User
{
    /**
     * @var Collection<int, Student>
     */
    // Doit s'appeler 'studentsReferred' pour correspondre à votre 'inversedBy'
    #[ORM\OneToMany(targetEntity: Student::class, mappedBy: 'prof_referent')]
    private Collection $studentsReferred;

    /**
     * @var Collection<int, Contract>
     */
    #[ORM\OneToMany(targetEntity: Contract::class, mappedBy: 'coordinator')]
    private Collection $contracts;

    /**
     * @var Collection<int, Level>
     */
    #[ORM\ManyToMany(targetEntity: Level::class, inversedBy: 'professors')]
    private Collection $sections;

    public function __construct()
    {
        parent::__construct();
        $this->studentsReferred = new ArrayCollection();
        $this->contracts = new ArrayCollection();
        $this->setRoles(['ROLE_PROFESSOR']);
        $this->sections = new ArrayCollection();
    }

    /**
     * @return Collection<int, Student>
     */
    public function getStudentsReferred(): Collection
    {
        return $this->studentsReferred;
    }

    public function addStudentsReferred(Student $student): static
    {
        if (!$this->studentsReferred->contains($student)) {
            $this->studentsReferred->add($student);
            $student->setProfReferent($this);
        }

        return $this;
    }

    public function removeStudentsReferred(Student $student): static
    {
        if ($this->studentsReferred->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getProfReferent() === $this) {
                $student->setProfReferent(null);
            }
        }

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
            $contract->setCoordinator($this);
        }

        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            // set the owning side to null (unless already changed)
            if ($contract->getCoordinator() === $this) {
                $contract->setCoordinator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Level>
     */
    public function getSections(): Collection
    {
        return $this->sections;
    }

    public function addSection(Level $section): static
    {
        if (!$this->sections->contains($section)) {
            $this->sections->add($section);
        }

        return $this;
    }

    public function removeSection(Level $section): static
    {
        $this->sections->removeElement($section);

        return $this;
    }
}
