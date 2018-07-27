<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Entity\Movie;
use App\Movies\EventListener\MovieSyncProcessor;
use App\Movies\Repository\MovieRepository;
use Enqueue\Client\Message;
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
     * @param bool          $loadSimilar
     * @param array         $similarMoviesTable
     */
    public function syncMovies(array $movies, bool $loadSimilar = true, array $similarMoviesTable = []): void
    {
        if (!count($movies)) {
            return;
        }

        if ($this->isSupport(reset($movies)) === false) {
            throw new \InvalidArgumentException('Unsupported array of movies provided');
        }

        $message = new Message(serialize($movies), [
            'load_similar' => $loadSimilar,
            'similar_movies_table' => $similarMoviesTable,
        ]);

        $this->producer->sendEvent(MovieSyncProcessor::ADD_MOVIES_TMDB, $message);
    }

    private function isSupport($movie)
    {
        return $movie instanceof Movie;
    }
}
