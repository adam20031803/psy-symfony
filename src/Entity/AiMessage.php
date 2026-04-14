<?php

namespace App\Entity;

use App\Repository\AiMessageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AiMessageRepository::class)]
#[ORM\Table(name: 'ai_message')]
class AiMessage
{
    const ROLE_USER      = 'user';
    const ROLE_ASSISTANT = 'assistant';

    const TYPE_TEXT        = 'text';
    const TYPE_QUESTION    = 'question';
    const TYPE_TIP         = 'tip';
    const TYPE_INSIGHT     = 'insight';
    const TYPE_CHALLENGE   = 'challenge';
    const TYPE_QUICK_REPLY = 'quick_reply';
    const TYPE_MOOD_CHECK  = 'mood_check';
    const TYPE_CELEBRATION = 'celebration';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    private ?AiConversation $conversation = null;

    #[ORM\Column(length: 10)]
    private string $role = self::ROLE_USER;

    #[ORM\Column(type: 'text')]
    private string $content = '';

    #[ORM\Column(length: 30)]
    private string $messageType = self::TYPE_TEXT;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $quickReplies = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $emotionDetected = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $dataCollected = null;

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getConversation(): ?AiConversation { return $this->conversation; }
    public function setConversation(?AiConversation $c): static { $this->conversation = $c; return $this; }

    public function getRole(): string { return $this->role; }
    public function setRole(string $r): static { $this->role = $r; return $this; }
    public function isFromUser(): bool { return $this->role === self::ROLE_USER; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $c): static { $this->content = $c; return $this; }

    public function getMessageType(): string { return $this->messageType; }
    public function setMessageType(string $t): static { $this->messageType = $t; return $this; }

    public function getQuickReplies(): array {
        return $this->quickReplies ? json_decode($this->quickReplies, true) : [];
    }
    public function setQuickReplies(array $r): static {
        $this->quickReplies = json_encode($r, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function getEmotionDetected(): ?string { return $this->emotionDetected; }
    public function setEmotionDetected(?string $e): static { $this->emotionDetected = $e; return $this; }

    public function getDataCollected(): ?array {
        return $this->dataCollected ? json_decode($this->dataCollected, true) : null;
    }
    public function setDataCollected(?array $d): static {
        $this->dataCollected = $d ? json_encode($d, JSON_UNESCAPED_UNICODE) : null;
        return $this;
    }

    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $r): static { $this->isRead = $r; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}