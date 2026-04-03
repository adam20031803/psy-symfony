<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'post')]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Categorie $categorie = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    private ?string $contenu = null;

    #[ORM\Column(length: 255, nullable: true, options: ['default' => null])]
    private ?string $image = null;

    #[ORM\Column(options: ['default' => false])]
    private ?bool $isAnonymous = false;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['default' => null])]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Commentaire::class, orphanRemoval: true)]
    private Collection $commentaires;

    #[ORM\OneToMany(mappedBy: 'post', targetEntity: PostShare::class, orphanRemoval: true)]
    private Collection $shares;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->commentaires = new ArrayCollection();
        $this->shares = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getCategorie(): ?Categorie { return $this->categorie; }
    public function setCategorie(?Categorie $categorie): static { $this->categorie = $categorie; return $this; }

    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }

    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = $contenu; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(?string $image): static { $this->image = $image; return $this; }

    public function isAnonymous(): ?bool { return $this->isAnonymous; }
    public function setIsAnonymous(bool $isAnonymous): static { $this->isAnonymous = $isAnonymous; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    public function getCommentaires(): Collection { return $this->commentaires; }
    public function getShares(): Collection { return $this->shares; }
}
