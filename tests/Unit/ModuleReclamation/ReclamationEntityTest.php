<?php

declare(strict_types=1);

namespace App\Tests\Unit\ModuleReclamation;

use App\Entity\Reclamation;
use PHPUnit\Framework\TestCase;

final class ReclamationEntityTest extends TestCase
{
    public function testNewReclamationHasDefaultStatutOuvert(): void
    {
        $r = new Reclamation();

        $this->assertSame('ouvert', $r->getStatut());
        $this->assertInstanceOf(\DateTimeImmutable::class, $r->getCreatedAt());
    }

    public function testSetStatutAndSujetAreFluentAndReadable(): void
    {
        $r = new Reclamation();
        $returned = $r->setSujet('Bug affichage')->setStatut('en_cours');

        $this->assertSame($r, $returned);
        $this->assertSame('Bug affichage', $r->getSujet());
        $this->assertSame('en_cours', $r->getStatut());
    }
}
