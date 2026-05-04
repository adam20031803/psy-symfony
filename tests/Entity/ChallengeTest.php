<?php

namespace App\Tests\Entity;

use App\Entity\Challenge;
use PHPUnit\Framework\TestCase;

class ChallengeTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $challenge = new Challenge();
        
        $this->assertEquals('actif', $challenge->getStatut());
        $this->assertInstanceOf(\DateTimeImmutable::class, $challenge->getCreatedAt());
        $this->assertCount(0, $challenge->getChats());
        $this->assertCount(0, $challenge->getCoaches());
        $this->assertCount(0, $challenge->getRecompenses());
    }

    public function testSettersAndGetters(): void
    {
        $challenge = new Challenge();
        $dateDebut = new \DateTime('2026-05-01');
        $dateFin = new \DateTime('2026-05-30');
        
        $challenge->setTitre('Défi Lecture')
                  ->setDescription('Lire 5 livres ce mois-ci')
                  ->setDateDebut($dateDebut)
                  ->setDateFin($dateFin)
                  ->setStatut('termine');

        $this->assertEquals('Défi Lecture', $challenge->getTitre());
        $this->assertEquals('Lire 5 livres ce mois-ci', $challenge->getDescription());
        $this->assertEquals($dateDebut, $challenge->getDateDebut());
        $this->assertEquals($dateFin, $challenge->getDateFin());
        $this->assertEquals('termine', $challenge->getStatut());
    }
}
