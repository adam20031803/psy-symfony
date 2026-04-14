<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_recompense')]
class UserRecompense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Recompense::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Recompense $recompense = null;

    #[ORM\ManyToOne(targetEntity: Challenge::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Challenge $sourceChallenge = null;

    #[ORM\Column]
    private \DateTimeImmutable $unlockedAt;

    public function __construct()
    {
        $this->unlockedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getRecompense(): ?Recompense { return $this->recompense; }
    public function setRecompense(?Recompense $recompense): static { $this->recompense = $recompense; return $this; }

    public function getSourceChallenge(): ?Challenge { return $this->sourceChallenge; }
    public function setSourceChallenge(?Challenge $sourceChallenge): static { $this->sourceChallenge = $sourceChallenge; return $this; }

    public function getUnlockedAt(): \DateTimeImmutable { return $this->unlockedAt; }
    public function setUnlockedAt(\DateTimeImmutable $unlockedAt): static { $this->unlockedAt = $unlockedAt; return $this; }
}
