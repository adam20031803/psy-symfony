<?php

namespace App\Entity;

use App\Repository\HabitStreaksRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HabitStreaksRepository::class)]
#[ORM\Table(name: 'habit_streaks')]
class HabitStreaks
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'streak')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Habitude $habitude = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $currentStreak = 0;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $longestStreak = 0;

    #[ORM\Column(type: 'date', nullable: true, options: ['default' => null])]
    private ?\DateTimeInterface $lastCompleted = null;

    public function getId(): ?int { return $this->id; }

    public function getHabitude(): ?Habitude { return $this->habitude; }
    public function setHabitude(?Habitude $habitude): static { $this->habitude = $habitude; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getCurrentStreak(): ?int { return $this->currentStreak; }
    public function setCurrentStreak(int $currentStreak): static { $this->currentStreak = $currentStreak; return $this; }

    public function getLongestStreak(): ?int { return $this->longestStreak; }
    public function setLongestStreak(int $longestStreak): static { $this->longestStreak = $longestStreak; return $this; }

    public function getLastCompleted(): ?\DateTimeInterface { return $this->lastCompleted; }
    public function setLastCompleted(?\DateTimeInterface $lastCompleted): static { $this->lastCompleted = $lastCompleted; return $this; }
}
