<?php

namespace App\Entity;

use App\Repository\MoodRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MoodRepository::class)]
#[ORM\Table(name: 'mood')]
#[UniqueEntity(fields: ['moodName'], message: 'Ce nom d’humeur existe déjà.')]
class Mood
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'mood_name', length: 60, unique: true)]
    #[Assert\NotBlank(message: 'Le nom du mood est obligatoire.', normalizer: 'trim')]
    #[Assert\Length(min: 2, max: 60, minMessage: 'Au moins {{ limit }} caractères.', maxMessage: 'Maximum {{ limit }} caractères.', normalizer: 'trim')]
    private ?string $moodName = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Assert\NotNull(message: 'La date de création est obligatoire.')]
    #[Assert\Type(\DateTimeImmutable::class, message: 'Date de création invalide.')]
    private ?\DateTimeImmutable $createdAt = null;

    /** @var Collection<int, MentalEntry> */
    #[ORM\OneToMany(targetEntity: MentalEntry::class, mappedBy: 'mood', cascade: ['persist'], orphanRemoval: true)]
    private Collection $mentalEntries;

    /** @var Collection<int, MentalTip> */
    #[ORM\OneToMany(targetEntity: MentalTip::class, mappedBy: 'mood', cascade: ['persist'], orphanRemoval: true)]
    private Collection $mentalTips;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->mentalEntries = new ArrayCollection();
        $this->mentalTips = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMoodName(): ?string
    {
        return $this->moodName;
    }

    public function setMoodName(?string $moodName): static
    {
        $this->moodName = $moodName;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** @return Collection<int, MentalEntry> */
    public function getMentalEntries(): Collection
    {
        return $this->mentalEntries;
    }

    /** @return Collection<int, MentalTip> */
    public function getMentalTips(): Collection
    {
        return $this->mentalTips;
    }

    public function __toString(): string
    {
        return $this->moodName ?? '(#'.$this->id.')';
    }
}
