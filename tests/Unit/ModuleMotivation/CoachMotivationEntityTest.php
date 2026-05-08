<?php

declare(strict_types=1);

namespace App\Tests\Unit\ModuleMotivation;

use App\Entity\CoachMotivation;
use PHPUnit\Framework\TestCase;

final class CoachMotivationEntityTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $before = new \DateTimeImmutable();
        $m = new CoachMotivation();
        $after = new \DateTimeImmutable();

        $this->assertNotNull($m->getCreatedAt());
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $m->getCreatedAt()->getTimestamp());
        $this->assertLessThanOrEqual($after->getTimestamp(), $m->getCreatedAt()->getTimestamp());
    }

    public function testSetMessageReturnsFluentInstance(): void
    {
        $m = new CoachMotivation();
        $text = 'Bravo pour ta régularité cette semaine, continue ainsi !';

        $ret = $m->setMessage($text);

        $this->assertSame($m, $ret);
        $this->assertSame($text, $m->getMessage());
    }
}
