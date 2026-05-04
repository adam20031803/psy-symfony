<?php

namespace App\Entity;

use App\Repository\WorkoutRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WorkoutRepository::class)]
#[ORM\Table(name: 'workout')]
class Workout
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $duree = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $niveau = null;

    #[ORM\Column(length: 255, nullable: true, options: ['default' => null])]
    private ?string $image = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getDuree(): ?int { return $this->duree; }
    public function setDuree(int $duree): static { $this->duree = $duree; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getNiveau(): ?string { return $this->niveau; }
    public function setNiveau(string $niveau): static { $this->niveau = $niveau; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
