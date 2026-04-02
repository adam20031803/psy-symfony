<?php

namespace App\Entity;

use App\Repository\HabitudeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HabitudeRepository::class)]
#[ORM\Table(name: 'habitude')]
class Habitude
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'habitudes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 50)]
    private ?string $frequence = null; // daily | weekly | monthly

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Categorie $categorie = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $isActive = true;

    #[ORM\OneToMany(mappedBy: 'habitude', targetEntity: HabitCompletions::class, orphanRemoval: true)]
    private Collection $completions;

    #[ORM\OneToOne(mappedBy: 'habitude', targetEntity: HabitStreaks::class, cascade: ['persist', 'remove'])]
    private ?HabitStreaks $streak = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->completions = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getFrequence(): ?string { return $this->frequence; }
    public function setFrequence(string $frequence): static { $this->frequence = $frequence; return $this; }

    public function getCategorie(): ?Categorie { return $this->categorie; }
    public function setCategorie(?Categorie $categorie): static { $this->categorie = $categorie; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function isActive(): ?bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getCompletions(): Collection { return $this->completions; }

    public function getStreak(): ?HabitStreaks { return $this->streak; }
    public function setStreak(?HabitStreaks $streak): static { $this->streak = $streak; return $this; }
}
