<?php

declare(strict_types=1);

namespace App\Tests\Unit\ModuleHabitudes;

use App\Entity\Habitude;
use PHPUnit\Framework\TestCase;

final class HabitudeEntityTest extends TestCase
{
    public function testConstructorSetsDefaultStreakAndCompletionCounters(): void
    {
        $h = new Habitude();

        $this->assertSame(0, $h->getCurrentStreak());
        $this->assertSame(0, $h->getLongestStreak());
        $this->assertSame(0, $h->getTotalCompletions());
        $this->assertTrue($h->isActive());
    }

    public function testSetTitleIsFluent(): void
    {
        $h = new Habitude();
        $ret = $h->setTitle('Méditation matinale');

        $this->assertSame($h, $ret);
        $this->assertSame('Méditation matinale', $h->getTitle());
    }
}
