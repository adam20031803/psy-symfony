<?php

namespace App\Entity;

use App\Repository\ChallengeCoachRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChallengeCoachRepository::class)]
#[ORM\Table(name: 'challenge_coach')]
class ChallengeCoach
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'coaches')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Challenge $challenge = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $coach = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $assignedAt = null;

    public function __construct()
    {
        $this->assignedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getChallenge(): ?Challenge { return $this->challenge; }
    public function setChallenge(?Challenge $challenge): static { $this->challenge = $challenge; return $this; }

    public function getCoach(): ?User { return $this->coach; }
    public function setCoach(?User $coach): static { $this->coach = $coach; return $this; }

    public function getAssignedAt(): ?\DateTimeImmutable { return $this->assignedAt; }
    public function setAssignedAt(\DateTimeImmutable $assignedAt): static { $this->assignedAt = $assignedAt; return $this; }
}
