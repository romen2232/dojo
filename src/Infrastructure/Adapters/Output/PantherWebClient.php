<?php

namespace Dojo\Infrastructure\Adapters\Output;

use Dojo\Domain\Port\WebClientInterface;
use Symfony\Component\Panther\Client as PantherClient;
use Symfony\Component\DomCrawler\Crawler;
use Facebook\WebDriver\WebDriverWait;

class PantherWebClient implements WebClientInterface
{
    private PantherClient $client;

    public function __construct(?PantherClient $client = null)
    {
        $this->client = $client ?? $this->createClient();
    }

    private function createClient(): PantherClient
    {
        return PantherClient::createChromeClient(null, [
            '--headless',
            '--disable-gpu',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--window-size=1920,1080'
        ]);
    }

    public function request(string $method, string $url): Crawler
    {
        return $this->client->request($method, $url);
    }

    public function wait(): WebDriverWait
    {
        return $this->client->wait();
    }

    public function executeScript(string $script): mixed
    {
        return $this->client->executeScript($script);
    }

    public function getCurrentURL(): string
    {
        return $this->client->getCurrentURL();
    }

    public function getCrawler(): Crawler
    {
        return $this->client->getCrawler();
    }
}
