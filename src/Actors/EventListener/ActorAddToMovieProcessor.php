<?php

namespace App\Actors\EventListener;

use App\Actors\Entity\Actor;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbNormalizerService;
use App\Movies\Service\TmdbSearchService;
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

class ActorAddToMovieProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const ADD_TO_MOVIE = 'addActorToMovie';

    private $em;
    private $producer;
    private $normalizer;
    private $logger;
    private $movieRepository;
    private $searchService;

    public function __construct(EntityManagerInterface $em, ProducerInterface $producer, TmdbNormalizerService $normalizer, LoggerInterface $logger, MovieRepository $movieRepository, TmdbSearchService $searchService)
    {
        $this->em = $em;
        $this->producer = $producer;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
        $this->movieRepository = $movieRepository;
        $this->searchService = $searchService;
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        $this->logger->info('SaveActorProcessor start with memory usage: ', [memory_get_usage()]);

        $messageBody = $message->getBody();
        $data = json_decode($messageBody, true);
        $movieId = $data['movieId'];
        $actorTmdbId = $data['actorTmdbId'];

        if (null === $movie = $this->movieRepository->find($movieId)) {
            return self::REJECT;
        }

        if (null === $actor = $this->actorRepository->findByTmdbId($actorTmdbId)) {
            return self::REJECT;
        }

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueException) {
            // its ok
        }

        $this->em->clear();
        return self::ACK;
    }

    private function loadTranslations(int $actorId)
    {
        $message = new Message(json_encode($actorId));
        $this->producer->sendEvent(ActorTranslationsProcessor::LOAD_TRANSLATIONS, $message);
    }

    public static function getSubscribedTopics()
    {
        return [self::SAVE_ACTOR];
    }
}
