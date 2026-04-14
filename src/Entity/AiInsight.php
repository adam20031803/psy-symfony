<?php

namespace App\Entity;

use App\Repository\AiInsightRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AiInsightRepository::class)]
#[ORM\Table(name: 'ai_insight')]
class AiInsight
{
    const TYPE_PATTERN        = 'pattern';
    const TYPE_PREDICTION     = 'prediction';
    const TYPE_RECOMMENDATION = 'recommendation';
    const TYPE_WARNING        = 'warning';
    const TYPE_CELEBRATION    = 'celebration';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 30)]
    private string $type = self::TYPE_RECOMMENDATION;

    #[ORM\Column(length: 255)]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    private string $content = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $actionSuggested = null;

    #[ORM\Column(nullable: true)]
    private ?float $confidence = null;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $u): static { $this->user = $u; return $this; }
    public function getType(): string { return $this->type; }
    public function setType(string $t): static { $this->type = $t; return $this; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $t): static { $this->title = $t; return $this; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $c): static { $this->content = $c; return $this; }
    public function getActionSuggested(): ?string { return $this->actionSuggested; }
    public function setActionSuggested(?string $v): static { $this->actionSuggested = $v; return $this; }
    public function getConfidence(): ?float { return $this->confidence; }
    public function setConfidence(?float $v): static { $this->confidence = $v; return $this; }
    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $r): static { $this->isRead = $r; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getTypeEmoji(): string {
        return match($this->type) {
            self::TYPE_PATTERN        => '🔍',
            self::TYPE_PREDICTION     => '🔮',
            self::TYPE_RECOMMENDATION => '💡',
            self::TYPE_WARNING        => '⚠️',
            self::TYPE_CELEBRATION    => '🎉',
            default => '📊',
        };
    }
}