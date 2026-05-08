<?php

declare(strict_types=1);

namespace App\Tests\Unit\ModuleSanteMentale;

use App\Entity\DailyCheckin;
use App\Entity\User;
use App\Service\DailyCheckinAnalyticsService;
use App\Service\GroqService;
use App\Service\OllamaFallbackService;
use PHPUnit\Framework\TestCase;

final class DailyCheckinAnalyticsServiceTest extends TestCase
{
    public function testBuildThirtyDayAnalysisWithNoCheckinsReturnsZeroCountAndRecommendation(): void
    {
        $groq = $this->createStub(GroqService::class);
        $ollama = $this->createStub(OllamaFallbackService::class);
        $service = new DailyCheckinAnalyticsService($groq, $ollama);

        $user = new User();
        $user->setEmail('test@example.com');

        $result = $service->buildThirtyDayAnalysis($user, []);

        $this->assertSame(0, $result['count']);
        $this->assertNull($result['averages']);
        $this->assertSame($user, $result['user']);
        $this->assertContains(
            'Ajoutez des check-ins réguliers pour voir des tendances sur 30 jours.',
            $result['recommendations']
        );
    }

    public function testGetAIFeedbackForJournalReturnsEmptyWhenAllTextFieldsEmpty(): void
    {
        $groq = $this->createStub(GroqService::class);
        $ollama = $this->createStub(OllamaFallbackService::class);
        $service = new DailyCheckinAnalyticsService($groq, $ollama);

        $checkin = new DailyCheckin();

        $this->assertSame([], $service->getAIFeedbackForJournal($checkin));
    }
}
