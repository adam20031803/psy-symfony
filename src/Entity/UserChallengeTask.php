<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_challenge_task')]
class UserChallengeTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: ChallengeTask::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?ChallengeTask $task = null;

    #[ORM\Column]
    private bool $isDone = true;

    #[ORM\Column]
    private \DateTimeImmutable $completedAt;

    public function __construct()
    {
        $this->completedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getTask(): ?ChallengeTask { return $this->task; }
    public function setTask(?ChallengeTask $task): static { $this->task = $task; return $this; }

    public function isDone(): bool { return $this->isDone; }
    public function setIsDone(bool $isDone): static { $this->isDone = $isDone; return $this; }

    public function getCompletedAt(): \DateTimeImmutable { return $this->completedAt; }
    public function setCompletedAt(\DateTimeImmutable $completedAt): static { $this->completedAt = $completedAt; return $this; }
}
