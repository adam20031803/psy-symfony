<?php

declare(strict_types=1);

namespace App\Tests\Unit\ModuleFitness;

use App\Service\FitnessProgramGeneratorService;
use App\Service\GeminiService;
use App\Service\GroqService;
use App\Service\OllamaFallbackService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class FitnessProgramGeneratorServiceTest extends TestCase
{
    private function decodeAiJson(FitnessProgramGeneratorService $service, string $response): ?array
    {
        $m = new ReflectionMethod(FitnessProgramGeneratorService::class, 'decodeAiJson');
        $m->setAccessible(true);

        return $m->invoke($service, $response);
    }

    private function createService(): FitnessProgramGeneratorService
    {
        return new FitnessProgramGeneratorService(
            $this->createStub(GeminiService::class),
            $this->createStub(GroqService::class),
            $this->createStub(OllamaFallbackService::class),
            $this->createStub(EntityManagerInterface::class),
        );
    }

    public function testDecodeAiJsonStripsMarkdownFenceAndParsesExercises(): void
    {
        $service = $this->createService();
        $json = <<<'JSON'
```json
{"title":"P","goal":"G","durationWeeks":4,"level":"Débutant","exercises":[{"name":"Squat"}]}
```
JSON;

        $decoded = $this->decodeAiJson($service, $json);

        $this->assertIsArray($decoded);
        $this->assertSame('P', $decoded['title']);
        $this->assertArrayHasKey('exercises', $decoded);
        $this->assertSame('Squat', $decoded['exercises'][0]['name']);
    }

    public function testDecodeAiJsonExtractsJsonObjectEmbeddedInText(): void
    {
        $service = $this->createService();
        $blob = 'Voici le programme: {"title":"X","goal":"Y","exercises":[{"name":"Plank","category":"Cardio"}]} merci.';

        $decoded = $this->decodeAiJson($service, $blob);

        $this->assertIsArray($decoded);
        $this->assertSame('X', $decoded['title']);
        $this->assertSame('Plank', $decoded['exercises'][0]['name']);
    }
}
