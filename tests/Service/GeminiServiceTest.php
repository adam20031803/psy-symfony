<?php

namespace App\Tests\Service;

use App\Service\GeminiService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class GeminiServiceTest extends TestCase
{
    public function testGenerateResponseReturnsMissingKeyMessageWhenKeyIsEmpty(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $service = new GeminiService($httpClient, '');
        
        $response = $service->generateResponse('Hello');
        
        $this->assertEquals('API Key is missing.', $response);
    }

    public function testGenerateResponseReturnsTextOnSuccess(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $responseMock = $this->createMock(ResponseInterface::class);
        
        $responseMock->method('toArray')->willReturn([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            ['text' => 'This is a mocked AI response.']
                        ]
                    ]
                ]
            ]
        ]);
        
        $httpClient->method('request')->willReturn($responseMock);
        
        $service = new GeminiService($httpClient, 'fake-api-key');
        
        $response = $service->generateResponse('Tell me a joke');
        
        $this->assertEquals('This is a mocked AI response.', $response);
    }

    public function testGenerateResponseHandlesExceptions(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willThrowException(new \Exception('Network error'));
        
        $service = new GeminiService($httpClient, 'fake-api-key');
        
        $response = $service->generateResponse('Hello');
        
        $this->assertStringContainsString('Erreur lors de la communication avec l\'IA: Network error', $response);
    }
}
