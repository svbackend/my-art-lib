<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Countries\Entity\Country;
use App\Movies\Entity\Movie;
use App\Movies\Repository\MovieReleaseDateRepository;
use Psr\SimpleCache\CacheInterface;

class ImdbReleaseDateService
{
    private $repository;
    private $cache;
    private $parser;

    public function __construct(MovieReleaseDateRepository $repository, CacheInterface $cache, ImdbReleaseDateParserService $parser)
    {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->parser = $parser;
    }

    /**
     * @throws
     */
    public function getReleaseDate(Movie $movie, Country $country): ?\DateTimeInterface
    {
        if ($this->cache->get($this->getCacheKeyForIsParsedFlag($movie), false) === false) {
            $this->parseReleaseDates($movie);
        }

        $timestamp = $this->cache->get($this->getCacheKeyForDate($movie, $country));
        if ($timestamp === null) {
            return null;
        }

        return (new \DateTimeImmutable())->setTimestamp($timestamp);
    }

    private function getCacheKeyForDate(Movie $movie, Country $country): string
    {
        return sprintf('%s_%s', $movie->getId(), $country->getCode());
    }

    private function getCacheKeyForIsParsedFlag(Movie $movie): string
    {
        return 'is_parsed'.$movie->getId();
    }

    /**
     * @throws
     */
    private function parseReleaseDates(Movie $movie): void
    {
        $result = $this->parser->getReleaseDates($movie);

        /**
         * @var string
         * @var $date  \DateTimeInterface
         */
        foreach ($result as $countryCode => $date) {
            $country = new Country('', $countryCode);
            $this->cache->set($this->getCacheKeyForDate($movie, $country), $date->getTimestamp(), 3600);
        }

        $this->cache->set($this->getCacheKeyForIsParsedFlag($movie), true, 3600);
    }
}
