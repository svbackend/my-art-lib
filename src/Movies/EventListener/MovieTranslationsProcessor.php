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
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class MovieTranslationsProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const LOAD_TRANSLATIONS = 'LoadMoviesTranslationsFromTMDB';

    /** @var EntityManager */
    private $em;
    private $searchService;
    private $movieRepository;
    private $locales;
    private $localesCount;

    public function __construct(EntityManagerInterface $em, MovieRepository $movieRepository, TmdbSearchService $searchService, LocaleService $localeService)
    {
        if ($em instanceof EntityManager === false) {
            throw new \InvalidArgumentException(
                sprintf(
                    'MovieTranslationsProcessor expects %s as %s realization',
                    EntityManager::class,
                    EntityManagerInterface::class
                )
            );
        }

        $this->em = $em;
        $this->movieRepository = $movieRepository;
        $this->searchService = $searchService;
        $this->locales = $localeService->getLocales();
        $this->localesCount = count($this->locales);
    }

    /**
     * @param PsrMessage $message
     * @param PsrContext $session
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \ErrorException
     *
     * @return string
     */
    public function process(PsrMessage $message, PsrContext $session)
    {
        echo "MovieTranslationsProcessor processing...\r\n";
        $moviesIds = $message->getBody();
        $moviesIds = unserialize($moviesIds);

        echo "Loading translations for ids:\r\n";
        echo var_export($moviesIds);

        if ($this->em->isOpen() === false) {
            $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
        }

        $movies = $this->movieRepository->findAllByIds($moviesIds);

        $totalCounter = count($movies);
        $successfullySavedMoviesCounter = 0;

        if ($totalCounter === 0) {
            echo "Looks like movies not found...\r\n";
        }

        foreach ($movies as $movie) {
            echo "Loading translations for {$movie->getOriginalTitle()}";
            echo "\r\n";
            if ($this->isAllTranslationsSaved($movie) === true) {
                echo "{$movie->getOriginalTitle()} already had translations\r\n";
                ++$successfullySavedMoviesCounter;
                continue;
            }

            try {
                $translationsDTOs = $this->loadTranslationsFromTMDB($movie->getTmdb()->getId());
            } catch (TmdbRequestLimitException $requestLimitException) {
                sleep(5);
                continue;
            } catch (TmdbMovieNotFoundException $movieNotFoundException) {
                // if movie not found let's think that it's successfully processed
                ++$successfullySavedMoviesCounter;
                continue;
            } catch (\Exception $exception) {
                echo "tmdb error...\r\n" . $exception->getMessage() . "\r\n";
                continue;
            }

            $this->addTranslations($translationsDTOs, $movie);
            ++$successfullySavedMoviesCounter;
        }

        try {
            echo "-----------Flushed {$successfullySavedMoviesCounter} translations.------------------\r\n";
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
            // do nothing, it's ok
            echo $uniqueConstraintViolationException->getMessage();
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        if ($successfullySavedMoviesCounter === $totalCounter) {
            return self::ACK;
        }

        echo "Requeue. Loaded only {$successfullySavedMoviesCounter} of {$totalCounter} movies\r\n";
        return self::REQUEUE;
    }

    /**
     * @param int $tmdbId
     *
     * @throws TmdbRequestLimitException
     * @throws \App\Movies\Exception\TmdbMovieNotFoundException
     *
     * @return array|MovieTranslationDTO[]
     */
    private function loadTranslationsFromTMDB(int $tmdbId): array
    {
        $translationsResponse = $this->searchService->findMovieTranslationsById($tmdbId);
        $translations = $translationsResponse['translations'];
        $newTranslations = [];

        foreach ($translations as $translation) {
            if (in_array($translation['iso_639_1'], $this->locales, true) === false) {
                continue;
            }
            $data = $translation['data'];

            $newTranslations[] = new MovieTranslationDTO($translation['iso_639_1'], $data['title'], $data['overview'], null);
        }

        return $newTranslations;
    }

    /**
     * @param array $moviesTranslationsDTOs
     * @param Movie $movie
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \ErrorException
     */
    private function addTranslations(array $moviesTranslationsDTOs, Movie $movie): void
    {
        foreach ($moviesTranslationsDTOs as $translationDTO) {
            if ($movie->getTranslation($translationDTO->getLocale(), false) !== null) {
                // If we already have translation for this locale just go to next iteration
                continue;
            }

            $movieTranslation = new MovieTranslations($movie, $translationDTO);
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
