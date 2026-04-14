<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'challenge_task')]
class ChallengeTask
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Challenge $challenge = null;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    private string $description = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $whyRecommended = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $completionCriteria = null;

    #[ORM\Column]
    private int $difficulty = 1; // 1–5

    #[ORM\Column]
    private int $estimatedMinutes = 15;

    #[ORM\Column]
    private int $points = 50;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\Column]
    private int $progressPct = 0; // 0–100

    #[ORM\Column]
    private bool $done = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters & setters
    public function getId(): ?int { return $this->id; }

    public function getChallenge(): ?Challenge { return $this->challenge; }
    public function setChallenge(?Challenge $c): static { $this->challenge = $c; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $t): static { $this->title = $t; return $this; }

    public function getDescription(): string { return $this->description; }
    public function setDescription(string $d): static { $this->description = $d; return $this; }

    public function getWhyRecommended(): ?string { return $this->whyRecommended; }
    public function setWhyRecommended(?string $w): static { $this->whyRecommended = $w; return $this; }

    public function getCompletionCriteria(): ?string { return $this->completionCriteria; }
    public function setCompletionCriteria(?string $c): static { $this->completionCriteria = $c; return $this; }

    public function getDifficulty(): int { return $this->difficulty; }
    public function setDifficulty(int $d): static { $this->difficulty = $d; return $this; }

    public function getEstimatedMinutes(): int { return $this->estimatedMinutes; }
    public function setEstimatedMinutes(int $m): static { $this->estimatedMinutes = $m; return $this; }

    public function getPoints(): int { return $this->points; }
    public function setPoints(int $p): static { $this->points = $p; return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $s): static { $this->sortOrder = $s; return $this; }

    public function getProgressPct(): int { return $this->progressPct; }
    public function setProgressPct(int $p): static { $this->progressPct = max(0, min(100, $p)); return $this; }

    public function isDone(): bool { return $this->done; }
    public function setDone(bool $d): static { $this->done = $d; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
