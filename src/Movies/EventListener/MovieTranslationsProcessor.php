<?php

namespace App\Movies\EventListener;

use App\Movies\DTO\MovieTranslationDTO;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTranslations;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbSearchService;
use App\Service\LocaleService;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class MovieTranslationsProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const LOAD_TRANSLATIONS = 'LoadMoviesTranslationsFromTMDB';

    private $em;
    private $searchService;
    private $movieRepository;
    private $locales;
    private $localesCount;
    private $producer;

    public function __construct(EntityManagerInterface $em, ProducerInterface $producer, MovieRepository $movieRepository, TmdbSearchService $searchService, LocaleService $localeService)
    {
        $this->em = $em;
        $this->movieRepository = $movieRepository;
        $this->searchService = $searchService;
        $this->locales = $localeService->getLocales();
        $this->localesCount = count($this->locales);
        $this->producer = $producer;
    }

    /**
     * @param PsrMessage $message
     * @param PsrContext $session
     * @return object|string
     * @throws \Doctrine\ORM\ORMException
     * @throws \ErrorException
     */
    public function process(PsrMessage $message, PsrContext $session)
    {
        $movieId = $message->getBody();
        $movieId = json_decode($movieId, true);

        if ($this->em->isOpen() === false) {
            throw new \ErrorException('em is closed');
        }

        $movie = $this->movieRepository->find($movieId);

        if ($this->isAllTranslationsSaved($movie) === true) {
            return self::ACK;
        }

        try {
            $translationsDTOs = $this->loadTranslationsFromTMDB($movie->getTmdb()->getId());
        } catch (TmdbRequestLimitException $requestLimitException) {
            sleep(5);
            return self::REQUEUE;
        } catch (TmdbMovieNotFoundException $notFoundException) {
            return self::REJECT;
        }

        $this->addTranslations($translationsDTOs, $movie);

        $this->em->flush();
        $this->em->clear();

        $message = $session = $movie = $movieId = null;
        unset($message, $session, $movie, $movieId);

        return self::ACK;
    }

    /**
     * @param int $tmdbId
     * @return \Iterator
     * @throws TmdbMovieNotFoundException
     * @throws TmdbRequestLimitException
     */
    private function loadTranslationsFromTMDB(int $tmdbId): \Iterator
    {
        $translationsResponse = $this->searchService->findMovieTranslationsById($tmdbId);
        $translations = $translationsResponse['translations'];

        foreach ($translations as $translation) {
            if (in_array($translation['iso_639_1'], $this->locales, true) === false) {
                continue;
            }
            $data = $translation['data'];

            yield new MovieTranslationDTO($translation['iso_639_1'], $data['title'], $data['overview'], null);
        }
    }

    /**
     * @param \Iterator $moviesTranslationsDTOs
     * @param Movie $movie
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \ErrorException
     */
    private function addTranslations(\Iterator $moviesTranslationsDTOs, Movie $movie): void
    {
        /** @var $movieReference Movie */
        $movieReference = $this->em->getReference(Movie::class, $movie->getId());

        foreach ($moviesTranslationsDTOs as $translationDTO) {
            if ($movie->getTranslation($translationDTO->getLocale(), false) !== null) {
                // If we already have translation for this locale just go to next iteration
                continue;
            }

            $movieTranslation = new MovieTranslations($movieReference, $translationDTO);
            $movie->addTranslation($movieTranslation);

            $this->em->persist($movieTranslation);
        }
    }

    /**
     * @param Movie $movie
     *
     * @throws \ErrorException
     *
     * @return bool
     */
    private function isAllTranslationsSaved(Movie $movie): bool
    {
        $existingTranslations = [];
        foreach ($this->locales as $locale) {
            if ($movie->getTranslation($locale, false) !== null) {
                $existingTranslations[] = $locale;
            }
        }

        return count(array_diff($this->locales, $existingTranslations)) <= 2;
    }

    public static function getSubscribedTopics()
    {
        return [self::LOAD_TRANSLATIONS];
    }
}
