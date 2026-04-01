<?php

namespace App\Entity;

use App\Repository\SessionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SessionRepository::class)]
#[ORM\Table(name: 'stage_campaign')]
class Session
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $name = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'level_id', referencedColumnName: 'id', nullable: false)]
    private ?Level $level = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'sessions')]
    #[ORM\JoinTable(name: 'session_user')]
    private Collection $users;

    /**
     * @var Collection<int, SessionDate>
     */
    #[Assert\Count(min: 1, minMessage: 'Ajoutez au moins une période à la session.')]
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

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

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

    public function getPeriodsLabel(): string
    {
        $labels = [];

        foreach ($this->sessionDates as $sessionDate) {
            if (!$sessionDate->getStartDate() || !$sessionDate->getEndDate()) {
                continue;
            }

            $labels[] = sprintf(
                '%s au %s',
                $sessionDate->getStartDate()->format('d/m/Y'),
                $sessionDate->getEndDate()->format('d/m/Y')
            );
        }

        return implode(' ; ', $labels);
    }

    public function __toString(): string
    {
        $levelName = $this->level?->getLevelName();

        if ($levelName) {
            return sprintf('%s - %s', $this->name ?? 'Session', $levelName);
        }

        return (string) $this->name;
    }
}
