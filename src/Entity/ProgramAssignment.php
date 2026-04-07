<?php

namespace App\Entity;

use App\Repository\ProgramAssignmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgramAssignmentRepository::class)]
#[ORM\Table(name: 'program_assignment')]
#[ORM\UniqueConstraint(name: 'uniq_program_user', columns: ['program_id', 'user_id'])]
class ProgramAssignment
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Program::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Program $program = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 20, options: ['default' => 'pending'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $sentAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $respondedAt = null;

    public function __construct()
    {
        $this->sentAt = new \DateTime();
    }

    public function getId(): ?int            { return $this->id; }

    public function getProgram(): ?Program   { return $this->program; }
    public function setProgram(?Program $p): static { $this->program = $p; return $this; }

    public function getUser(): ?User         { return $this->user; }
    public function setUser(?User $u): static { $this->user = $u; return $this; }

    public function getStatus(): string      { return $this->status; }
    public function setStatus(string $s): static
    {
        $this->status = $s;
        if ($s !== self::STATUS_PENDING) {
            $this->respondedAt = new \DateTime();
        }
        return $this;
    }

    public function isPending(): bool    { return $this->status === self::STATUS_PENDING; }
    public function isAccepted(): bool   { return $this->status === self::STATUS_ACCEPTED; }
    public function isDeclined(): bool   { return $this->status === self::STATUS_DECLINED; }

    public function getSentAt(): \DateTimeInterface  { return $this->sentAt; }
    public function getRespondedAt(): ?\DateTimeInterface { return $this->respondedAt; }
}
