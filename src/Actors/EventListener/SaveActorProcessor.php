<?php

namespace App\Actors\EventListener;

use App\Actors\Entity\Actor;
use App\Actors\Repository\ActorRepository;
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

class SaveActorProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const SAVE_ACTOR = 'saveActor';

    private $em;
    private $producer;
    private $normalizer;
    private $logger;
    private $movieRepository;
    private $actorRepository;
    private $searchService;

    public function __construct(EntityManagerInterface $em, ProducerInterface $producer, TmdbNormalizerService $normalizer, LoggerInterface $logger, MovieRepository $movieRepository, ActorRepository $actorRepository, TmdbSearchService $searchService)
    {
        $this->em = $em;
        $this->producer = $producer;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
        $this->movieRepository = $movieRepository;
        $this->actorRepository = $actorRepository;
        $this->searchService = $searchService;
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        $this->logger->info('SaveActorProcessor start with memory usage: ', [memory_get_usage()]);

        $actorId = $message->getBody();
        $actorId = json_decode($actorId, true);

        if (null !== $actor = $this->actorRepository->findByTmdbId($actorId)) {
            return self::REJECT;
        }

        try {
            $tmdbActor = $this->searchService->findActorById($actorId);
        } catch (TmdbRequestLimitException $requestLimitException) {
            sleep(5);

            return self::REQUEUE;
        } catch (TmdbMovieNotFoundException $notFoundException) {
            return self::REJECT;
        }

        /** @var $actor Actor */
        $actor = $this->normalizer->normalizeActorsToObjects([$tmdbActor])->current();
        $this->em->persist($actor);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
            return self::ACK;
        }

        $this->loadTranslations($actor->getId());

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