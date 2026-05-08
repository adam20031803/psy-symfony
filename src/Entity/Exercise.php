<?php

namespace App\Entity;

use App\Repository\ExerciseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExerciseRepository::class)]
#[ORM\Table(name: 'exercise')]
#[ORM\HasLifecycleCallbacks]
class Exercise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(min: 3, max: 100, minMessage: "Le nom doit avoir au moins {{ limit }} caractères.", maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères.")]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "La catégorie est obligatoire.")]
    #[Assert\Choice(choices: ['Cardio', 'Musculation', 'Yoga', 'HIIT', 'Flexibilité'], message: "Catégorie invalide.")]
    private ?string $category = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le niveau est obligatoire.")]
    #[Assert\Choice(choices: ['Débutant', 'Intermédiaire', 'Avancé'], message: "Difficulté invalide.")]
    private ?string $difficulty = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "La durée est obligatoire.")]
    #[Assert\GreaterThanOrEqual(value: 1, message: "La durée doit être au moins 1 minute.")]
    private ?int $duration = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: "Les calories sont obligatoires.")]
    #[Assert\GreaterThanOrEqual(value: 0, message: "Les calories doivent être positives.")]
    private ?int $calories = null;

    #[ORM\Column(length: 500, nullable: true)]
    #[Assert\Url(message: "L'URL de l'image n'est pas valide.")]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToMany(targetEntity: Program::class, mappedBy: 'exercises')]
    private Collection $programs;

    public function __construct()
    {
        $this->programs = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): ?string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getCategory(): ?string { return $this->category; }
    public function setCategory(string $category): static { $this->category = $category; return $this; }

    public function getDifficulty(): ?string { return $this->difficulty; }
    public function setDifficulty(string $difficulty): static { $this->difficulty = $difficulty; return $this; }

    public function getDuration(): ?int { return $this->duration; }
    public function setDuration(int $duration): static { $this->duration = $duration; return $this; }

    public function getCalories(): ?int { return $this->calories; }
    public function setCalories(int $calories): static { $this->calories = $calories; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeInterface $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    /** @return Collection<int, Program> */
    public function getPrograms(): Collection { return $this->programs; }

    public function __toString(): string { return $this->name ?? ''; }
}
