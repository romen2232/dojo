<?php

namespace Tests\Domain\Port;

use Dojo\Domain\Model\Kata;
use Dojo\Domain\Port\KataRepositoryInterface;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @method \PHPUnit\Framework\MockObject\MockObject createMock(string $originalClassName)
 */
class KataRepositoryInterfaceTest extends TestCase
{
    private KataRepositoryInterface&MockObject $repository;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(KataRepositoryInterface::class);
    }

    public function testGetKataByUrlReturnsKata(): void
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

        // Configure mock to return the expected Kata
        $this->repository
            ->expects($this->once())
            ->method('getKataByUrl')
            ->with($url)
            ->willReturn($expectedKata);

        // Act
        $kata = $this->repository->getKataByUrl($url);

        // Assert
        $this->assertInstanceOf(Kata::class, $kata);
        $this->assertEquals($url, $kata->getUrl());
        $this->assertEquals('test-id', $kata->getId());
        $this->assertEquals('Test Kata', $kata->getName());
        $this->assertEquals('Description', $kata->getDescription());
        $this->assertEquals('6 kyu', $kata->getDifficulty());
        $this->assertEquals('php', $kata->getLanguage());
        $this->assertEquals(['algorithms'], $kata->getTags());
        $this->assertEquals('algorithms', $kata->getCategory());
        $this->assertEquals('author', $kata->getAuthor());
        $this->assertEquals(['php'], $kata->getLanguagesAvailable());
    }

    public function testGetKataByUrlWithInvalidUrl(): void
    {
        // Arrange
        $invalidUrl = 'invalid-url';

        // Configure mock to throw exception for invalid URL
        $this->repository
            ->expects($this->once())
            ->method('getKataByUrl')
            ->with($invalidUrl)
            ->willThrowException(new InvalidArgumentException('Invalid URL format'));

        // Assert & Act
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL format');

        $this->repository->getKataByUrl($invalidUrl);
    }

    public function testGetKataByUrlWithNonExistentKata(): void
    {
        // Arrange
        $nonExistentUrl = 'https://codewars.com/kata/non-existent';

        // Configure mock to throw exception for non-existent kata
        $this->repository
            ->expects($this->once())
            ->method('getKataByUrl')
            ->with($nonExistentUrl)
            ->willThrowException(new RuntimeException('Kata not found'));

        // Assert & Act
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kata not found');

        $this->repository->getKataByUrl($nonExistentUrl);
    }

    public function testGetKataByUrlWithEmptyUrl(): void
    {
        // Arrange
        $emptyUrl = '';

        // Configure mock to throw exception for empty URL
        $this->repository
            ->expects($this->once())
            ->method('getKataByUrl')
            ->with($emptyUrl)
            ->willThrowException(new InvalidArgumentException('URL cannot be empty'));

        // Assert & Act
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URL cannot be empty');

        $this->repository->getKataByUrl($emptyUrl);
    }

    public function testGetKataByUrlWithNonCodewarsUrl(): void
    {
        // Arrange
        $nonCodewarsUrl = 'https://example.com/kata/test-id';

        // Configure mock to throw exception for non-Codewars URL
        $this->repository
            ->expects($this->once())
            ->method('getKataByUrl')
            ->with($nonCodewarsUrl)
            ->willThrowException(new InvalidArgumentException('URL must be a valid Codewars kata URL'));

        // Assert & Act
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URL must be a valid Codewars kata URL');

        $this->repository->getKataByUrl($nonCodewarsUrl);
    }
}
