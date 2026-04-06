<?php

// src/Entity/PostShare.php

namespace App\Entity;

use App\Repository\PostShareRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Mappage exact de la table post_share (reprises / reposts).
 */
#[ORM\Entity(repositoryClass: PostShareRepository::class)]
#[ORM\Table(name: 'post_share')]
class PostShare
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'shares')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?Post $post = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'shared_at', type: 'datetime')]
    private ?\DateTimeInterface $sharedAt = null;

    public function __construct()
    {
        $this->sharedAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getPost(): ?Post { return $this->post; }
    public function setPost(?Post $post): static { $this->post = $post; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getSharedAt(): ?\DateTimeInterface { return $this->sharedAt; }
    public function setSharedAt(\DateTimeInterface $sharedAt): static { $this->sharedAt = $sharedAt; return $this; }
}
