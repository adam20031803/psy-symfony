<?php

// src/Entity/User.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Entité mappée exactement sur la table existante 'user'.
 */
#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $password = null;

    /**
     * Le rôle est une chaîne ('user', 'coach', 'admin'), et non un tableau JSON.
     */
    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'user'])]
    private string $role = 'user';

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $cv = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $age = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(name: 'is_active', type: 'boolean', options: ['default' => 1])]
    private bool $isActive = true;

    #[ORM\Column(name: 'password_reset_token', type: 'string', length: 100, nullable: true, unique: true)]
    private ?string $passwordResetToken = null;

    #[ORM\Column(name: 'password_reset_requested_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $passwordResetRequestedAt = null;

    /**
     * Face descriptor: 128-float array produced by face-api.js for biometric login.
     * Stored as a JSON array in the database.
     */
    #[ORM\Column(name: 'face_descriptor', type: 'json', nullable: true)]
    private ?array $faceDescriptor = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: MentalEntries::class, orphanRemoval: true)]
    private Collection $mentalEntries;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Moods::class, orphanRemoval: true)]
    private Collection $moods;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: NotificationLog::class, orphanRemoval: true)]
    private Collection $notificationLogs;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Reclamation::class, orphanRemoval: true)]
    private Collection $reclamations;

    // --- Getters & Setters ---

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->mentalEntries = new ArrayCollection();
        $this->moods = new ArrayCollection();
        $this->notificationLogs = new ArrayCollection();
        $this->reclamations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = [$this->role ? 'ROLE_' . strtoupper($this->role) : 'ROLE_USER'];
        // Les coaches héritent des droits d'administration
        if (in_array($this->role, ['admin', 'coach'], true)) {
            $roles[] = 'ROLE_ADMIN';
        }
        // Guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'coach'], true);
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function getCv(): ?string
    {
        return $this->cv;
    }

    public function setCv(?string $cv): static
    {
        $this->cv = $cv;
        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(?int $age): static
    {
        $this->age = $age;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    public function setPasswordResetToken(?string $passwordResetToken): static
    {
        $this->passwordResetToken = $passwordResetToken;
        return $this;
    }

    public function getPasswordResetRequestedAt(): ?\DateTimeImmutable
    {
        return $this->passwordResetRequestedAt;
    }

    public function setPasswordResetRequestedAt(?\DateTimeImmutable $passwordResetRequestedAt): static
    {
        $this->passwordResetRequestedAt = $passwordResetRequestedAt;
        return $this;
    }

    public function getFaceDescriptor(): ?array
    {
        return $this->faceDescriptor;
    }

    public function setFaceDescriptor(?array $faceDescriptor): static
    {
        $this->faceDescriptor = $faceDescriptor;
        return $this;
    }

    /**
     * @return Collection<int, MentalEntries>
     */
    public function getMentalEntries(): Collection
    {
        return $this->mentalEntries;
    }

    /**
     * @return Collection<int, Moods>
     */
    public function getMoods(): Collection
    {
        return $this->moods;
    }

    /**
     * @return Collection<int, NotificationLog>
     */
    public function getNotificationLogs(): Collection
    {
        return $this->notificationLogs;
    }

    /**
     * @return Collection<int, Reclamation>
     */
    public function getReclamations(): Collection
    {
        return $this->reclamations;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // Si vous stockez des données temporaires sensibles, effacez-les ici.
    }
}
