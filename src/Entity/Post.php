<?php

// src/Entity/Post.php

namespace App\Entity;

use App\Repository\PostRepository;
use App\Validator\PostValidator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité mappée exactement sur la table existante 'post'.
 */
#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'post')]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    #[Assert\Callback([PostValidator::class, 'checkBadWords'])]
    private ?string $titre = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Callback([PostValidator::class, 'checkBadWords'])]
    private ?string $contenu = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(name: 'is_anonymous', type: 'boolean', options: ['default' => 0])]
    private bool $isAnonymous = false;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * Association ManyToOne avec l'auteur (utilisateur).
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private ?User $user = null;

    /**
     * Association ManyToOne avec la catégorie.
     */
    #[ORM\ManyToOne(targetEntity: Categorie::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(name: 'categorie_id', referencedColumnName: 'id', nullable: true)]
    #[Assert\NotNull(message: 'Veuillez sélectionner une catégorie.')]
    private ?Categorie $categorie = null;

    /**
     * Les commentaires associés à ce post.
     * @var Collection<int, Commentaire>
     */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Commentaire::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $commentaires;

    /**
     * @var Collection<int, PostLike>
     */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: PostLike::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $likes;

    /**
     * @var Collection<int, PostShare>
     */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: PostShare::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $shares;

    public function __construct()
    {
        $this->commentaires = new ArrayCollection();
        $this->likes        = new ArrayCollection();
        $this->shares       = new ArrayCollection();
    }

    // --- Getters & Setters ---

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;
        return $this;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): static
    {
        $this->contenu = $contenu;
        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function isIsAnonymous(): bool
    {
        return $this->isAnonymous;
    }

    public function setIsAnonymous(bool $isAnonymous): static
    {
        $this->isAnonymous = $isAnonymous;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCategorie(): ?Categorie
    {
        return $this->categorie;
    }

    public function setCategorie(?Categorie $categorie): static
    {
        $this->categorie = $categorie;
        return $this;
    }

    /**
     * @return Collection<int, Commentaire>
     */
    public function getCommentaires(): Collection
    {
        return $this->commentaires;
    }

    public function addCommentaire(Commentaire $commentaire): static
    {
        if (!$this->commentaires->contains($commentaire)) {
            $this->commentaires->add($commentaire);
            $commentaire->setPost($this);
        }

        return $this;
    }

    public function removeCommentaire(Commentaire $commentaire): static
    {
        if ($this->commentaires->removeElement($commentaire)) {
            // set the owning side to null (unless already changed)
            if ($commentaire->getPost() === $this) {
                $commentaire->setPost(null);
            }
        }

        return $this;
    }

    /* ── Likes ─────────────────────────────────────────────────── */

    /** @return Collection<int, PostLike> */
    public function getLikes(): Collection { return $this->likes; }

    public function countLikes(): int
    {
        return $this->likes->filter(fn($l) => $l->getType() === PostLike::TYPE_LIKE)->count();
    }

    public function countDislikes(): int
    {
        return $this->likes->filter(fn($l) => $l->getType() === PostLike::TYPE_DISLIKE)->count();
    }

    public function getUserLike(User $user): ?PostLike
    {
        foreach ($this->likes as $like) {
            if ($like->getUser() === $user) {
                return $like;
            }
        }
        return null;
    }

    /* ── Shares / Reposts ───────────────────────────────────────── */

    /** @return Collection<int, PostShare> */
    public function getShares(): Collection { return $this->shares; }

    public function countShares(): int { return $this->shares->count(); }

    public function hasUserShared(User $user): bool
    {
        foreach ($this->shares as $share) {
            if ($share->getUser() === $user) {
                return true;
            }
        }
        return false;
    }
}
