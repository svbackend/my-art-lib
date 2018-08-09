<?php

namespace App\Actors\EventListener;

use App\Actors\Entity\Actor;
use App\Actors\Entity\ActorTranslations;
use App\Actors\Repository\ActorRepository;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Service\TmdbSearchService;
use App\Service\LocaleService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

class ActorTranslationsProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const LOAD_TRANSLATIONS = 'loadActorTranslations';

    private $em;
    private $logger;
    private $actorRepository;
    private $searchService;
    private $locales = [];

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, ActorRepository $actorRepository, TmdbSearchService $searchService, LocaleService $localeService)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->actorRepository = $actorRepository;
        $this->searchService = $searchService;
        $this->locales = $localeService->getLocales();
    }

    /**
     * @param PsrMessage $message
     * @param PsrContext $session
     * @return string
     * @throws \Doctrine\ORM\ORMException
     * @throws \ErrorException
     */
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

        $this->mergeTranslations($actor, $translations);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
            // its ok
        }
        $this->em->clear();
        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::LOAD_TRANSLATIONS];
    }

    /**
     * @param Actor $actor
     * @param array $translations
     * @return void
     * @throws \Doctrine\ORM\ORMException
     * @throws \ErrorException
     */
    private function mergeTranslations(Actor $actor, array $translations): void
    {
        /** @var $actorRef Actor */
        $actorRef = $this->em->getReference(Actor::class, $actor->getId());

        foreach ($translations as $translation) {
            $translationLocale = $translation['iso_639_1'];
            if (in_array($translationLocale, $this->locales) === false) {
                continue;
            }
            if ($actor->getTranslation($translationLocale, false) !== null) {
                continue;
            }

            $data = $translation['data'];

            $translationObject = new ActorTranslations($actorRef, $translationLocale, $actor->getOriginalName());
            $translationObject->setBiography($data['biography'] ?? '');
            $actor->addTranslation($translationObject);
            $this->em->persist($translationObject);
        }
    }
}
