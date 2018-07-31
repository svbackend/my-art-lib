<?php

namespace App\Movies\EventListener;

use App\Movies\Entity\Movie;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbNormalizerService;
use App\Movies\Service\TmdbSearchService;
use App\Movies\Service\TmdbSyncService;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class SimilarMoviesProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const LOAD_SIMILAR_MOVIES = 'LoadSimilarMoviesFromTMDB';

    private $searchService;
    private $movieRepository;
    private $normalizer;
    private $sync;
    private $producer;

    public function __construct(MovieRepository $movieRepository, TmdbSearchService $searchService, TmdbNormalizerService $normalizer, TmdbSyncService $sync, ProducerInterface $producer)
    {
        $this->movieRepository = $movieRepository;
        $this->searchService = $searchService;
        $this->normalizer = $normalizer;
        $this->sync = $sync;
        $this->producer = $producer;
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        echo "\r\n================BEFORE==================\r\n";
        echo memory_get_usage();
        echo "\r\n========================================\r\n";
        $moviesIds = $message->getBody();
        $moviesIds = unserialize($moviesIds);

        $movies = $this->movieRepository->findAllByIdsWithSimilarMovies($moviesIds);
        $allSimilarMoviesToSave = [];
        $allSimilarMoviesTable = [];
        $totalSuccessfullyProcessedMovies = 0;
        $requeueIds = [];

        foreach ($movies as $movie) {
            echo "\r\n";
            echo var_export($movie);
            echo "\r\n";
            if ($movie['sm_id'] !== null) {
                ++$totalSuccessfullyProcessedMovies;
                continue;
            }

            try {
                $similarMovies = $this->loadSimilarMoviesFromTMDB($movie['tmdb']['id']);
            } catch (TmdbRequestLimitException $requestLimitException) {
                $requeueIds[] = $movie['id'];
                continue;
            } catch (TmdbMovieNotFoundException $movieNotFoundException) {
                ++$totalSuccessfullyProcessedMovies;
                continue;
            } catch (\Exception $exception) {
                ++$totalSuccessfullyProcessedMovies;
                continue;
            }

            try {
                $similarMovies = $this->normalizer->normalizeMoviesToObjects($similarMovies);
            } catch (\Exception $exception) {
                echo $exception->getMessage();
                ++$totalSuccessfullyProcessedMovies;
                continue;
            }

            if (!count($similarMovies)) {
                ++$totalSuccessfullyProcessedMovies;
                continue;
            }

            $allSimilarMoviesTable[$movie['id']] = array_map(function (Movie $newSimilarMovie) {
                return $newSimilarMovie->getTmdb()->getId(); // bcuz $newSimilarMovie->getId() === null, currently
            }, $similarMovies);
            $allSimilarMoviesToSave = array_merge($allSimilarMoviesToSave, $similarMovies);
            ++$totalSuccessfullyProcessedMovies;
        }

        $allSimilarMoviesToSave = $this->getUniqueSimilarMoviesToSave($allSimilarMoviesToSave);
        $this->sync->syncMovies($allSimilarMoviesToSave, false, $allSimilarMoviesTable);
        $this->producer->sendEvent(AddSimilarMoviesProcessor::ADD_SIMILAR_MOVIES, json_encode($allSimilarMoviesTable));

        if (count($movies) === $totalSuccessfullyProcessedMovies) {


            echo "\r\n=============AFTER (BEFORE CLEANUP)===========\r\n";
            echo memory_get_usage();
            $message = $session = $moviesIds = $movies = $allSimilarMoviesToSave = $allSimilarMoviesTable = $totalSuccessfullyProcessedMovies = $requeueIds = $movie = null;
            unset($message, $session, $moviesIds, $movies, $allSimilarMoviesToSave, $allSimilarMoviesTable, $totalSuccessfullyProcessedMovies, $requeueIds, $movie);
            gc_collect_cycles();

            echo "\r\n=============AFTER (AFTER CLEANUP)===========\r\n";
            echo memory_get_usage();
            echo "\r\n=======================================\r\n";

            return self::ACK;
        }

        $this->producer->sendEvent(self::LOAD_SIMILAR_MOVIES, serialize($requeueIds));

        $message = $session = $moviesIds = $movies = $allSimilarMoviesToSave = $allSimilarMoviesTable = $totalSuccessfullyProcessedMovies = $requeueIds = $movie = null;
        unset($message, $session, $moviesIds, $movies, $allSimilarMoviesToSave, $allSimilarMoviesTable, $totalSuccessfullyProcessedMovies, $requeueIds, $movie);
        gc_collect_cycles();

        return self::ACK;
    }

    /**
     * @param int $tmdbId
     *
     * @throws TmdbRequestLimitException
     * @throws \App\Movies\Exception\TmdbMovieNotFoundException
     *
     * @return array
     */
    private function loadSimilarMoviesFromTMDB(int $tmdbId): array
    {
        $similarMoviesResponse = $this->searchService->findSimilarMoviesById($tmdbId);
        $movies = $similarMoviesResponse['results'] ?? [];

        // If we have a lot of movies then load it all
        if (isset($similarMoviesResponse['total_pages']) && $similarMoviesResponse['total_pages'] > 1) {
            // $i = 2 because $movies currently already has movies from page 1
            for ($i = 2; $i <= $similarMoviesResponse['total_pages']; ++$i) {
                $moviesOnPage = $this->searchService->findSimilarMoviesById($tmdbId, $i);
                $movies = array_merge($movies, $moviesOnPage['results']);
            }
        }

        return $movies;
    }

    /**
     * @param array|Movie[] $movies
     *
     * @return array|Movie[]
     */
    private function getUniqueSimilarMoviesToSave(array $movies)
    {
        $uniqueMovies = [];

        foreach ($movies as $movie) {
            $uniqueMovies[$movie->getTmdb()->getId()] = $movie;
        }

        return $uniqueMovies;
    }

    public static function getSubscribedTopics()
    {
        return [self::LOAD_SIMILAR_MOVIES];
    }
}
