<?php

namespace App\Entity;

use App\Repository\OrganisationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganisationRepository::class)]
class Organisation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $addressHq = null;

    #[ORM\Column(length: 20)]
    private ?string $postalCodeHq = null;

    #[ORM\Column(length: 150)]
    private ?string $cityHq = null;

    #[ORM\Column(length: 255)]
    private ?string $addressInternship = null;

    #[ORM\Column(length: 20)]
    private ?string $postalCodeInternship = null;

    #[ORM\Column(length: 150)]
    private ?string $cityInternship = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column(length: 255)]
    private ?string $respName = null;

    #[ORM\Column(length: 255)]
    private ?string $respFunction = null;

    #[ORM\Column(length: 255)]
    private ?string $respEmail = null;

    #[ORM\Column(length: 30)]
    private ?string $respPhone = null;

    #[ORM\Column(length: 255)]
    private ?string $insuranceName = null;

    #[ORM\Column(length: 255)]
    private ?string $insuranceContract = null;

    /**
     * @var Collection<int, Contract>
     */
    #[ORM\OneToMany(targetEntity: Contract::class, mappedBy: 'organisation')]
    private Collection $contracts;

    public function __construct()
    {
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

    public function getAddressHq(): ?string
    {
        return $this->addressHq;
    }

    public function setAddressHq(string $addressHq): static
    {
        $this->addressHq = $addressHq;

        return $this;
    }

    public function getPostalCodeHq(): ?string
    {
        return $this->postalCodeHq;
    }

    public function setPostalCodeHq(string $postalCodeHq): static
    {
        $this->postalCodeHq = $postalCodeHq;

        return $this;
    }

    public function getCityHq(): ?string
    {
        return $this->cityHq;
    }

    public function setCityHq(string $cityHq): static
    {
        $this->cityHq = $cityHq;

        return $this;
    }

    public function getAddressInternship(): ?string
    {
        return $this->addressInternship;
    }

    public function setAddressInternship(string $addressInternship): static
    {
        $this->addressInternship = $addressInternship;

        return $this;
    }

    public function getPostalCodeInternship(): ?string
    {
        return $this->postalCodeInternship;
    }

    public function setPostalCodeInternship(string $postalCodeInternship): static
    {
        $this->postalCodeInternship = $postalCodeInternship;

        return $this;
    }

    public function getCityInternship(): ?string
    {
        return $this->cityInternship;
    }

    public function setCityInternship(string $cityInternship): static
    {
        $this->cityInternship = $cityInternship;

        return $this;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function getRespName(): ?string
    {
        return $this->respName;
    }

    public function setRespName(string $respName): static
    {
        $this->respName = $respName;

        return $this;
    }

    public function getRespFunction(): ?string
    {
        return $this->respFunction;
    }

    public function setRespFunction(string $respFunction): static
    {
        $this->respFunction = $respFunction;

        return $this;
    }

    public function getRespEmail(): ?string
    {
        return $this->respEmail;
    }

    public function setRespEmail(string $respEmail): static
    {
        $this->respEmail = $respEmail;

        return $this;
    }

    public function getRespPhone(): ?string
    {
        return $this->respPhone;
    }

    public function setRespPhone(string $respPhone): static
    {
        $this->respPhone = $respPhone;

        return $this;
    }

    public function getInsuranceName(): ?string
    {
        return $this->insuranceName;
    }

    public function setInsuranceName(string $insuranceName): static
    {
        $this->insuranceName = $insuranceName;

        return $this;
    }

    public function getInsuranceContract(): ?string
    {
        return $this->insuranceContract;
    }

    public function setInsuranceContract(string $insuranceContract): static
    {
        $this->insuranceContract = $insuranceContract;

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
            $contract->setOrganisation($this);
        }

        return $this;
    }

    public function removeContract(Contract $contract): static
    {
        if ($this->contracts->removeElement($contract)) {
            // set the owning side to null (unless already changed)
            if ($contract->getOrganisation() === $this) {
                $contract->setOrganisation(null);
            }
        }

        return $this;
    }
}
