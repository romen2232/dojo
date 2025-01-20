<?php

namespace Tests\Dojo\Infrastructure\Adapters\Output;

use Dojo\Domain\Port\WebClientInterface;
use Dojo\Infrastructure\Adapters\Output\CodewarsKataRepository;
use Facebook\WebDriver\WebDriverWait;
use InvalidArgumentException;
use League\HTMLToMarkdown\HtmlConverter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

class TestCodewarsKataRepository extends CodewarsKataRepository
{
    protected function log(string $message): void
    {
        // Suppress logging in tests
    }

    protected function logElement(string $element, ?string $value): void
    {
        // Suppress logging in tests
    }
}

class CodewarsKataRepositoryTest extends TestCase
{
    private TestCodewarsKataRepository $repository;
    private WebClientInterface&MockObject $client;
    private HtmlConverter&MockObject $htmlConverter;
    private WebDriverWait&MockObject $wait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = $this->createMock(WebClientInterface::class);
        $this->htmlConverter = $this->createMock(HtmlConverter::class);
        $this->wait = $this->createMock(WebDriverWait::class);

        // Configure client to return wait mock
        $this->client->method('wait')->willReturn($this->wait);

        $this->repository = new TestCodewarsKataRepository($this->client, $this->htmlConverter);
    }

    public function testGetKataByUrlSuccessfully(): void
    {
        $crawler = $this->createMock(Crawler::class);
        $this->client->expects($this->atLeastOnce())
            ->method('request')
            ->with('GET', 'https://www.codewars.com/kata/123456/train/php')
            ->willReturn($crawler);

        $this->wait->expects($this->exactly(3))
            ->method('until')
            ->willReturn(true);

        $nameFilter = $this->createMock(Crawler::class);
        $nameFilter->method('text')->willReturn('Test Kata');

        $difficultyFilter = $this->createMock(Crawler::class);
        $difficultyFilter->method('text')->willReturn('6 kyu');

        $descriptionFilter = $this->createMock(Crawler::class);
        $descriptionFilter->method('text')->willReturn('Test description');

        $authorFilter = $this->createMock(Crawler::class);
        $authorFilter->method('text')->willReturn('Test Author');

        $tagsFilter = $this->createMock(Crawler::class);
        $tagsFilter->method('each')->willReturn(['tag1', 'tag2']);

        $solutionFilter = $this->createMock(Crawler::class);
        $solutionFilter->method('each')->willReturn(['function solution() {}']);

        $testsFilter = $this->createMock(Crawler::class);
        $testsFilter->method('each')->willReturn(['test("test", () => {})']);

        $crawler->expects($this->any())
            ->method('filter')
            ->willReturnMap([
                ['h4', $nameFilter],
                ['.small-hex span', $difficultyFilter],
                ['.description-content .markdown', $descriptionFilter],
                ['[data-tippy-content="This kata\'s Sensei"]', $authorFilter],
                ['.keyword-tag', $tagsFilter],
                ['#code .CodeMirror-line', $solutionFilter],
                ['#fixture .CodeMirror-line', $testsFilter],
            ]);

        $this->client->expects($this->any())
            ->method('executeScript')
            ->willReturnMap([
                ['window.scrollTo(0, document.body.scrollHeight);', null],
                ['window.scrollTo(0, 0);', null],
                ['return document.querySelector(".description-content .markdown").innerHTML;', '<div>Test description</div>'],
                ['return Array.from(document.querySelectorAll(".language-selector dd")).map(el => el.textContent.trim()).filter(text => text);', ['PHP', 'JavaScript']],
            ]);

        $this->client->expects($this->any())
            ->method('getCrawler')
            ->willReturn($crawler);

        $this->htmlConverter->expects($this->any())
            ->method('convert')
            ->willReturn('Test description');

        $kata = $this->repository->getKataByUrl('https://www.codewars.com/kata/123456/train/php');

        $this->assertEquals('Test Kata', $kata->getName());
        $this->assertEquals('Test description', $kata->getDescription());
        $this->assertEquals('Test Author', $kata->getAuthor());
        $this->assertEquals(['tag1', 'tag2'], $kata->getTags());
        $this->assertEquals(['PHP', 'JavaScript'], $kata->getLanguagesAvailable());
    }

    public function testGetKataByUrlWithInvalidUrl(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not extract kata ID from URL: invalid-url');

        $this->repository->getKataByUrl('invalid-url');
    }

    public function testGetKataByUrlWithFailedRequest(): void
    {
        $this->client->expects($this->atLeastOnce())
            ->method('request')
            ->willThrowException(new RuntimeException('Failed to load page'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to load page');

        $this->repository->getKataByUrl('https://www.codewars.com/kata/123456/train/php');
    }

    public function testGetKataByUrlWithMissingElements(): void
    {
        $crawler = $this->createMock(Crawler::class);
        $this->client->expects($this->atLeastOnce())
            ->method('request')
            ->with('GET', 'https://www.codewars.com/kata/123456/train/php')
            ->willReturn($crawler);

        $this->wait->expects($this->exactly(15))
            ->method('until')
            ->willReturn(true);

        $emptyFilter = $this->createMock(Crawler::class);
        $emptyFilter->method('text')->willReturn('');
        $emptyFilter->method('each')->willReturn([]);

        $crawler->expects($this->any())
            ->method('filter')
            ->willReturn($emptyFilter);

        $this->client->expects($this->any())
            ->method('executeScript')
            ->willReturnMap([
                ['window.scrollTo(0, document.body.scrollHeight);', null],
                ['window.scrollTo(0, 0);', null],
                ['return document.querySelector(".description-content .markdown").innerHTML;', ''],
                ['return Array.from(document.querySelectorAll(".language-selector dd")).map(el => el.textContent.trim()).filter(text => text);', []],
            ]);

        $this->client->expects($this->any())
            ->method('getCrawler')
            ->willReturn($crawler);

        $this->htmlConverter->expects($this->any())
            ->method('convert')
            ->willReturn('');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Name cannot be empty');

        $this->repository->getKataByUrl('https://www.codewars.com/kata/123456/train/php');
    }

    private function mockCrawlerFilter(
        MockObject $crawler,
        string $selector,
        ?string $returnText,
        bool $returnHtml = false,
        ?Crawler $returnCrawler = null
    ): void {
        $elementCrawler = $returnCrawler ?? $this->createMock(Crawler::class);

        if ($returnText !== null) {
            $elementCrawler->method('text')
                ->willReturn($returnText);
        }

        if ($returnHtml) {
            $elementCrawler->method('html')
                ->willReturn($returnText);
        }

        $crawler->method('filter')
            ->with($selector)
            ->willReturn($elementCrawler);
    }

    protected function mockSuccessfulScenario(MockObject $crawler): void
    {
        // Mock wait behavior
        $this->wait->expects($this->exactly(2))
            ->method('until')
            ->willReturn(true);

        // Mock all necessary elements
        $this->mockCrawlerFilter($crawler, 'h4', 'Test Kata Name');
        $this->mockCrawlerFilter($crawler, '.small-hex span', '6 kyu');
        $this->mockCrawlerFilter($crawler, '[data-tippy-content="This kata\'s Sensei"]', 'Test Author');
        $this->mockCrawlerFilter($crawler, '.description-content .markdown', '<div>Test description</div>', true);

        $tagsCrawler = new Crawler();
        $tagsCrawler->addHtmlContent('<div class="keyword-tag">algorithms</div>');
        $this->mockCrawlerFilter($crawler, '.keyword-tag', null, false, $tagsCrawler);

        // Mock JavaScript executions
        $this->client->method('executeScript')
            ->willReturnMap([
                ['window.scrollTo(0, document.body.scrollHeight);', null],
                ['window.scrollTo(0, 0);', null],
                ['return document.querySelector(".description-content .markdown").innerHTML;', '<div>Test description</div>'],
                ['return Array.from(document.querySelectorAll(".language-selector dd")).map(el => el.textContent.trim()).filter(text => text);', ['PHP']],
            ]);

        // Mock solution and tests
        $this->mockCrawlerFilter($crawler, '#code .CodeMirror-line', 'function solution() {}');
        $this->mockCrawlerFilter($crawler, '#fixture .CodeMirror-line', 'test("should work", () => {});');

        // Mock HTML converter
        $this->htmlConverter->method('convert')
            ->willReturn('Test description');
    }
}
