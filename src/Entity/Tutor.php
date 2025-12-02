<?php

namespace App\Entity;

use App\Repository\TutorRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TutorRepository::class)]
class Tutor extends User
{
    #[ORM\Column(length: 30)]
    private ?string $telMobile = null;

    #[ORM\Column(length: 30)]
    private ?string $telOther = null;

    /**
     * @var Collection<int, Contract>
     */
    #[ORM\OneToMany(targetEntity: Contract::class, mappedBy: 'tutor')]
    private Collection $contracts;

    public function __construct()
    {
        $this->contracts = new ArrayCollection();
    }

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
            $contract->setTutor($this);
        }

        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            // set the owning side to null (unless already changed)
            if ($contract->getTutor() === $this) {
                $contract->setTutor(null);
            }
        }

        return $this;
    }
}
