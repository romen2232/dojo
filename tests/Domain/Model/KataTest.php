<?php

namespace Tests\Domain\Model;

use Dojo\Domain\Model\Kata;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class KataTest extends TestCase
{
    private function createValidKata(): Kata
    {
        return new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata',
            'Description',
            '6 kyu',
            'php',
            'function solution() {}',
            'test code',
            ['algorithms'],
            'algorithms',
            'author',
            ['php', 'javascript']
        );
    }

    public function testKataGetters(): void
    {
        $kata = $this->createValidKata();

        $this->assertEquals('test-id', $kata->getId());
        $this->assertEquals('https://codewars.com/kata/test-id', $kata->getUrl());
        $this->assertEquals('Test Kata', $kata->getName());
        $this->assertEquals('Description', $kata->getDescription());
        $this->assertEquals('6 kyu', $kata->getDifficulty());
        $this->assertEquals('php', $kata->getLanguage());
        $this->assertEquals('function solution() {}', $kata->getSolutionPlaceholder());
        $this->assertEquals('test code', $kata->getTests());
        $this->assertEquals(['algorithms'], $kata->getTags());
        $this->assertEquals('algorithms', $kata->getCategory());
        $this->assertEquals('author', $kata->getAuthor());
        $this->assertEquals(['php', 'javascript'], $kata->getLanguagesAvailable());
    }

    public function testNullableFields(): void
    {
        $kata = new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata',
            'Description',
            '6 kyu',
            'php',
            'function solution() {}',
            'test code',
            ['algorithms'],
            null,  // category can be null
            null,  // author can be null
            ['php']
        );

        $this->assertNull($kata->getCategory());
        $this->assertNull($kata->getAuthor());
    }

    public function testEmptyTags(): void
    {
        $kata = new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata',
            'Description',
            '6 kyu',
            'php',
            'function solution() {}',
            'test code',
            [],  // empty tags array
            null,
            null,
            ['php']
        );

        $this->assertIsArray($kata->getTags());
        $this->assertEmpty($kata->getTags());
    }

    public function testMultipleLanguagesAvailable(): void
    {
        $languages = ['php', 'javascript', 'python', 'ruby', 'java'];
        $kata = new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata',
            'Description',
            '6 kyu',
            'php',
            'function solution() {}',
            'test code',
            ['algorithms'],
            null,
            null,
            $languages
        );

        $this->assertEquals($languages, $kata->getLanguagesAvailable());
        $this->assertCount(5, $kata->getLanguagesAvailable());
    }

    public function testLongDescription(): void
    {
        $longDescription = str_repeat('This is a very long description. ', 100);
        $kata = new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata',
            $longDescription,
            '6 kyu',
            'php',
            'function solution() {}',
            'test code',
            ['algorithms'],
            null,
            null,
            ['php']
        );

        $this->assertEquals($longDescription, $kata->getDescription());
    }

    public function testDifferentKyuLevels(): void
    {
        $kyuLevels = ['1 kyu', '2 kyu', '3 kyu', '4 kyu', '5 kyu', '6 kyu', '7 kyu', '8 kyu'];

        foreach ($kyuLevels as $kyuLevel) {
            $kata = new Kata(
                'test-id',
                'https://codewars.com/kata/test-id',
                'Test Kata',
                'Description',
                $kyuLevel,
                'php',
                'function solution() {}',
                'test code',
                ['algorithms'],
                null,
                null,
                ['php']
            );

            $this->assertEquals($kyuLevel, $kata->getDifficulty());
        }
    }

    public function testDifferentDanLevels(): void
    {
        $danLevels = ['1 dan', '2 dan', '3 dan', '4 dan', '5 dan', '6 dan', '7 dan', '8 dan'];

        foreach ($danLevels as $danLevel) {
            $kata = new Kata(
                'test-id',
                'https://codewars.com/kata/test-id',
                'Test Kata',
                'Description',
                $danLevel,
                'php',
                'function solution() {}',
                'test code',
                ['algorithms'],
                null,
                null,
                ['php']
            );

            $this->assertEquals($danLevel, $kata->getDifficulty());
        }
    }

    public function testMultipleTags(): void
    {
        $tags = ['algorithms', 'strings', 'arrays', 'mathematics', 'puzzles'];
        $kata = new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata',
            'Description',
            '6 kyu',
            'php',
            'function solution() {}',
            'test code',
            $tags,
            null,
            null,
            ['php']
        );

        $this->assertEquals($tags, $kata->getTags());
        $this->assertCount(5, $kata->getTags());
    }

    public function testComplexSolutionPlaceholder(): void
    {
        $complexPlaceholder = <<<'PHP'
<?php
class Solution {
    public function someMethod($param1, $param2) {
        // Your solution here
    }

    private function helperMethod() {
        // Helper method
    }
}
PHP;

        $kata = new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata',
            'Description',
            '6 kyu',
            'php',
            $complexPlaceholder,
            'test code',
            ['algorithms'],
            null,
            null,
            ['php']
        );

        $this->assertEquals($complexPlaceholder, $kata->getSolutionPlaceholder());
    }

    /**
     * @dataProvider invalidKataDataProvider
     */
    public function testValidationFailures(string $field, $value, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $data = [
            'id' => 'test-id',
            'url' => 'https://codewars.com/kata/test-id',
            'name' => 'Test Kata',
            'description' => 'Description',
            'difficulty' => '6 kyu',
            'language' => 'php',
            'solutionPlaceholder' => 'function solution() {}',
            'tests' => 'test code',
            'tags' => ['algorithms'],
            'category' => 'algorithms',
            'author' => 'author',
            'languagesAvailable' => ['php'],
        ];

        $data[$field] = $value;

        new Kata(
            $data['id'],
            $data['url'],
            $data['name'],
            $data['description'],
            $data['difficulty'],
            $data['language'],
            $data['solutionPlaceholder'],
            $data['tests'],
            $data['tags'],
            $data['category'],
            $data['author'],
            $data['languagesAvailable']
        );
    }

    public function invalidKataDataProvider(): array
    {
        return [
            'empty id' => ['id', '', 'ID cannot be empty'],
            'empty url' => ['url', '', 'URL cannot be empty'],
            'invalid url' => ['url', 'not-a-url', 'Invalid URL format'],
            'empty name' => ['name', '', 'Name cannot be empty'],
            'empty description' => ['description', '', 'Description cannot be empty'],
            'invalid kyu level' => ['difficulty', '9 kyu', 'Invalid difficulty level. Must be between 1-8 kyu or 1-8 dan'],
            'invalid dan level' => ['difficulty', '9 dan', 'Invalid difficulty level. Must be between 1-8 kyu or 1-8 dan'],
            'malformed difficulty' => ['difficulty', 'not-kyu', 'Invalid difficulty format. Must be "X kyu" or "X dan"'],
            'empty language' => ['language', '', 'Language cannot be empty'],
            'empty solution placeholder' => ['solutionPlaceholder', '', 'Solution placeholder cannot be empty'],
            'empty tests' => ['tests', '', 'Tests cannot be empty'],
            'empty languages array' => ['languagesAvailable', [], 'At least one language must be available'],
            'language not in available languages' => ['language', 'ruby', 'Selected language must be in available languages'],
        ];
    }

    public function testUrlMustBeCodewarsUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('URL must be a valid Codewars kata URL');

        new Kata(
            'test-id',
            'https://other-site.com/kata/test-id',
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
    }

    public function testLanguagesMustBeUnique(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Languages available must be unique');

        new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata',
            'Description',
            '6 kyu',
            'php',
            'function solution() {}',
            'test code',
            ['algorithms'],
            'algorithms',
            'author',
            ['php', 'javascript', 'php'] // Duplicate php
        );
    }

    public function testTagsMustBeUnique(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tags must be unique');

        new Kata(
            'test-id',
            'https://codewars.com/kata/test-id',
            'Test Kata',
            'Description',
            '6 kyu',
            'php',
            'function solution() {}',
            'test code',
            ['algorithms', 'strings', 'algorithms'], // Duplicate algorithms
            'algorithms',
            'author',
            ['php']
        );
    }
}
