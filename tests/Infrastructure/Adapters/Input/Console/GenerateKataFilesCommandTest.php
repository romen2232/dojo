<?php

namespace Tests\Dojo\Infrastructure\Adapters\Input\Console;

use Dojo\Infrastructure\Adapters\Input\Console\GenerateKataFilesCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class GenerateKataFilesCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $application = new Application();
        $command = new GenerateKataFilesCommand();
        $application->add($command);

        $this->commandTester = new CommandTester($command);
        $this->tempDir = sys_get_temp_dir() . '/kata_test_' . uniqid();
        mkdir($this->tempDir);
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

    public function testSuccessfulFileGeneration(): void
    {
        $jsonPath = $this->tempDir . '/kata.json';
        $kataData = [
            'name' => 'Test Kata',
            'difficulty' => '4 kyu',
            'description' => 'Test description',
            'language' => 'python',
            'solutionPlaceholder' => 'def solution():',
            'tests' => 'def test_solution():',
        ];
        file_put_contents($jsonPath, json_encode($kataData));

        $this->commandTester->execute([
            'kata-json' => $jsonPath,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());

        // Verify directory structure
        $this->assertDirectoryExists($this->tempDir . '/solution');
        $this->assertDirectoryExists($this->tempDir . '/tests');

        // Verify files were created with correct content
        $this->assertFileExists($this->tempDir . '/solution/solution.py');
        $this->assertFileExists($this->tempDir . '/tests/test.py');
        $this->assertFileExists($this->tempDir . '/README.md');

        $this->assertEquals('def solution():', file_get_contents($this->tempDir . '/solution/solution.py'));
        $this->assertEquals('def test_solution():', file_get_contents($this->tempDir . '/tests/test.py'));

        $expectedReadme = "# Test Kata\n\nDifficulty: 4 kyu\n\nTest description";
        $this->assertEquals($expectedReadme, file_get_contents($this->tempDir . '/README.md'));
    }

    public function testInvalidJsonFilePath(): void
    {
        $this->commandTester->execute([
            'kata-json' => $this->tempDir . '/nonexistent.json',
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('JSON file not found', $this->commandTester->getDisplay());
    }

    public function testInvalidJsonContent(): void
    {
        $jsonPath = $this->tempDir . '/invalid.json';
        file_put_contents($jsonPath, 'invalid json content');

        $this->commandTester->execute([
            'kata-json' => $jsonPath,
        ]);

        $this->assertEquals(1, $this->commandTester->getStatusCode());
        $this->assertStringContainsString('Invalid JSON file', $this->commandTester->getDisplay());
    }

    /**
     * @dataProvider languageExtensionProvider
     */
    public function testDifferentLanguageExtensions(string $language, string $expectedExtension): void
    {
        $jsonPath = $this->tempDir . '/kata.json';
        $kataData = [
            'name' => 'Test Kata',
            'difficulty' => '4 kyu',
            'description' => 'Test description',
            'language' => $language,
            'solutionPlaceholder' => 'code here',
            'tests' => 'test here',
        ];
        file_put_contents($jsonPath, json_encode($kataData));

        $this->commandTester->execute([
            'kata-json' => $jsonPath,
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
        $this->assertFileExists($this->tempDir . '/solution/solution.' . $expectedExtension);
        $this->assertFileExists($this->tempDir . '/tests/test.' . $expectedExtension);
    }

    public function languageExtensionProvider(): array
    {
        return [
            ['python', 'py'],
            ['javascript', 'js'],
            ['typescript', 'ts'],
            ['java', 'java'],
            ['c#', 'cs'],
            ['c++', 'cpp'],
            ['php', 'php'],
            ['ruby', 'rb'],
            ['rust', 'rs'],
            ['go', 'go'],
            ['kotlin', 'kt'],
            ['scala', 'scala'],
            ['swift', 'swift'],
            ['unknown', 'txt'],
        ];
    }
}
