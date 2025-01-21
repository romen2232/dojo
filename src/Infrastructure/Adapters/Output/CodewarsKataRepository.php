<?php

namespace Dojo\Infrastructure\Adapters\Output;

use Dojo\Domain\Model\Kata;
use Dojo\Domain\Port\KataRepositoryInterface;
use Dojo\Domain\Port\WebClientInterface;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use League\HTMLToMarkdown\HtmlConverter;
use RuntimeException;

class CodewarsKataRepository implements KataRepositoryInterface
{
    private WebClientInterface $client;
    private HtmlConverter $htmlConverter;
    private const MAX_RETRIES = 5;
    private const RETRY_DELAY = 1; // seconds
    private const LANGUAGE_MAP = [
        'php' => 'PHP',
        'python' => 'Python',
        'javascript' => 'JavaScript',
        'typescript' => 'TypeScript',
        'java' => 'Java',
        'csharp' => 'C#',
        'cpp' => 'C++',
        'c' => 'C',
        'ruby' => 'Ruby',
        'swift' => 'Swift',
        'go' => 'Go',
        'rust' => 'Rust',
        'shell' => 'Shell',
        'sql' => 'SQL',
        'coffeescript' => 'CoffeeScript',
        'crystal' => 'Crystal',
        'dart' => 'Dart',
        'elixir' => 'Elixir',
        'elm' => 'Elm',
        'erlang' => 'Erlang',
        'fsharp' => 'F#',
        'haskell' => 'Haskell',
        'julia' => 'Julia',
        'kotlin' => 'Kotlin',
        'lua' => 'Lua',
        'nasm' => 'NASM',
        'nim' => 'Nim',
        'objc' => 'Objective-C',
        'ocaml' => 'OCaml',
        'pascal' => 'Pascal',
        'perl' => 'Perl',
        'php' => 'PHP',
        'powershell' => 'PowerShell',
        'prolog' => 'Prolog',
        'purescript' => 'PureScript',
        'r' => 'R',
        'racket' => 'Racket',
        'reason' => 'Reason',
        'scala' => 'Scala',
        'scheme' => 'Scheme',
        'solidity' => 'Solidity',
        'vb' => 'VB',
    ];

    public function __construct(?WebClientInterface $client = null, ?HtmlConverter $htmlConverter = null)
    {
        $this->client = $client ?? new PantherWebClient(null);
        $this->htmlConverter = $htmlConverter ?? new HtmlConverter([
            'strip_tags' => false,
            'remove_nodes' => 'script style',
            'hard_break' => true,
            'preserve_comments' => false,
        ]);
    }

