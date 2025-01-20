<?php

namespace Tests\Dojo\Application\Service;

use Dojo\Application\Service\KataScraperService;
use Dojo\Domain\Model\Kata;
use Dojo\Domain\Port\KataRepositoryInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use InvalidArgumentException;
use RuntimeException;

class KataScraperServiceTest extends TestCase
{
    private KataScraperService $service;
    private KataRepositoryInterface&MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(KataRepositoryInterface::class);
        $this->service = new KataScraperService($this->repository);
    }

    public function testScrapeKataSuccessfully(): void
    {
        // Arrange
        $url = 'https://codewars.com/kata/test-id';
        $expectedKata = new Kata(
            'test-id',
            $url,
            'Test Kata',
            'Description',
            '6 kyu',
            'php',
            'function solution() {}',
            'test code',
            ['algorithms'],
            'algorithms',
            'author',
            ['php']
        );

        $this->repository
            ->expects($this->once())
            ->method('getKataByUrl')
            ->with($url)
            ->willReturn($expectedKata);

        // Act
        $kata = $this->service->scrapeKata($url);

        // Assert
        $this->assertSame($expectedKata, $kata);
    }

    public function testScrapeKataWithInvalidUrl(): void
    {
        // Arrange
        $url = 'invalid-url';
        $this->repository
            ->expects($this->once())
            ->method('getKataByUrl')
            ->with($url)
            ->willThrowException(new InvalidArgumentException('Invalid URL format'));

        // Assert & Act
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format');
        $this->service->scrapeKata($url);
    }

    public function testScrapeKataWithRuntimeError(): void
    {
        // Arrange
        $url = 'https://codewars.com/kata/test-id';
        $this->repository
            ->expects($this->once())
            ->method('getKataByUrl')
            ->with($url)
            ->willThrowException(new RuntimeException('Failed to scrape kata'));

        // Assert & Act
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to scrape kata');
        $this->service->scrapeKata($url);
    }
}
