<?php

namespace App\Entity;

use App\Repository\ChallengeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChallengeRepository::class)]
#[ORM\Table(name: 'challenge')]
class Challenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\ManyToOne(inversedBy: 'challenges')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Categorie $categorie = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column(length: 50, nullable: false, options: ['default' => 'actif'])]
    private string $statut = 'actif'; // actif | termine | annule

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'challenge', targetEntity: ChallengeChat::class, orphanRemoval: true)]
    private Collection $chats;

    #[ORM\OneToMany(mappedBy: 'challenge', targetEntity: ChallengeCoach::class, orphanRemoval: true)]
    private Collection $coaches;

    #[ORM\OneToMany(mappedBy: 'challenge', targetEntity: ChallengeRecompense::class, orphanRemoval: true)]
    private Collection $recompenses;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->chats = new ArrayCollection();
        $this->coaches = new ArrayCollection();
        $this->recompenses = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getDateDebut(): ?\DateTimeInterface { return $this->dateDebut; }
    public function setDateDebut(\DateTimeInterface $dateDebut): static { $this->dateDebut = $dateDebut; return $this; }

    public function getDateFin(): ?\DateTimeInterface { return $this->dateFin; }
    public function setDateFin(\DateTimeInterface $dateFin): static { $this->dateFin = $dateFin; return $this; }

    public function getCategorie(): ?Categorie { return $this->categorie; }
    public function setCategorie(?Categorie $categorie): static { $this->categorie = $categorie; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }

    public function getStatut(): ?string { return $this->statut; }
    public function setStatut(string $statut): static { $this->statut = $statut; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getChats(): Collection { return $this->chats; }
    public function getCoaches(): Collection { return $this->coaches; }
    public function getRecompenses(): Collection { return $this->recompenses; }
}
