<?php

namespace App\Entity;

use App\Repository\MentalTipRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MentalTipRepository::class)]
#[ORM\Table(name: 'mental_tip')]
class MentalTip
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Mood::class, inversedBy: 'mentalTips')]
    #[ORM\JoinColumn(name: 'mood_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Sélectionnez un mood !')]
    #[Assert\Type(Mood::class, message: 'L’humeur choisie n’est pas valide (référence mood_id incorrecte).')]
    private ?Mood $mood = null;

    #[ORM\Column(name: 'tip_text', length: 255)]
    #[Assert\NotBlank(message: 'Le tip est obligatoire.', normalizer: 'trim')]
    #[Assert\Length(min: 2, max: 255, minMessage: 'Au moins {{ limit }} caractères.', maxMessage: 'Maximum {{ limit }} caractères.', normalizer: 'trim')]
    private ?string $tipText = null;

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

    public function getTipText(): ?string
    {
        return $this->tipText;
    }

    public function setTipText(?string $tipText): static
    {
        $this->tipText = $tipText;

        return $this;
    }
}
