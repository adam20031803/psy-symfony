<?php

namespace App\Entity;

use App\Repository\WorkoutPlanRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkoutPlanRepository::class)]
#[ORM\Table(name: 'workout_plan')]
class WorkoutPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Workout $workout = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $datePlanifie = null;

    #[ORM\Column(length: 50, options: ['default' => 'planifie'])]
    private ?string $statut = 'planifie'; // planifie | complete | annule

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getWorkout(): ?Workout { return $this->workout; }
    public function setWorkout(?Workout $workout): static { $this->workout = $workout; return $this; }

    public function getDatePlanifie(): ?\DateTimeInterface { return $this->datePlanifie; }
    public function setDatePlanifie(\DateTimeInterface $datePlanifie): static { $this->datePlanifie = $datePlanifie; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
