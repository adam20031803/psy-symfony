<?php

// src/Entity/PostLike.php

namespace App\Entity;

use App\Repository\PostLikeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité mappée sur la table post_like (likes/dislikes par utilisateur).
 * Une contrainte UNIQUE (post_id, user_id) garantit un seul vote par utilisateur par post.
 */
#[ORM\Entity(repositoryClass: PostLikeRepository::class)]
#[ORM\Table(name: 'post_like')]
#[ORM\UniqueConstraint(name: 'unique_user_post', columns: ['post_id', 'user_id'])]
class PostLike
{
    public const TYPE_LIKE    = 'like';
    public const TYPE_DISLIKE = 'dislike';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'likes')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Post $post = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    /** Valeur : 'like' ou 'dislike' */
    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'like'])]
    private string $type = self::TYPE_LIKE;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    public function getId(): ?int { return $this->id; }

    public function getPost(): ?Post { return $this->post; }
    public function setPost(?Post $post): static { $this->post = $post; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }
}
