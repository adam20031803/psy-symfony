<?php

namespace App\Entity;

use App\Repository\DailyCheckinRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\DBAL\Types\Types;

#[ORM\Entity(repositoryClass: DailyCheckinRepository::class)]
class DailyCheckin
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank(message: 'The checkin date is required.')]
    #[Assert\Type("\DateTimeInterface")]
    private ?\DateTimeImmutable $checkinDate = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'Mood rating must be between {{ min }} and {{ max }}.')]
    private ?int $moodRating = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'Energy level must be between {{ min }} and {{ max }}.')]
    private ?int $energyLevel = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'Productivity level must be between {{ min }} and {{ max }}.')]
    private ?int $productivityLevel = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'Stress level must be between {{ min }} and {{ max }}.')]
    private ?int $stressLevel = null;

    #[ORM\Column]
    #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'Sleep quality must be between {{ min }} and {{ max }}.')]
    private ?int $sleepQuality = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Sleep hours must be zero or a positive number.')]
    #[Assert\Range(min: 0, max: 24, notInRangeMessage: 'Sleep hours must be between {{ min }} and {{ max }}.')]
    private ?float $sleepHours = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $whatWentWell = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $whatCouldImprove = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $gratitude = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $mainChallenges = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $biggestWins = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $additionalNotes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $emotionData = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $aiInsight = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(nullable: true)]
    private ?int $imageSize = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCheckinDate(): ?\DateTimeImmutable
    {
        return $this->checkinDate;
    }

    public function setCheckinDate(?\DateTimeInterface $checkinDate): static
    {
        if ($checkinDate === null) {
            $this->checkinDate = null;

            return $this;
        }

        $this->checkinDate = \DateTimeImmutable::createFromInterface($checkinDate);

        return $this;
    }

    public function getMoodRating(): ?int
    {
        return $this->moodRating;
    }

    public function setMoodRating(?int $moodRating): static
    {
        $this->moodRating = $moodRating;
        return $this;
    }

    public function getEnergyLevel(): ?int
    {
        return $this->energyLevel;
    }

    public function setEnergyLevel(?int $energyLevel): static
    {
        $this->energyLevel = $energyLevel;
        return $this;
    }

    public function getProductivityLevel(): ?int
    {
        return $this->productivityLevel;
    }

    public function setProductivityLevel(?int $productivityLevel): static
    {
        $this->productivityLevel = $productivityLevel;
        return $this;
    }

    public function getStressLevel(): ?int
    {
        return $this->stressLevel;
    }

    public function setStressLevel(?int $stressLevel): static
    {
        $this->stressLevel = $stressLevel;
        return $this;
    }

    public function getSleepQuality(): ?int
    {
        return $this->sleepQuality;
    }

    public function setSleepQuality(?int $sleepQuality): static
    {
        $this->sleepQuality = $sleepQuality;
        return $this;
    }

    public function getSleepHours(): ?float
    {
        return $this->sleepHours;
    }

    public function setSleepHours(?float $sleepHours): static
    {
        $this->sleepHours = $sleepHours;
        return $this;
    }

    public function getWhatWentWell(): ?string
    {
        return $this->whatWentWell;
    }

    public function setWhatWentWell(?string $whatWentWell): static
    {
        $this->whatWentWell = $whatWentWell;
        return $this;
    }

    public function getWhatCouldImprove(): ?string
    {
        return $this->whatCouldImprove;
    }

    public function setWhatCouldImprove(?string $whatCouldImprove): static
    {
        $this->whatCouldImprove = $whatCouldImprove;
        return $this;
    }

    public function getGratitude(): ?string
    {
        return $this->gratitude;
    }

    public function setGratitude(?string $gratitude): static
    {
        $this->gratitude = $gratitude;
        return $this;
    }

    public function getMainChallenges(): ?string
    {
        return $this->mainChallenges;
    }

    public function setMainChallenges(?string $mainChallenges): static
    {
        $this->mainChallenges = $mainChallenges;
        return $this;
    }

    public function getBiggestWins(): ?string
    {
        return $this->biggestWins;
    }

    public function setBiggestWins(?string $biggestWins): static
    {
        $this->biggestWins = $biggestWins;
        return $this;
    }

    public function getAdditionalNotes(): ?string
    {
        return $this->additionalNotes;
    }

    public function setAdditionalNotes(?string $additionalNotes): static
    {
        $this->additionalNotes = $additionalNotes;
        return $this;
    }

    public function getEmotionData(): ?string
    {
        return $this->emotionData;
    }

    public function setEmotionData(?string $emotionData): static
    {
        $this->emotionData = $emotionData;
        return $this;
    }

    public function getAiInsight(): ?string
    {
        return $this->aiInsight;
    }

    public function setAiInsight(?string $aiInsight): static
    {
        $this->aiInsight = $aiInsight;
        return $this;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param mixed $imageFile
     */
    public function setImageFile($imageFile = null): void
    {
        // Manual handling later
    }

    public function getImageFile()
    {
        return null;
    }

    public function setImageName(?string $imageName): void
    {
        $this->imageName = $imageName;
    }

    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    public function setImageSize(?int $imageSize): void
    {
        $this->imageSize = $imageSize;
    }

    public function getImageSize(): ?int
    {
        return $this->imageSize;
    }
}
