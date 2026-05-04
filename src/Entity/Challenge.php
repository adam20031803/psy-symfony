<?php

namespace App\Entity;

use App\Repository\ChallengeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ChallengeRepository::class)]
#[ORM\Table(name: 'challenge')]
class Challenge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le titre doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le titre ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[Assert\Regex(
        pattern: '/^\d/',
        match: false,
        message: 'Le titre ne peut pas commencer par un chiffre.'
    )]
    #[Assert\Regex(
        pattern: '/^\d+$/',
        match: false,
        message: 'Le titre ne peut pas être composé uniquement de chiffres.'
    )]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description est obligatoire.')]
    #[Assert\Length(
        min: 3,
        minMessage: 'La description doit contenir au moins {{ limit }} caractères.'
    )]
    private ?string $description = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull(message: 'La date de début est obligatoire.')]
    #[Assert\GreaterThanOrEqual(
        value: "today",
        message: "La date de début doit être aujourd'hui ou ultérieure."
    )]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull(message: 'La date de fin est obligatoire.')]
    #[Assert\GreaterThan(
        propertyPath: 'dateDebut',
        message: 'La date de fin doit être postérieure à la date de début.'
    )]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\ManyToOne(inversedBy: 'challenges')]
    #[ORM\JoinColumn(nullable: true)]
    #[Assert\NotNull(message: 'La catégorie est obligatoire.')]
    private ?Categorie $categorie = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $createdBy = null;

    #[ORM\Column(length: 50, nullable: false, options: ['default' => 'actif'])]
    #[Assert\Choice(
        choices: ['actif', 'termine', 'annule'],
        message: 'Le statut doit être actif, termine ou annule.'
    )]
    private string $statut = 'actif'; // actif | termine | annule

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mediaUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $mediaType = null; // image | video

    /** @var Collection<int, ChallengeChat> */
    #[ORM\OneToMany(mappedBy: 'challenge', targetEntity: ChallengeChat::class, orphanRemoval: true)]
    private Collection $chats;

    /** @var Collection<int, ChallengeCoach> */
    #[ORM\OneToMany(mappedBy: 'challenge', targetEntity: ChallengeCoach::class, orphanRemoval: true)]
    private Collection $coaches;

    /** @var Collection<int, ChallengeRecompense> */
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

    public function getMediaUrl(): ?string { return $this->mediaUrl; }
    public function setMediaUrl(?string $mediaUrl): static { $this->mediaUrl = $mediaUrl; return $this; }

    public function getMediaType(): ?string { return $this->mediaType; }
    public function setMediaType(?string $mediaType): static { $this->mediaType = $mediaType; return $this; }

    public function getAdresse(): ?string { return $this->adresse; }
    public function setAdresse(?string $adresse): static { $this->adresse = $adresse; return $this; }

    public function getChats(): Collection { return $this->chats; }
    public function getCoaches(): Collection { return $this->coaches; }
    public function getRecompenses(): Collection { return $this->recompenses; }
}
