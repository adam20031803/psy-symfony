<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    private ?string $prenom = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 20, options: ['default' => 'user'])]
    private ?string $role = 'user'; // user | coach | admin

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $isActive = true;

    // Collections
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Post::class, orphanRemoval: true)]
    private Collection $posts;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Commentaire::class, orphanRemoval: true)]
    private Collection $commentaires;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Reclamation::class, orphanRemoval: true)]
    private Collection $reclamations;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Moods::class, orphanRemoval: true)]
    private Collection $moods;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: DailyCheckin::class, orphanRemoval: true)]
    private Collection $dailyCheckins;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Habitude::class, orphanRemoval: true)]
    private Collection $habitudes;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: MentalEntries::class, orphanRemoval: true)]
    private Collection $mentalEntries;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: NotificationLog::class, orphanRemoval: true)]
    private Collection $notificationLogs;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->posts = new ArrayCollection();
        $this->commentaires = new ArrayCollection();
        $this->reclamations = new ArrayCollection();
        $this->moods = new ArrayCollection();
        $this->dailyCheckins = new ArrayCollection();
        $this->habitudes = new ArrayCollection();
        $this->mentalEntries = new ArrayCollection();
        $this->notificationLogs = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function getRole(): ?string { return $this->role; }
    public function setRole(string $role): static { $this->role = $role; return $this; }

    public function getPhoto(): ?string { return $this->photo; }
    public function setPhoto(?string $photo): static { $this->photo = $photo; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function isActive(): ?bool { return $this->isActive; }
    public function setIsActive(bool $isActive): static { $this->isActive = $isActive; return $this; }

    public function getPosts(): Collection { return $this->posts; }
    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setUser($this);
        }
        return $this;
    }
    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            if ($post->getUser() === $this) { $post->setUser(null); }
        }
        return $this;
    }

    public function getCommentaires(): Collection { return $this->commentaires; }
    public function getReclamations(): Collection { return $this->reclamations; }
    public function getMoods(): Collection { return $this->moods; }
    public function getDailyCheckins(): Collection { return $this->dailyCheckins; }
    public function getHabitudes(): Collection { return $this->habitudes; }
    public function getMentalEntries(): Collection { return $this->mentalEntries; }
    public function getNotificationLogs(): Collection { return $this->notificationLogs; }
}
