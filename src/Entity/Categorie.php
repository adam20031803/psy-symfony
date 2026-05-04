<?php

// src/Entity/Categorie.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité mappée sur la table 'categorie'.
 */
#[ORM\Entity]
#[ORM\Table(name: 'categorie')]
class Categorie
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $nom = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $icon = null;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: Post::class)]
    private Collection $posts;

    /**
     * @var Collection<int, Challenge>
     */
    #[ORM\OneToMany(mappedBy: 'categorie', targetEntity: Challenge::class)]
    private Collection $challenges;

    public function __construct()
    {
        $this->posts = new ArrayCollection();
        $this->challenges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setCategorie($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getCategorie() === $this) {
                $post->setCategorie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Challenge>
     */
    public function getChallenges(): Collection
    {
        return $this->challenges;
    }

    public function addChallenge(Challenge $challenge): static
    {
        if (!$this->challenges->contains($challenge)) {
            $this->challenges->add($challenge);
            $challenge->setCategorie($this);
        }

        return $this;
    }

    public function removeChallenge(Challenge $challenge): static
    {
        if ($this->challenges->removeElement($challenge)) {
            // set the owning side to null (unless already changed)
            if ($challenge->getCategorie() === $this) {
                $challenge->setCategorie(null);
            }
        }

        return $this;
    }
}
