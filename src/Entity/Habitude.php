<?php

namespace App\Entity;

use App\Repository\HabitudeRepository;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HabitudeRepository::class)]
class Habitude
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The title cannot be blank.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'The title must be at least {{ limit }} characters long.',
        maxMessage: 'The title cannot be longer than {{ limit }} characters.'
    )]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 1000, maxMessage: 'The description cannot be longer than {{ limit }} characters.')]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'The category cannot be blank.')]
    private ?string $category = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'The frequency type is required.')]
    #[Assert\PositiveOrZero(message: 'The frequency type must be zero or a positive number.')]
    private ?int $frequencyType = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: 'The start date is required.')]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\GreaterThan(propertyPath: 'startDate', message: 'The end date must be after the start date.')]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column]
    private ?bool $active = true;



    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'The current streak must be zero or positive.')]
    private ?int $currentStreak = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'The longest streak must be zero or positive.')]
    private ?int $longestStreak = 0;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'The total completions must be zero or positive.')]
    private ?int $totalCompletions = 0;

    #[ORM\OneToMany(mappedBy: 'habitude', targetEntity: HabitCompletions::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $completions;

    #[ORM\OneToOne(mappedBy: 'habitude', targetEntity: HabitStreaks::class, cascade: ['persist', 'remove'])]
    private ?HabitStreaks $streak = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user = null;

    public function __construct()
    {
        $this->completions = new ArrayCollection();
        $this->active = true;
        $this->currentStreak = 0;
        $this->longestStreak = 0;
        $this->totalCompletions = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
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

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;
        return $this;
    }

    public function getFrequencyType(): ?int
    {
        return $this->frequencyType;
    }

    public function setFrequencyType(?int $frequencyType): static
    {
        $this->frequencyType = $frequencyType;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }



    public function getCurrentStreak(): ?int
    {
        return $this->currentStreak;
    }

    public function setCurrentStreak(?int $currentStreak): static
    {
        $this->currentStreak = $currentStreak;
        return $this;
    }

    public function getLongestStreak(): ?int
    {
        return $this->longestStreak;
    }

    public function setLongestStreak(?int $longestStreak): static
    {
        $this->longestStreak = $longestStreak;
        return $this;
    }

    public function getTotalCompletions(): ?int
    {
        return $this->totalCompletions;
    }

    public function setTotalCompletions(?int $totalCompletions): static
    {
        $this->totalCompletions = $totalCompletions;
        return $this;
    }

    /**
     * @return Collection<int, HabitCompletions>
     */
    public function getCompletions(): Collection
    {
        return $this->completions;
    }

    public function addCompletion(HabitCompletions $completion): static
    {
        if (!$this->completions->contains($completion)) {
            $this->completions->add($completion);
            $completion->setHabitude($this);
        }

        return $this;
    }

    public function removeCompletion(HabitCompletions $completion): static
    {
        if ($this->completions->removeElement($completion)) {
            if ($completion->getHabitude() === $this) {
                $completion->setHabitude(null);
            }
        }

        return $this;
    }

    public function getStreak(): ?HabitStreaks
    {
        return $this->streak;
    }

    public function setStreak(?HabitStreaks $streak): static
    {
        $this->streak = $streak;
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
}
