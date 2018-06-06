<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Entity\Movie;
use App\Movies\EventListener\MovieSyncProcessor;
use App\Movies\Repository\MovieRepository;
use Enqueue\Client\ProducerInterface;

class TmdbSyncService
{
    private $repository;
    private $producer;

    public function __construct(MovieRepository $repository, ProducerInterface $producer)
    {
        $this->repository = $repository;
        $this->producer = $producer;
    }

    /**
     * @param array|Movie[] $movies
     */
    public function syncMovies(array $movies): void
    {
        if (false === $this->isSupport(reset($movies))) {
            throw new \InvalidArgumentException('Unsupported array of movies provided');
        }

        $this->addMovies($movies);
    }

    private function addMovies(array $movies): void
    {
        $this->producer->sendEvent(MovieSyncProcessor::ADD_MOVIES_TMDB, serialize($movies));
    }

    private function isSupport($movie)
    {
        return $movie instanceof Movie;
    }
}
