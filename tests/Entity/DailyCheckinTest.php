<?php

namespace App\Tests\Entity;

use App\Entity\DailyCheckin;
use PHPUnit\Framework\TestCase;

class DailyCheckinTest extends TestCase
{
    public function testSetCheckinDateConvertsToImmutable(): void
    {
        $checkin = new DailyCheckin();
        $date = new \DateTime('2026-05-01 12:00:00');
        
        $checkin->setCheckinDate($date);
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $checkin->getCheckinDate());
        $this->assertEquals('2026-05-01 12:00:00', $checkin->getCheckinDate()->format('Y-m-d H:i:s'));
    }

    public function testSettersAndGetters(): void
    {
        $checkin = new DailyCheckin();
        
        $checkin->setMoodRating(8)
                ->setEnergyLevel(7)
                ->setProductivityLevel(9)
                ->setStressLevel(3)
                ->setSleepQuality(8)
                ->setSleepHours(7.5)
                ->setAiInsight('Great job staying productive.');

        $this->assertEquals(8, $checkin->getMoodRating());
        $this->assertEquals(7, $checkin->getEnergyLevel());
        $this->assertEquals(9, $checkin->getProductivityLevel());
        $this->assertEquals(3, $checkin->getStressLevel());
        $this->assertEquals(8, $checkin->getSleepQuality());
        $this->assertEquals(7.5, $checkin->getSleepHours());
        $this->assertEquals('Great job staying productive.', $checkin->getAiInsight());
    }
}
