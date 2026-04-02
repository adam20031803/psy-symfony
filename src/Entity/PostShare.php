<?php

namespace App\Entity;

use App\Repository\PostShareRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostShareRepository::class)]
#[ORM\Table(name: 'post_share')]
class PostShare
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shares')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Post $post = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $sharedAt = null;

    public function __construct()
    {
        $this->sharedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getPost(): ?Post { return $this->post; }
    public function setPost(?Post $post): static { $this->post = $post; return $this; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getSharedAt(): ?\DateTimeImmutable { return $this->sharedAt; }
    public function setSharedAt(\DateTimeImmutable $sharedAt): static { $this->sharedAt = $sharedAt; return $this; }
}
