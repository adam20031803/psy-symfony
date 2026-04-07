<?php

namespace App\Entity;

use App\Repository\MentalEntryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MentalEntryRepository::class)]
#[ORM\Table(name: 'mental_entry')]
class MentalEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Mood::class, inversedBy: 'mentalEntries')]
    #[ORM\JoinColumn(name: 'mood_id', referencedColumnName: 'id', nullable: false)]
    #[Assert\NotNull(message: 'Sélectionnez un mood !')]
    #[Assert\Type(Mood::class, message: 'L’humeur choisie n’est pas valide (identifiant mood_id / référence incorrecte).')]
    private ?Mood $mood = null;

    #[ORM\Column(name: 'entry_date', type: Types::DATE_IMMUTABLE)]
    #[Assert\NotNull(message: 'La date est obligatoire.')]
    #[Assert\Type(\DateTimeImmutable::class, message: 'La date saisie est invalide.')]
    #[Assert\LessThanOrEqual(value: 'today', message: 'La date ne peut pas être dans le futur.')]
    private ?\DateTimeImmutable $entryDate = null;

    #[ORM\Column(name: 'emotion_level')]
    #[Assert\NotNull(message: 'Le niveau d’émotion est obligatoire.')]
    #[Assert\Type('integer', message: 'Le niveau d’émotion doit être un nombre entier.')]
    #[Assert\Positive(message: 'Le niveau d’émotion doit être un entier strictement positif.')]
    #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'Le niveau doit être entre {{ min }} et {{ max }}.')]
    private ?int $emotionLevel = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Activity est obligatoire.', normalizer: 'trim')]
    #[Assert\Length(min: 2, max: 120, minMessage: 'Au moins {{ limit }} caractères.', maxMessage: 'Maximum {{ limit }} caractères.', normalizer: 'trim')]
    private ?string $activity = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255, normalizer: 'trim')]
    #[Assert\Regex(pattern: '/^$|.*\S.*/u', message: 'La note ne peut pas être uniquement des espaces.')]
    private ?string $note = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMood(): ?Mood
    {
        return $this->mood;
    }

    public function setMood(?Mood $mood): static
    {
        $this->mood = $mood;

        return $this;
    }

    public function getEntryDate(): ?\DateTimeImmutable
    {
        return $this->entryDate;
    }

    public function setEntryDate(?\DateTimeImmutable $entryDate): static
    {
        $this->entryDate = $entryDate;

        return $this;
    }

    public function getEmotionLevel(): ?int
    {
        return $this->emotionLevel;
    }

    public function setEmotionLevel(?int $emotionLevel): static
    {
        $this->emotionLevel = $emotionLevel;

        return $this;
    }

    public function getActivity(): ?string
    {
        return $this->activity;
    }

    public function setActivity(?string $activity): static
    {
        $this->activity = $activity;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }
}
