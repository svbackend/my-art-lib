<?php

namespace App\Movies\EventListener;

use App\Movies\DTO\MovieTranslationDTO;
use App\Movies\Entity\MovieTranslations;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbSearchService;
use App\Service\LocaleService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Enqueue\Client\TopicSubscriberInterface;

class MovieTranslationsProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const LOAD_TRANSLATIONS = 'LoadMoviesTranslationsFromTMDB';

    private $em;
    private $searchService;
    private $movieRepository;
    private $locales;
    private $localesCount;

    public function __construct(EntityManagerInterface $em, MovieRepository $movieRepository, TmdbSearchService $searchService, LocaleService $localeService)
    {
        $this->em = $em;
        $this->movieRepository = $movieRepository;
        $this->searchService = $searchService;
        $this->locales = $localeService->getLocales();
        $this->localesCount = count($this->locales);
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        $moviesIds = $message->getBody();
        $moviesIds = unserialize($moviesIds);

        if ($this->em->isOpen() === false) {
            $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
        }

        $movies = $this->movieRepository->findAllByIds($moviesIds);

        $totalCounter = count($movies);
        $successfullySavedMoviesCounter = 0;
        foreach ($movies as $movie) {
            // {begin} If all translations already saved
            $existingTranslations = [];
            foreach ($this->locales as $locale) {
                if (null !== $movie->getTranslation($locale, false)) {
                    $existingTranslations[] = $locale;
                }
            }

            if (!count(array_diff($this->locales, $existingTranslations))) {
                $successfullySavedMoviesCounter++;
                continue;
            }
            // {end} If all translations already saved

            try {
                $translationsDTOs = $this->loadTranslationsFromTMDB($movie->getTmdb()->getId());
            } catch (TmdbRequestLimitException $requestLimitException) {
                continue;
            } catch (TmdbMovieNotFoundException $movieNotFoundException) {
                // if movie not found let's think that it's successfully processed
                $successfullySavedMoviesCounter++;
                continue;
            }

            foreach ($translationsDTOs as $translationDTO) {
                if (null !== $movie->getTranslation($translationDTO->getLocale(), false)) {
                    // If we already have translation for this locale just go to next iteration
                    continue;
                }

                $movieTranslation = new MovieTranslations($movie, $translationDTO);
                $movie->addTranslation($movieTranslation);

                $this->em->persist($movieTranslation);
            }

            $successfullySavedMoviesCounter++;
        }

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
            // do nothing, it's ok
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        if ($successfullySavedMoviesCounter === $totalCounter) {
            return self::ACK;
        } else {
            return self::REQUEUE;
        }
    }

    /**
     * @param int $tmdbId
     * @return array|MovieTranslationDTO[]
     * @throws TmdbRequestLimitException
     * @throws \App\Movies\Exception\TmdbMovieNotFoundException
     */
    private function loadTranslationsFromTMDB(int $tmdbId): array
    {
        $translationsResponse = $this->searchService->findMovieTranslationsById($tmdbId);
        $translations = $translationsResponse['translations'];
        $newTranslations = [];

        foreach ($translations as $translation) {
            if (in_array($translation['iso_639_1'], $this->locales) === false) { continue; }
            $data = $translation['data'];

            $newTranslations[] = new MovieTranslationDTO($translation['iso_639_1'], $data['title'], $data['overview'], null);
        }

        return $newTranslations;
    }

    public static function getSubscribedTopics()
    {
        return [self::LOAD_TRANSLATIONS];
    }
}