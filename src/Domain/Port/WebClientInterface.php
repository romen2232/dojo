<?php

namespace Dojo\Domain\Port;

use Facebook\WebDriver\WebDriverWait;
use Symfony\Component\DomCrawler\Crawler;

interface WebClientInterface
{
    public function request(string $method, string $url): Crawler;

    public function wait(): WebDriverWait;

    public function executeScript(string $script): mixed;

    public function getCurrentURL(): string;

    public function getCrawler(): Crawler;
}
