<?php

namespace Dojo\Domain\Model;

use InvalidArgumentException;

class Kata
{
    private const VALID_KYU_LEVELS = ['1 kyu', '2 kyu', '3 kyu', '4 kyu', '5 kyu', '6 kyu', '7 kyu', '8 kyu'];
    private const VALID_DAN_LEVELS = ['1 dan', '2 dan', '3 dan', '4 dan', '5 dan', '6 dan', '7 dan', '8 dan'];

    public function __construct(
        private string $id,
        private string $url,
        private string $name,
        private string $description,
        private string $difficulty,
        private string $language,
        private string $solutionPlaceholder,
        private string $tests,
        private array $tags,
        private ?string $category,
        private ?string $author,
        private array $languagesAvailable
    ) {
        $this->validate();
    }

    private function validate(): void
    {
        if (empty($this->id)) {
            throw new InvalidArgumentException('ID cannot be empty');
        }

        if (empty($this->url)) {
            throw new InvalidArgumentException('URL cannot be empty');
        }

        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('Invalid URL format');
        }

        if (!preg_match('#^https?://(?:www\.)?codewars\.com/kata/#', $this->url)) {
            throw new InvalidArgumentException('URL must be a valid Codewars kata URL');
        }

        if (empty($this->name)) {
            throw new InvalidArgumentException('Name cannot be empty');
        }

        if (empty($this->description)) {
            throw new InvalidArgumentException('Description cannot be empty');
        }

        if (!in_array($this->difficulty, self::VALID_KYU_LEVELS, true) &&
            !in_array($this->difficulty, self::VALID_DAN_LEVELS, true)) {
            if (preg_match('/^\d (kyu|dan)$/', $this->difficulty)) {
                throw new InvalidArgumentException('Invalid difficulty level. Must be between 1-8 kyu or 1-8 dan');
            }

            throw new InvalidArgumentException('Invalid difficulty format. Must be "X kyu" or "X dan"');
        }

        if (empty($this->language)) {
            throw new InvalidArgumentException('Language cannot be empty');
        }

        if (empty($this->solutionPlaceholder)) {
            throw new InvalidArgumentException('Solution placeholder cannot be empty');
        }

        if (empty($this->tests)) {
            throw new InvalidArgumentException('Tests cannot be empty');
        }

        if (!is_array($this->tags)) {
            throw new InvalidArgumentException('Tags must be an array');
        }

        if (!is_array($this->languagesAvailable)) {
            throw new InvalidArgumentException('Languages available must be an array');
        }

        if (empty($this->languagesAvailable)) {
            throw new InvalidArgumentException('At least one language must be available');
        }

        if (!in_array($this->language, $this->languagesAvailable, true)) {
            throw new InvalidArgumentException('Selected language must be in available languages');
        }

        // Check for duplicate languages
        if (count(array_unique($this->languagesAvailable)) !== count($this->languagesAvailable)) {
            throw new InvalidArgumentException('Languages available must be unique');
        }

        // Check for duplicate tags
        if (count(array_unique($this->tags)) !== count($this->tags)) {
            throw new InvalidArgumentException('Tags must be unique');
        }
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getDifficulty(): string
    {
        return $this->difficulty;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getSolutionPlaceholder(): string
    {
        return $this->solutionPlaceholder;
    }

    public function getTests(): string
    {
        return $this->tests;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function getLanguagesAvailable(): array
    {
        return $this->languagesAvailable;
    }
}
