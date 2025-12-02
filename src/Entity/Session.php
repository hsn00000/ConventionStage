<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionRepository::class)]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'sessions')]
    private Collection $users;

    /**
     * @var Collection<int, SessionDate>
     */
    #[ORM\OneToMany(targetEntity: SessionDate::class, mappedBy: 'session')]
    private Collection $sessionDates;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->sessionDates = new ArrayCollection();
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

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);

        return $this;
    }

    /**
     * @return Collection<int, SessionDate>
     */
    public function getSessionDates(): Collection
    {
        return $this->sessionDates;
    }

    public function addSessionDate(SessionDate $sessionDate): static
    {
        if (!$this->sessionDates->contains($sessionDate)) {
            $this->sessionDates->add($sessionDate);
            $sessionDate->setSession($this);
        }

        return $this;
    }

    public function removeSessionDate(SessionDate $sessionDate): static
    {
        if ($this->sessionDates->removeElement($sessionDate)) {
            // set the owning side to null (unless already changed)
            if ($sessionDate->getSession() === $this) {
                $sessionDate->setSession(null);
            }
        }

        return $this;
    }
}
