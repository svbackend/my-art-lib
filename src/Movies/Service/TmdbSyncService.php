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
     * @param \Iterator $movies
     * @param bool          $loadSimilar
     * @param array         $similarMoviesTable
     */
    public function syncMovies(\Iterator $movies, bool $loadSimilar = true, array $similarMoviesTable = []): void
    {
        /**
         * @var $movies \Iterator
         */
        if (!$movies->current()) {
            return;
        }

        if ($this->isSupport($movies->current()) === false) {
            throw new \InvalidArgumentException('Unsupported array of movies provided');
        }

        foreach ($movies as $movie) {
            $message = new Message(serialize([$movie]), [
                'load_similar' => $loadSimilar,
                'similar_movies_table' => $similarMoviesTable,
            ]);

            $this->producer->sendEvent(MovieSyncProcessor::ADD_MOVIES_TMDB, $message);
        }

    }

    private function isSupport($movie)
    {
        return $movie instanceof Movie;
    }
}
