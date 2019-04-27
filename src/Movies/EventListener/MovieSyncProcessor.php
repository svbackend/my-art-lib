<?php

namespace App\Movies\EventListener;

use App\Actors\EventListener\ActorSyncProcessor;
use App\Movies\Entity\Movie;
use App\Movies\Event\MovieAddedFromTmdbEvent;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbNormalizerService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MovieSyncProcessor implements PsrProcessor, TopicSubscriberInterface
{
    public const ADD_MOVIES_TMDB = 'addMoviesTMDB';
    public const PARAM_LOAD_SIMILAR_MOVIES = 'loadSimilarMovies';

    private $em;
    private $producer;
    private $normalizer;
    private $logger;
    private $movieRepository;
    private $dispatcher;

    public function __construct(EntityManagerInterface $em, ProducerInterface $producer, TmdbNormalizerService $normalizer, LoggerInterface $logger, MovieRepository $movieRepository, EventDispatcherInterface $dispatcher)
    {
        $this->em = $em;
        $this->producer = $producer;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
        $this->movieRepository = $movieRepository;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param PsrMessage $message
     * @param PsrContext $session
     *
     * @throws \ErrorException
     *
     * @return object|string
     */
    public function process(PsrMessage $message, PsrContext $session)
    {
        $this->logger->info('MovieSyncProcessor start with memory usage: ', [memory_get_usage()]);

        $movie = $message->getBody();
        $movie = json_decode($movie, true);

        if (null !== $this->movieRepository->findOneByIdOrTmdbId(null, (int) $movie['id'])) {
            return self::ACK;
        }

        $movies = $this->normalizer->normalizeMoviesToObjects([$movie]);
        /** @var $movie Movie */
        $movie = $movies->current();

        if ($this->em->isOpen() === false) {
            throw new \ErrorException('em is closed');
        }

        $this->em->persist($movie);
        $this->logger->info(sprintf('Saved %s with id %s', $movie->getOriginalTitle(), $movie->getId()));

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueException) {
            return self::ACK;
        }

        $event = new MovieAddedFromTmdbEvent($movie);
        $this->dispatcher->dispatch($event::NAME, $event);

        $this->em->clear();

        $this->loadTranslations($movie->getId());
        if ($message->getProperty(self::PARAM_LOAD_SIMILAR_MOVIES, false) === true) {
            $this->loadSimilarMovies($movie->getId());
        }
        $this->loadActors($movie->getId());
        $this->loadPosters($movie->getId());

        $message = $session = $movies = $savedMoviesIds = $savedMoviesTmdbIds = $moviesCount = null;
        unset($message, $session, $movies, $savedMoviesIds, $savedMoviesTmdbIds, $moviesCount);

        $this->logger->info('MovieSyncProcessor end with memory usage: ', [memory_get_usage()]);

        return self::ACK;
    }

    private function loadTranslations(int $movieId)
    {
        $this->producer->sendEvent(MovieTranslationsProcessor::LOAD_TRANSLATIONS, json_encode($movieId));
    }

    private function loadSimilarMovies(int $movieId)
    {
        $message = new Message(json_encode($movieId));
        $message->setPriority(MessagePriority::VERY_LOW);
        $this->producer->sendEvent(SimilarMoviesProcessor::LOAD_SIMILAR_MOVIES, $message);
    }

    private function loadPosters(int $movieId)
    {
        $message = new Message(json_encode($movieId));
        $message->setPriority(MessagePriority::VERY_LOW);
        $this->producer->sendEvent(MoviePostersProcessor::LOAD_POSTERS, $message);
    }

    private function loadActors(int $movieId)
    {
        $message = new Message(json_encode($movieId));
        $this->producer->sendEvent(ActorSyncProcessor::ADD_ACTOR, $message);
    }

    public static function getSubscribedTopics(): array
    {
        return [self::ADD_MOVIES_TMDB];
    }
}