    protected function log(string $message): void
    {
        echo "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    }

    protected function logElement(string $element, ?string $value): void
    {
        $this->log(sprintf(
            "Found %s: %s",
            $element,
            $value ? (strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value) : 'NOT FOUND'
        ));
    }

    private function convertHtmlToMarkdown(string $html): string
    {
        return $this->htmlConverter->convert($html);
    }

    private function normalizeLanguage(string $language): string
    {
        $normalized = strtolower(trim($language));

        return self::LANGUAGE_MAP[$normalized] ?? $language;
    }

    private function normalizeLanguages(array $languages): array
    {
        return array_map([$this, 'normalizeLanguage'], $languages);
    }

    public function getKataByUrl(string $url): Kata
    {
        $retries = 0;
        while ($retries < self::MAX_RETRIES) {
            try {
                $this->log("Attempt " . ($retries + 1) . " to fetch kata...");

                // Extract kata ID from URL
                preg_match('/kata\/([\w\d]+)/', $url, $matches);
                $kataId = $matches[1] ?? null;

                if (!$kataId) {
                    throw new RuntimeException("Could not extract kata ID from URL: " . $url);
                }

                $this->log("Fetching kata with ID: " . $kataId);

                // Navigate to the kata page
                $this->log("Navigating to URL: " . $url);
                $crawler = $this->client->request('GET', $url);
                $this->log("Page loaded, current URL: " . $this->client->getCurrentURL());

                // Wait for the loading text to disappear
                $this->log("Waiting for loading text to disappear...");
                $this->client->wait()->until(
                    WebDriverExpectedCondition::invisibilityOfElementLocated(
                        WebDriverBy::xpath("//div[contains(text(), 'Loading description...')]")
                    )
                );
                $this->log("Loading text disappeared");

                // Wait for description to be present and visible
                $this->log("Waiting for description element to be present...");
                $this->client->wait()->until(
                    WebDriverExpectedCondition::presenceOfElementLocated(
                        WebDriverBy::cssSelector('.description-content .markdown')
                    )
                );
                $this->log("Description element is present");

                $this->log("Waiting for description element to be visible...");
                $this->client->wait()->until(
                    WebDriverExpectedCondition::visibilityOfElementLocated(
                        WebDriverBy::cssSelector('.description-content .markdown')
                    )
                );
                $this->log("Description element is visible");

                // Scroll to the bottom of the page to ensure all content is loaded
                $this->log("Scrolling to the bottom of the page...");
                $this->client->executeScript('window.scrollTo(0, document.body.scrollHeight);');
                sleep(1); // Wait for any dynamic content to load
                $this->client->executeScript('window.scrollTo(0, 0);'); // Scroll back to top
                sleep(1); // Wait for scroll to complete

                // Get kata name
                $this->log("Extracting kata name...");
                $name = $crawler->filter('h4')->text();
                $this->logElement('name', $name);

                // Get difficulty
                $this->log("Extracting difficulty...");
                $difficulty = $crawler->filter('.small-hex span')->text();
                $this->logElement('difficulty', $difficulty);

                // Get description - refresh the crawler and try different selectors
                $this->log("Refreshing crawler and extracting description...");
                $crawler = $this->client->getCrawler();

                try {
                    $descriptionElement = $crawler->filter('.description-content .markdown');
                    $this->log("Found description element, attempting to get content...");

                    // Get the innerHTML using JavaScript
                    $description = $this->client->executeScript(
                        'return document.querySelector(".description-content .markdown").innerHTML;'
                    );

                    if (!$description) {
                        throw new \Exception("Description is empty");
                    }

                    // Convert HTML to Markdown
                    $description = $this->convertHtmlToMarkdown($description);
                } catch (\Exception $e) {
                    $this->log("Failed to get description with first method: " . $e->getMessage());

                    try {
                        $this->log("Trying alternative method with textContent...");
                        $description = $this->client->executeScript(
                            'return document.querySelector(".description-content .markdown").textContent;'
                        );

                        if (!$description) {
                            throw new \Exception("Description is empty");
                        }
                    } catch (\Exception $e2) {
                        $this->log("Failed with second method: " . $e2->getMessage());

                        try {
                            $this->log("Trying to get content from parent element...");
                            $description = $this->client->executeScript(
                                'return document.querySelector(".description-content").textContent;'
                            );

                            if (!$description) {
                                throw new \Exception("Description is empty");
                            }
                        } catch (\Exception $e3) {
                            $description = "Failed to extract description";
                            $this->log("All description extraction attempts failed");
                        }
                    }
                }
                $this->logElement('description', $description);

                // Get author
                $this->log("Extracting author...");
                $author = $crawler->filter('[data-tippy-content="This kata\'s Sensei"]')->text();
                $this->logElement('author', $author);

                // Get category and tags
                $this->log("Extracting tags...");
                $tags = $crawler->filter('.keyword-tag')->each(function ($node) {
                    return $node->text();
                });
                $this->logElement('tags', implode(', ', $tags));
                $category = $tags[0] ?? null;
                $this->logElement('category', $category);

                // Get languages available
                $this->log("Extracting available languages...");

                try {
                    $languages = $this->client->executeScript(
                        'return Array.from(document.querySelectorAll(".language-selector dd")).map(el => el.textContent.trim()).filter(text => text);'
                    );
                    if (!$languages) {
                        throw new \Exception("No languages found");
                    }
                } catch (\Exception $e) {
                    $this->log("Failed to get languages with JavaScript, trying crawler...");

                    try {
                        $languages = $crawler->filter('.language-selector dd')->each(function ($node) {
                            $text = trim($node->text());

                            return $text ?: null;
                        });
                        $languages = array_filter($languages);
                    } catch (\Exception $e2) {
                        $this->log("Failed to get languages: " . $e2->getMessage());
                        $languages = ['Unknown'];
                    }
                }
                $this->logElement('languages', implode(', ', $languages));

                // Get solution placeholder and tests
                $this->log("Extracting solution placeholder...");
                $solutionPlaceholder = $crawler->filter('#code .CodeMirror-line')->each(function ($node) {
                    return $node->text();
                });
                $solutionPlaceholder = implode("\n", $solutionPlaceholder);
                $this->logElement('solution placeholder', $solutionPlaceholder);

                $this->log("Extracting tests...");
                $tests = $crawler->filter('#fixture .CodeMirror-line')->each(function ($node) {
                    return $node->text();
                });
                $tests = implode("\n", $tests);
                $this->logElement('tests', $tests);

                $this->log("Successfully fetched all kata information");

                // Extract language from URL
                $urlParts = explode('/', rtrim($url, '/'));
                $languageFromUrl = end($urlParts);
                $this->log("Language extracted from URL: " . $languageFromUrl);

                // Normalize languages
                $languageFromUrl = $this->normalizeLanguage($languageFromUrl);
                $languages = $this->normalizeLanguages($languages);

                return new Kata(
                    id: $kataId,
                    url: $url,
                    name: $name,
                    description: $description,
                    difficulty: $difficulty,
                    language: $languageFromUrl,
                    solutionPlaceholder: $solutionPlaceholder,
                    tests: $tests,
                    tags: $tags,
                    category: $category,
                    author: $author,
                    languagesAvailable: $languages
                );
            } catch (\Exception $e) {
                $this->log("ERROR: " . get_class($e) . ": " . $e->getMessage());
                $this->log("Stack trace:\n" . $e->getTraceAsString());

                if ($retries >= self::MAX_RETRIES - 1) {
                    throw $e;
                }
                $retries++;
                $this->log("Retrying in " . self::RETRY_DELAY . " seconds...");
                sleep(self::RETRY_DELAY);
            }
        }

        throw new RuntimeException("Failed to fetch kata after " . self::MAX_RETRIES . " attempts");
    }

    private function mapRankToKyu(int $rank): string
    {
        if ($rank < 0) {
            return abs($rank) . ' kyu';
        }

        return $rank . ' dan';
    }
}
