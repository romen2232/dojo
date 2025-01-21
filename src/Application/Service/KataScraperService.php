<?php

namespace Dojo\Application\Service;

use Dojo\Domain\Model\Kata;
use Dojo\Domain\Port\KataRepositoryInterface;

class KataScraperService
{
    public function __construct(
        private KataRepositoryInterface $kataRepository
    ) {
    }

    /**
     * Scrapes a kata from Codewars
     *
     * @param string $url The URL of the kata to scrape
     * @return Kata The scraped kata
     * @throws \InvalidArgumentException If the URL is invalid
     * @throws \RuntimeException If the kata cannot be scraped
     */
    public function scrapeKata(string $url): Kata
    {
        return $this->kataRepository->getKataByUrl($url);
    }
}
