<?php

namespace App\Entity;

use App\Repository\AiConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Ignore;

#[ORM\Entity(repositoryClass: AiConversationRepository::class)]
#[ORM\Table(name: 'ai_conversation')]
class AiConversation
{
    const PHASE_ONBOARDING  = 'onboarding';
    const PHASE_DAILY       = 'daily';
    const PHASE_MOTIVATION  = 'motivation';
    const PHASE_CRISIS      = 'crisis';
    const PHASE_COACHING    = 'coaching';
    const PHASE_REFLECTION  = 'reflection';

    const PERSONALITY_ACHIEVER  = 'achiever';
    const PERSONALITY_DREAMER   = 'dreamer';
    const PERSONALITY_ANALYST   = 'analyst';
    const PERSONALITY_EMPATH    = 'empath';

    const STYLE_DIRECT     = 'direct';
    const STYLE_GENTLE     = 'gentle';
    const STYLE_HUMOROUS   = 'humorous';
    const STYLE_STRUCTURED = 'structured';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 64, unique: true)]
    #[Ignore]
    private string $sessionToken;

    #[ORM\Column(length: 30)]
    private string $phase = self::PHASE_ONBOARDING;

    #[ORM\Column(nullable: true)]
    private ?int $moodScore = null;

    #[ORM\Column(nullable: true)]
    private ?int $energyLevel = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $personalityType = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $communicationStyle = null;

    #[ORM\Column]
    private int $totalMessages = 0;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $insightsJson = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $goalsJson = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $habitsJson = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $blockersJson = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $strengthsJson = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastActivityAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'conversation', targetEntity: AiMessage::class, orphanRemoval: true)]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    public function __construct()
    {
        $this->createdAt    = new \DateTimeImmutable();
        $this->sessionToken = bin2hex(random_bytes(32));
        $this->messages     = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getSessionToken(): string { return $this->sessionToken; }

    public function getPhase(): string { return $this->phase; }
    public function setPhase(string $phase): static { $this->phase = $phase; return $this; }

    public function getMoodScore(): ?int { return $this->moodScore; }
    public function setMoodScore(?int $v): static { $this->moodScore = $v; return $this; }

    public function getEnergyLevel(): ?int { return $this->energyLevel; }
    public function setEnergyLevel(?int $v): static { $this->energyLevel = $v; return $this; }

    public function getPersonalityType(): ?string { return $this->personalityType; }
    public function setPersonalityType(?string $v): static { $this->personalityType = $v; return $this; }

    public function getCommunicationStyle(): ?string { return $this->communicationStyle; }
    public function setCommunicationStyle(?string $v): static { $this->communicationStyle = $v; return $this; }

    public function getTotalMessages(): int { return $this->totalMessages; }
    public function incrementMessages(): static { $this->totalMessages++; return $this; }

    public function getInsights(): array { return $this->insightsJson ? json_decode($this->insightsJson, true) : []; }
    public function setInsights(array $v): static { $this->insightsJson = json_encode($v, JSON_UNESCAPED_UNICODE); return $this; }
    public function mergeInsight(string $key, mixed $value): static {
        $data = $this->getInsights(); $data[$key] = $value;
        return $this->setInsights($data);
    }

    public function getGoals(): array { return $this->goalsJson ? json_decode($this->goalsJson, true) : []; }
    public function setGoals(array $v): static { $this->goalsJson = json_encode($v, JSON_UNESCAPED_UNICODE); return $this; }

    public function getHabits(): array { return $this->habitsJson ? json_decode($this->habitsJson, true) : []; }
    public function setHabits(array $v): static { $this->habitsJson = json_encode($v, JSON_UNESCAPED_UNICODE); return $this; }

    public function getBlockers(): array { return $this->blockersJson ? json_decode($this->blockersJson, true) : []; }
    public function setBlockers(array $v): static { $this->blockersJson = json_encode($v, JSON_UNESCAPED_UNICODE); return $this; }

    public function getStrengths(): array { return $this->strengthsJson ? json_decode($this->strengthsJson, true) : []; }
    public function setStrengths(array $v): static { $this->strengthsJson = json_encode($v, JSON_UNESCAPED_UNICODE); return $this; }

    public function getLastActivityAt(): ?\DateTimeImmutable { return $this->lastActivityAt; }
    public function touchActivity(): static { $this->lastActivityAt = new \DateTimeImmutable(); return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getMessages(): Collection { return $this->messages; }

    public function getPhaseLabel(): string {
        return match($this->phase) {
            self::PHASE_ONBOARDING => 'Découverte',
            self::PHASE_DAILY      => 'Check-in quotidien',
            self::PHASE_MOTIVATION => 'Motivation',
            self::PHASE_CRISIS     => 'Soutien',
            self::PHASE_COACHING   => 'Coaching',
            self::PHASE_REFLECTION => 'Réflexion',
            default => $this->phase,
        };
    }

    public function shouldAdvancePhase(): bool {
        return $this->phase === self::PHASE_ONBOARDING && $this->totalMessages >= 6;
    }
}