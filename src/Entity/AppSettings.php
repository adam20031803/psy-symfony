<?php

namespace App\Entity;

use App\Repository\AppSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppSettingsRepository::class)]
#[ORM\Table(name: 'app_settings')]
class AppSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    private ?string $cle = null; // clé de paramètre (ex: site_name, maintenance_mode)

    #[ORM\Column(type: 'text')]
    private ?string $valeur = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    public function getId(): ?int { return $this->id; }

    public function getCle(): ?string { return $this->cle; }
    public function setCle(string $cle): static { $this->cle = $cle; return $this; }

    public function getValeur(): ?string { return $this->valeur; }
    public function setValeur(string $valeur): static { $this->valeur = $valeur; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }
}
