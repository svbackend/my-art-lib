<?php

namespace App\Movies\EventListener;

use App\Genres\Entity\Genre;
use App\Movies\Entity\Movie;
use App\Movies\Service\TmdbNormalizerService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

class MovieSyncProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const ADD_MOVIES_TMDB = 'addMoviesTMDB';

    private $em;
    private $producer;
    private $normalizer;
    private $logger;

    public function __construct(EntityManagerInterface $em, ProducerInterface $producer, TmdbNormalizerService $normalizer, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->producer = $producer;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        $this->logger->info('MovieSyncProcessor start with memory usage: ', [memory_get_usage()]);

        $movie = $message->getBody();
        $movie = json_decode($movie, true);
        $movies = $this->normalizer->normalizeMoviesToObjects([$movie]);
        $savedMoviesIds = [];
        $savedMoviesTmdbIds = []; // tmdbId => Movie Object
        $moviesCount = 0;

        if ($this->em->isOpen() === false) {
            throw new \ErrorException('em is closed');
        }

        foreach ($movies as $movie) {
            $this->em->persist($movie);
            $this->logger->info(sprintf('Saved %s with id %s', $movie->getOriginalTitle(), $movie->getId()));
            $savedMoviesIds[] = $movie->getId();
            $savedMoviesTmdbIds[$movie->getTmdb()->getId()] = $movie;
            ++$moviesCount;
        }

        $this->em->flush();
        $this->em->clear();

        $this->loadTranslations($savedMoviesIds);
        $this->loadSimilarMovies($savedMoviesIds);
        $this->loadPosters($savedMoviesIds);

        $message = $session = $movies = $savedMoviesIds = $savedMoviesTmdbIds = $moviesCount = null;
        unset($message, $session, $movies, $savedMoviesIds, $savedMoviesTmdbIds, $moviesCount);

        $this->logger->info('MovieSyncProcessor end with memory usage: ', [memory_get_usage()]);

        return self::ACK;
    }

    private function loadTranslations(array $moviesIds)
    {
        foreach ($moviesIds as $movieId) {
            $this->producer->sendEvent(MovieTranslationsProcessor::LOAD_TRANSLATIONS, json_encode($movieId));
        }
    }

    private function loadSimilarMovies(array $moviesIds)
    {
        foreach ($moviesIds as $id) {
            $message = new Message(json_encode($id));
            $message->setPriority(MessagePriority::VERY_LOW);
            $this->producer->sendEvent(SimilarMoviesProcessor::LOAD_SIMILAR_MOVIES, $message);
        }
    }

    private function loadPosters(array $moviesIds)
    {
        foreach ($moviesIds as $id) {
            $message = new Message(json_encode($id));
            $message->setPriority(MessagePriority::VERY_LOW);
            $this->producer->sendEvent(MoviePostersProcessor::LOAD_POSTERS, $message);
        }

    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_MOVIES_TMDB];
    }
}
