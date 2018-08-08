<?php

namespace App\Actors\EventListener;

use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbSearchService;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

class ActorSyncProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const ADD_ACTOR = 'addActor';

    private $producer;
    private $logger;
    private $movieRepository;
    private $searchService;

    public function __construct(ProducerInterface $producer, LoggerInterface $logger, MovieRepository $movieRepository, TmdbSearchService $searchService)
    {
        $this->producer = $producer;
        $this->logger = $logger;
        $this->movieRepository = $movieRepository;
        $this->searchService = $searchService;
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        $this->logger->info('ActorSyncProcessor start with memory usage: ', [memory_get_usage()]);

        $movieId = $message->getBody();
        $movieId = json_decode($movieId, true);

        if (null === $movie = $this->movieRepository->find($movieId)) {
            return self::REJECT;
        }

        $movieTmdbId = $movie->getTmdb()->getId();
        $actorsList = $this->searchService->findActorsByMovieId($movieTmdbId)['cast'];
        $actorsList = array_slice($actorsList, 0, 10);

        foreach ($actorsList as $actor) {
            $this->saveActor($actor['id']);
            $this->addActorToMovie($actor['id'], $movie->getId());
        }

        return self::ACK;
    }

    private function saveActor(int $actorTmdbId)
    {
        $message = new Message(json_encode($actorTmdbId));
        $this->producer->sendEvent(SaveActorProcessor::SAVE_ACTOR, $message);
    }

    private function addActorToMovie(int $actorTmdbId, int $movieId)
    {
        $message = new Message(json_encode([
            'actorTmdbId' => $actorTmdbId,
            'movieId' => $movieId,
        ]));
        $message->setPriority(MessagePriority::VERY_LOW);
        $this->producer->sendEvent(ActorAddToMovieProcessor::ADD_TO_MOVIE, $message);
    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_ACTOR];
    }
}
