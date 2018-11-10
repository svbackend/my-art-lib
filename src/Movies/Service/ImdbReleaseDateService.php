<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Countries\Entity\Country;
use App\Movies\Entity\Movie;
use App\Movies\Repository\MovieReleaseDateRepository;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

class ImdbReleaseDateService
{
    private $repository;
    private $cache;
    private $parser;
    private $logger;

    public function __construct(MovieReleaseDateRepository $repository, CacheInterface $cache, ImdbReleaseDateParserService $parser, LoggerInterface $logger)
    {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->parser = $parser;
        $this->logger = $logger;
    }

    /**
     * @throws
     */
    public function getReleaseDate(Movie $movie, Country $country): ?\DateTimeInterface
    {
        if ($this->cache->get($this->getCacheKeyForIsParsedFlag($movie), false) === false) {
            $this->logger->debug("[ImdbReleaseDateService] Cache for key {$this->getCacheKeyForIsParsedFlag($movie)} not found, so lets parse imdb");
            $this->parseReleaseDates($movie);
        }

        $timestamp = $this->cache->get($this->getCacheKeyForDate($movie, $country));
        $this->logger->debug("[ImdbReleaseDateService] Trying to get releaseDate for movie {$movie->getOriginalTitle()} in {$country->getCode()}, result is: {$timestamp}");
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
        $this->logger->debug("[ImdbReleaseDateService] parseReleaseDates result: ", $result);

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
