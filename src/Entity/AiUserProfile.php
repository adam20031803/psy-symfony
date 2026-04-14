<?php

namespace App\Entity;

use App\Repository\AiUserProfileRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AiUserProfileRepository::class)]
#[ORM\Table(name: 'ai_user_profile')]
class AiUserProfile
{
    const LEVEL_NAMES = [
        1 => 'Novice',
        2 => 'Explorateur',
        3 => 'Progressif',
        4 => 'Engagé',
        5 => 'Champion',
        6 => 'Expert',
        7 => 'Maître',
        8 => 'Légende',
    ];

    const XP_PER_LEVEL = 100;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $wakeUpTime = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $sleepTime = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $workSchedule = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $exerciseFrequency = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $stressTriggers = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motivationDrivers = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $topChallenges = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $achievements = null;

    // ✅ CORRECTION : 'values' renommé en 'personalValues'
    #[ORM\Column(type: 'text', nullable: true, name: 'personal_values')]
    private ?string $personalValues = null;

    #[ORM\Column(nullable: true)]
    private ?float $currentMoodAvg = null;

    #[ORM\Column]
    private int $streakDays = 0;

    #[ORM\Column]
    private int $totalXp = 0;

    #[ORM\Column]
    private int $level = 1;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $badgesJson = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastCheckinAt = null;

    #[ORM\Column]
    private bool $onboardingComplete = false;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $u): static
    {
        $this->user = $u;
        return $this;
    }

    public function getWakeUpTime(): ?string
    {
        return $this->wakeUpTime;
    }
    public function setWakeUpTime(?string $v): static
    {
        $this->wakeUpTime = $v;
        return $this;
    }

    public function getSleepTime(): ?string
    {
        return $this->sleepTime;
    }
    public function setSleepTime(?string $v): static
    {
        $this->sleepTime = $v;
        return $this;
    }

    public function getWorkSchedule(): ?string
    {
        return $this->workSchedule;
    }
    public function setWorkSchedule(?string $v): static
    {
        $this->workSchedule = $v;
        return $this;
    }

    public function getExerciseFrequency(): ?string
    {
        return $this->exerciseFrequency;
    }
    public function setExerciseFrequency(?string $v): static
    {
        $this->exerciseFrequency = $v;
        return $this;
    }

    public function getStressTriggers(): array
    {
        return $this->stressTriggers ? json_decode($this->stressTriggers, true) : [];
    }
    public function setStressTriggers(array $v): static
    {
        $this->stressTriggers = json_encode($v, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function getMotivationDrivers(): array
    {
        return $this->motivationDrivers ? json_decode($this->motivationDrivers, true) : [];
    }
    public function setMotivationDrivers(array $v): static
    {
        $this->motivationDrivers = json_encode($v, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function getTopChallenges(): array
    {
        return $this->topChallenges ? json_decode($this->topChallenges, true) : [];
    }
    public function setTopChallenges(array $v): static
    {
        $this->topChallenges = json_encode($v, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function getAchievements(): array
    {
        return $this->achievements ? json_decode($this->achievements, true) : [];
    }
    public function setAchievements(array $v): static
    {
        $this->achievements = json_encode($v, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    // ✅ NOUVEAUX GETTERS/SETTERS pour personalValues
    public function getPersonalValues(): array
    {
        return $this->personalValues ? json_decode($this->personalValues, true) : [];
    }
    
    public function setPersonalValues(array $v): static
    {
        $this->personalValues = json_encode($v, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    // ✅ ALIAS pour compatibilité avec l'ancien code (si utilisé ailleurs)
    public function getValues(): array
    {
        return $this->getPersonalValues();
    }
    
    public function setValues(array $v): static
    {
        return $this->setPersonalValues($v);
    }

    public function getCurrentMoodAvg(): ?float
    {
        return $this->currentMoodAvg;
    }
    public function setCurrentMoodAvg(?float $v): static
    {
        $this->currentMoodAvg = $v;
        return $this;
    }

    public function getStreakDays(): int
    {
        return $this->streakDays;
    }
    public function setStreakDays(int $v): static
    {
        $this->streakDays = $v;
        return $this;
    }
    public function incrementStreak(): static
    {
        $this->streakDays++;
        return $this;
    }

    public function getTotalXp(): int
    {
        return $this->totalXp;
    }
    public function addXp(int $xp): static
    {
        $this->totalXp += $xp;
        $this->level = max(1, min(8, (int) floor($this->totalXp / self::XP_PER_LEVEL) + 1));
        return $this;
    }

    public function getLevel(): int
    {
        return $this->level;
    }
    public function getLevelName(): string
    {
        return self::LEVEL_NAMES[$this->level] ?? 'Légende';
    }

    public function getXpForNextLevel(): int
    {
        return ($this->level * self::XP_PER_LEVEL) - $this->totalXp;
    }
    public function getLevelProgress(): int
    {
        $xpInLevel = $this->totalXp % self::XP_PER_LEVEL;
        return (int) (($xpInLevel / self::XP_PER_LEVEL) * 100);
    }

    public function getBadges(): array
    {
        return $this->badgesJson ? json_decode($this->badgesJson, true) : [];
    }
    public function addBadge(string $badge): static
    {
        $badges = $this->getBadges();
        if (!in_array($badge, $badges)) {
            $badges[] = $badge;
        }
        $this->badgesJson = json_encode($badges, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function getLastCheckinAt(): ?\DateTimeImmutable
    {
        return $this->lastCheckinAt;
    }
    public function setLastCheckinAt(?\DateTimeImmutable $v): static
    {
        $this->lastCheckinAt = $v;
        return $this;
    }

    public function isOnboardingComplete(): bool
    {
        return $this->onboardingComplete;
    }
    public function setOnboardingComplete(bool $v): static
    {
        $this->onboardingComplete = $v;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function touch(): static
    {
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}