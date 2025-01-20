<?php

namespace Tests\Dojo\Infrastructure\Adapters\Input\Console;

use Dojo\Application\Service\KataScraperService;
use Dojo\Domain\Model\Kata;
use Dojo\Infrastructure\Adapters\Input\Console\ScrapeKataCommand;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ScrapeKataCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private KataScraperService&MockObject $kataScraperService;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . '/kata_test_' . uniqid();
        mkdir($this->tempDir);

        $this->kataScraperService = $this->createMock(KataScraperService::class);

        $application = new Application();
        $command = new ScrapeKataCommand($this->kataScraperService);
        $application->add($command);

        $this->commandTester = new CommandTester($command);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testSuccessfulKataScraping(): void
    {
        $kata = new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata Name',
            'A test kata description',
            '6 kyu',
            'python',
            'def solution():',
            'def test_solution():',
            ['arrays', 'algorithms'],
            'Practice',
            'Test Author',
            ['python', 'javascript']
        );

        $this->kataScraperService
            ->expects($this->once())
            ->method('scrapeKata')
            ->with('https://codewars.com/kata/test-id')
            ->willReturn($kata);

        $this->commandTester->execute([
            'url' => 'https://codewars.com/kata/test-id',
            '--katas-dir' => $this->tempDir
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        // Verify directory structure
        $expectedPath = $this->tempDir . '/codewars/python/6_kyu/test_kata_name';
        $this->assertDirectoryExists($expectedPath);

        // Verify JSON file exists and content
        $jsonPath = $expectedPath . '/test_kata_name.json';
        $this->assertFileExists($jsonPath);

        $jsonContent = json_decode(file_get_contents($jsonPath), true);
        $this->assertEquals('test-id', $jsonContent['id']);
        $this->assertEquals('Test Kata Name', $jsonContent['name']);
        $this->assertEquals('6 kyu', $jsonContent['difficulty']);
        $this->assertEquals('python', $jsonContent['language']);
        $this->assertEquals(['arrays', 'algorithms'], $jsonContent['tags']);

        // Verify output contains important information
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Test Kata Name', $output);
        $this->assertStringContainsString('6 kyu', $output);
        $this->assertStringContainsString('Test Author', $output);
        $this->assertStringContainsString('arrays, algorithms', $output);
    }

    public function testScrapingWithServiceException(): void
    {
        $this->kataScraperService
            ->expects($this->once())
            ->method('scrapeKata')
            ->willThrowException(new \Exception('Failed to scrape kata'));

        $this->commandTester->execute([
            'url' => 'https://codewars.com/kata/invalid-id',
            '--katas-dir' => $this->tempDir
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Failed to scrape kata', $this->commandTester->getDisplay());
    }

    public function testCustomKatasDirectory(): void
    {
        $kata = new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata',
            'Description',
            '7 kyu',
            'javascript',
            'function solution() {}',
            'test("should work", () => {});',
            [],
            'Practice',
            'Author',
            ['javascript']
        );

        $this->kataScraperService
            ->expects($this->once())
            ->method('scrapeKata')
            ->willReturn($kata);

        $customDir = $this->tempDir . '/custom_katas';
        $this->commandTester->execute([
            'url' => 'https://codewars.com/kata/test-id',
            '--katas-dir' => $customDir
        ]);

        $expectedPath = $customDir . '/codewars/javascript/7_kyu/test_kata';
        $this->assertDirectoryExists($expectedPath);
        $this->assertFileExists($expectedPath . '/test_kata.json');
    }

    public function testSanitizeFolderNameWithSpecialCharacters(): void
    {
        $kata = new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata @#$% Special!!! Chars',
            'Description',
            '8 kyu',
            'php',
            '<?php function solution() {}',
            '<?php test("test");',
            [],
            'Practice',
            'Author',
            ['php']
        );

        $this->kataScraperService
            ->expects($this->once())
            ->method('scrapeKata')
            ->willReturn($kata);

        $this->commandTester->execute([
            'url' => 'https://codewars.com/kata/test-id',
            '--katas-dir' => $this->tempDir
        ]);

        $expectedPath = $this->tempDir . '/codewars/php/8_kyu/test_kata_special_chars';
        $this->assertDirectoryExists($expectedPath);

        // The sanitized name should only contain alphanumeric characters, spaces, and hyphens
        $jsonPath = $expectedPath . '/test_kata_special_chars.json';
        $this->assertFileExists($jsonPath);

        // Verify the content to ensure the original name is preserved in the JSON
        $jsonContent = json_decode(file_get_contents($jsonPath), true);
        $this->assertEquals('Test Kata @#$% Special!!! Chars', $jsonContent['name']);
    }

    public function testDefaultKatasDirectory(): void
    {
        $kata = new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata',
            'Description',
            '5 kyu',
            'ruby',
            'def solution; end',
            'describe "test" do; end',
            [],
            'Practice',
            'Author',
            ['ruby']
        );

        $this->kataScraperService
            ->expects($this->once())
            ->method('scrapeKata')
            ->willReturn($kata);

        $this->commandTester->execute([
            'url' => 'https://codewars.com/kata/test-id'
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('katas/codewars/ruby/5_kyu/test_kata', $this->commandTester->getDisplay());
    }
}
