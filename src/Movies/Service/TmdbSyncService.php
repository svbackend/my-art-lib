<?php
declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Entity\Movie;
use App\Movies\Event\MovieSyncProcessor;
use App\Movies\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Enqueue\Client\ProducerInterface;

class TmdbSyncService
{
    private $logger;
    private $repository;
    private $em;
    private $producer;

    public function __construct(LoggerInterface $logger, MovieRepository $repository, EntityManagerInterface $entityManager, ProducerInterface $producer)
    {
        $this->logger = $logger;
        $this->repository = $repository;
        $this->em = $entityManager;
        $this->producer = $producer;
    }

    public function syncMovies(array $movies): void
    {
        if (false === $this->isSupport(reset($movies))) {
            $this->logger->error('Unsupported array of movies provided', [
                'movies' => $movies
            ]);
            throw new \InvalidArgumentException('Unsupported array of movies provided');
        }

        // todo movies to update
        $moviesToSave = $this->getMoviesToSave($movies);
        $this->addMovies($moviesToSave);
    }

    private function addMovies(array $movies): void
    {
        $this->logger->debug('START Send event ADD_MOVIES_TMDB');
        $this->producer->sendEvent(MovieSyncProcessor::ADD_MOVIES_TMDB, serialize($movies));
        $this->logger->debug('END Send event ADD_MOVIES_TMDB');
        /*
        // todo call method to add movies into the queue and then insert them into the database
        foreach ($movies as $movie) {
            $this->em->persist($movie);
        }

        $this->em->flush();*/
    }

    private function updateMovies(array $movies)
    {
        // todo call method to update movies in the database (use queue)
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

    private function getAlreadySavedMoviesIdsByTmdbIds(array $ids)
    {
         return $this->repository->getTmdbIds($ids);
    }

    private function isSupport($movie)
    {
        return $movie instanceof Movie;
    }
}