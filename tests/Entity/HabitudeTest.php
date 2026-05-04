<?php

namespace App\Tests\Entity;

use App\Entity\Habitude;
use App\Entity\HabitCompletions;
use PHPUnit\Framework\TestCase;

class HabitudeTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $habitude = new Habitude();
        
        $this->assertTrue($habitude->isActive());
        $this->assertEquals(0, $habitude->getCurrentStreak());
        $this->assertEquals(0, $habitude->getLongestStreak());
        $this->assertEquals(0, $habitude->getTotalCompletions());
        $this->assertCount(0, $habitude->getCompletions());
    }

    public function testSettersAndGetters(): void
    {
        $habitude = new Habitude();
        $startDate = new \DateTime('2026-05-01');
        
        $habitude->setTitle('Boire de l\'eau')
                 ->setDescription('2 litres par jour')
                 ->setCategory('Santé')
                 ->setFrequencyType(7)
                 ->setStartDate($startDate);

        $this->assertEquals('Boire de l\'eau', $habitude->getTitle());
        $this->assertEquals('2 litres par jour', $habitude->getDescription());
        $this->assertEquals('Santé', $habitude->getCategory());
        $this->assertEquals(7, $habitude->getFrequencyType());
        $this->assertEquals($startDate, $habitude->getStartDate());
    }

    public function testAddAndRemoveCompletion(): void
    {
        $habitude = new Habitude();
        $completion = new HabitCompletions();
        
        $habitude->addCompletion($completion);
        
        $this->assertCount(1, $habitude->getCompletions());
        $this->assertSame($habitude, $completion->getHabitude());

        $habitude->removeCompletion($completion);
        
        $this->assertCount(0, $habitude->getCompletions());
        $this->assertNull($completion->getHabitude());
    }
}
