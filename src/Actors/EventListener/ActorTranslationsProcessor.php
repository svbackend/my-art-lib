<?php

namespace App\Actors\EventListener;

use App\Actors\Entity\Actor;
use App\Actors\Entity\ActorTranslations;
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
    private $searchService;
    private $locales = [];

    public function __construct(EntityManagerInterface $em, ProducerInterface $producer, TmdbNormalizerService $normalizer, LoggerInterface $logger, MovieRepository $movieRepository, TmdbSearchService $searchService, LocaleService $localeService)
    {
        $this->em = $em;
        $this->producer = $producer;
        $this->normalizer = $normalizer;
        $this->logger = $logger;
        $this->movieRepository = $movieRepository;
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
        $translations = $this->searchService->findActorTranslationsById($actor->getTmdb()->getId());

        $locales = $this->locales;

        $addTranslation = function(array $trans) use ($actor, $locales) {
            if (in_array($trans['iso_639_1'], $locales) === false) { return; }
            $data = $trans['data'];
            $translation = new ActorTranslations($actor, $trans['iso_639_1'], $actor->getOriginalName());
            $translation->setBiography($data['biography'] ?? '');
            $actor->addTranslation($translation);
        };

        $updateTranslation = function(array $trans, ActorTranslations $oldTranslation) use ($actor, $locales) {
            if (in_array($trans['iso_639_1'], $locales) === false) { return; }
            $data = $trans['data'];
            $oldTranslation->setBiography($data['biography'] ?? $oldTranslation->getBiography());
        };

        $actor->updateTranslations($translations, $addTranslation, $updateTranslation);

        $this->em->persist($actor);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
            //return self::ACK;
        }
        $this->em->clear();
        return self::ACK;
    }

    private function loadTranslations(int $actorId)
    {
        $message = new Message(json_encode($actorId));
        $this->producer->sendEvent(AddSimilarMoviesProcessor::ADD_SIMILAR_MOVIES, $message);
    }

    public static function getSubscribedTopics()
    {
        return [self::LOAD_TRANSLATIONS];
    }
}
