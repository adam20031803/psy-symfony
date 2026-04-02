<?php

namespace App\Entity;

use App\Repository\ChallengeRecompenseRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ChallengeRecompenseRepository::class)]
#[ORM\Table(name: 'challenge_recompense')]
class ChallengeRecompense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'recompenses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Challenge $challenge = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Recompense $recompense = null;

    public function getId(): ?int { return $this->id; }

    public function getChallenge(): ?Challenge { return $this->challenge; }
    public function setChallenge(?Challenge $challenge): static { $this->challenge = $challenge; return $this; }

    public function getRecompense(): ?Recompense { return $this->recompense; }
    public function setRecompense(?Recompense $recompense): static { $this->recompense = $recompense; return $this; }
}
