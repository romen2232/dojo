<?php

namespace Dojo\Domain\Port;

use Dojo\Domain\Model\Kata;
use InvalidArgumentException;

interface KataRepositoryInterface
{
    /**
     * Get a Kata by its URL
     *
     * @param string $url The URL of the kata to retrieve
     * @return Kata The retrieved kata
     * @throws InvalidArgumentException If the URL is invalid, empty, not a Codewars URL, or the kata is not found
     */
    public function getKataByUrl(string $url): Kata;
}
