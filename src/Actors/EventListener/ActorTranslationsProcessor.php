<?php

namespace App\Actors\EventListener;

use App\Actors\Entity\Actor;
use App\Actors\Entity\ActorTranslations;
use App\Actors\Repository\ActorRepository;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbNormalizerService;
use App\Movies\Service\TmdbSearchService;
use App\Service\LocaleService;
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

class ActorTranslationsProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const LOAD_TRANSLATIONS = 'loadActorTranslations';

    private $em;
    private $producer;
    private $normalizer;
    private $logger;
    private $movieRepository;
    private $actorRepository;
    private $searchService;
    private $locales = [];

    public function __construct(EntityManagerInterface $em, ProducerInterface $producer, TmdbNormalizerService $normalizer, LoggerInterface $logger, MovieRepository $movieRepository, ActorRepository $actorRepository, TmdbSearchService $searchService, LocaleService $localeService)
    {
        $this->em = $em;
        $this->producer = $producer;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
        $this->movieRepository = $movieRepository;
        $this->actorRepository = $actorRepository;
        $this->searchService = $searchService;
        $this->locales = $localeService->getLocales();
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        $this->logger->info('ActorTranslationsProcessor start with memory usage: ', [memory_get_usage()]);

        $actorId = $message->getBody();
        $actorId = json_decode($actorId, true);

        if (null === $actor = $this->actorRepository->find($actorId)) {
            return self::REJECT;
        }

        /** @var $actor Actor */
        try {
            $translations = $this->searchService->findActorTranslationsById($actor->getTmdb()->getId());
            $translations = $translations['translations'];
        } catch (TmdbRequestLimitException $requestLimitException) {
            sleep(5);

            return self::REQUEUE;
        } catch (TmdbMovieNotFoundException $notFoundException) {
            return self::REJECT;
        }
        $locales = $this->locales;
        /** @var $actorRef Actor */
        $actorRef = $this->em->getReference(Actor::class, $actor->getId());

        $addTranslation = function(array $trans) use ($actor, $locales, $actorRef) {
            if (in_array($trans['iso_639_1'], $locales) === false) { return; }
            $data = $trans['data'];
            $translation = new ActorTranslations($actorRef, $trans['iso_639_1'], $actor->getOriginalName());
            $translation->setBiography($data['biography'] ?? '');
            $actor->addTranslation($translation);
        };

        $updateTranslation = function(array $trans, ActorTranslations $oldTranslation) use ($actor, $locales) {
            if (in_array($trans['iso_639_1'], $locales) === false) { return; }
            $data = $trans['data'];
            $oldTranslation->setBiography($data['biography'] ?? $oldTranslation->getBiography());
        };

        $actor->updateTranslations($translations, $addTranslation, $updateTranslation, 'iso_639_1');

        $this->em->persist($actor);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
            //return self::ACK;
        }
        $this->em->clear();
        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::LOAD_TRANSLATIONS];
    }
}
