<?php
declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Entity\Movie;
use App\Movies\Event\MovieSyncProcessor;
use App\Movies\Repository\MovieRepository;
use Psr\Log\LoggerInterface;
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

        $moviesToSave = $this->getMoviesToSave($movies);
        $this->addMovies($moviesToSave);
    }

    private function addMovies(array $movies): void
    {
        $this->producer->sendEvent(MovieSyncProcessor::ADD_MOVIES_TMDB, serialize($movies));
    }

    /**
     * This method give you array of movies which is not yet loaded to our database
     * @param array $movies
     * @return array
     */
    private function getMoviesToSave(array $movies): array
    {
        $ids = array_map(function (Movie $movie) { return $movie->getTmdb()->getId(); }, $movies);
        $alreadySavedIds = $this->getAlreadySavedMoviesIdsByTmdbIds($ids);

        return array_filter($movies, function (Movie $movie) use ($alreadySavedIds) {
            return in_array($movie->getTmdb()->getId(), $alreadySavedIds) === false;
        });
    }

    private function getAlreadySavedMoviesIdsByTmdbIds(array $tmdb_ids)
    {
         return $this->repository->getExistedTmdbIds($tmdb_ids);
    }

    private function isSupport($movie)
    {
        return $movie instanceof Movie;
    }
}