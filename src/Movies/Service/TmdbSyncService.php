<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\Entity\Movie;
use App\Movies\EventListener\MovieSyncProcessor;
use App\Movies\Repository\MovieRepository;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
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

    public function syncMovies(array $movies, bool $loadSimilar = false): void
    {
        if (!count($movies)) {
            return;
        }

        if ($this->isSupport(reset($movies)) === false) {
            throw new \InvalidArgumentException('Unsupported array of movies provided');
        }

        $alreadySavedMovies = $this->repository->findAllByTmdbIds(array_map(function (array $tmdbMovie) {
            return $tmdbMovie['id'];
        }, $movies));
        $alreadySavedMoviesIds = array_flip(array_map(function (Movie $movie) {
            return $movie->getId();
        }, $alreadySavedMovies));

        foreach ($movies as $movie) {
            if (isset($alreadySavedMoviesIds[$movie['id']])) {
                continue;
            }

            $message = new Message(json_encode($movie));
            $message->setPriority(MessagePriority::HIGH);
            $message->setProperty(MovieSyncProcessor::PARAM_LOAD_SIMILAR_MOVIES, $loadSimilar);

            $this->producer->sendEvent(MovieSyncProcessor::ADD_MOVIES_TMDB, $message);
        }
    }

    private function isSupport($movie)
    {
        return is_array($movie) && isset($movie['id']) && isset($movie['original_title']);
    }
}
