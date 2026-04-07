<?php

namespace App\Entity;

use App\Repository\ProgramRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProgramRepository::class)]
#[ORM\Table(name: 'program')]
class Program
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: "Le titre est obligatoire.")]
    #[Assert\Length(min: 3, max: 150, minMessage: "Le titre doit avoir au moins {{ limit }} caractères.", maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'objectif est obligatoire.")]
    private ?string $goal = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La durée est obligatoire.")]
    #[Assert\GreaterThanOrEqual(value: 1, message: "La durée doit être au moins 1 semaine.")]
    #[Assert\LessThanOrEqual(value: 52, message: "La durée ne peut pas dépasser 52 semaines.")]
    private ?int $durationWeeks = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le niveau est obligatoire.")]
    #[Assert\Choice(choices: ['Débutant', 'Intermédiaire', 'Avancé'], message: "Niveau invalide.")]
    private ?string $level = null;

    #[ORM\ManyToMany(targetEntity: Exercise::class, inversedBy: 'programs')]
    #[ORM\JoinTable(name: 'program_exercise')]
    private Collection $exercises;

    #[ORM\Column(options: ['default' => false])]
    private bool $isPublished = false;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->exercises = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getGoal(): ?string { return $this->goal; }
    public function setGoal(string $goal): static { $this->goal = $goal; return $this; }

    public function getDurationWeeks(): ?int { return $this->durationWeeks; }
    public function setDurationWeeks(int $durationWeeks): static { $this->durationWeeks = $durationWeeks; return $this; }

    public function getLevel(): ?string { return $this->level; }
    public function setLevel(string $level): static { $this->level = $level; return $this; }

    /** @return Collection<int, Exercise> */
    public function getExercises(): Collection { return $this->exercises; }

    public function addExercise(Exercise $exercise): static
    {
        if (!$this->exercises->contains($exercise)) {
            $this->exercises->add($exercise);
        }
        return $this;
    }

    public function removeExercise(Exercise $exercise): static
    {
        $this->exercises->removeElement($exercise);
        return $this;
    }

    public function isPublished(): bool { return $this->isPublished; }
    public function setIsPublished(bool $isPublished): static { $this->isPublished = $isPublished; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function __toString(): string { return $this->title ?? ''; }
}
