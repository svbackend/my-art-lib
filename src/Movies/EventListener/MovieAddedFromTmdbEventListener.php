<?php

declare(strict_types=1);

namespace App\Movies\EventListener;

use App\Movies\Entity\ReleaseDateQueue;
use App\Movies\Event\MovieAddedFromTmdbEvent;
use App\Movies\Service\ImdbIdLoaderService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

class MovieAddedFromTmdbEventListener
{
    /** @var $em EntityManagerInterface */
    private $em;

    private $imdbIdLoaderService;

    public function __construct(EntityManagerInterface $em, ImdbIdLoaderService $imdbIdLoaderService)
    {
        $this->em = $em;
        $this->imdbIdLoaderService = $imdbIdLoaderService;
    }

    public function onMovieAddedFromTmdb(MovieAddedFromTmdbEvent $event): void
    {
        $movie = $event->getMovie();

        // If we dont know release date yet or it would be in the future - add movie to queue
        // All movies in queue will be checked every day for any info about release date (parsing imdb/tmdb)
        if ($movie->getReleaseDate() === null || $movie->getReleaseDate()->getTimestamp() > time()) {
            $releaseDateQueueItem = new ReleaseDateQueue($movie);
            $this->em->persist($releaseDateQueueItem);
            try {
                $this->em->flush();
            } catch (UniqueConstraintViolationException $exception) {
                // It's ok
            }
        }

        if ($movie->getImdbId() === null) {
            $theMovieDbId = $movie->getTmdb()->getId();
            $imdbId = $this->imdbIdLoaderService->getImdbId($theMovieDbId);
            $movie->setImdbId($imdbId);
            $this->em->persist($movie);
            $this->em->flush();
        }
    }
}
