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
        $moviesIds = $message->getBody();
        $moviesIds = unserialize($moviesIds);

        $movies = $this->movieRepository->findAllByIdsWithSimilarMovies($moviesIds);
        $allSimilarMoviesTable = [];
        $totalSuccessfullyProcessedMovies = 0;
        $requeueIds = [];

        foreach ($movies as $movie) {
            if ($movie['sm_id'] !== null) {
                ++$totalSuccessfullyProcessedMovies;
                continue;
            }

            try {
                $similarMovies = $this->loadSimilarMoviesFromTMDB($movie['m_tmdb.id']);
            } catch (TmdbRequestLimitException $requestLimitException) {
                $requeueIds[] = $movie['m_id'];
                sleep(5);
                continue;
            } catch (TmdbMovieNotFoundException $movieNotFoundException) {
                ++$totalSuccessfullyProcessedMovies;
                continue;
            } catch (\Exception $exception) {
                ++$totalSuccessfullyProcessedMovies;
                continue;
            }

            $allSimilarMoviesTable[$movie['m_id']] = array_map(function (array $newSimilarMovie) {
                return $newSimilarMovie['id'];
            }, $similarMovies);

            try {
                $similarMovies = $this->normalizer->normalizeMoviesToObjects($similarMovies);
            } catch (\Exception $exception) {
                echo $exception->getMessage();
                ++$totalSuccessfullyProcessedMovies;
                continue;
            }

            if (!$similarMovies->current()) {
                ++$totalSuccessfullyProcessedMovies;
                continue;
            }

            $this->sync->syncMovies($similarMovies);
            ++$totalSuccessfullyProcessedMovies;
        }

        $this->producer->sendEvent(AddSimilarMoviesProcessor::ADD_SIMILAR_MOVIES, json_encode($allSimilarMoviesTable));

        if (count($requeueIds)) {
            $this->producer->sendEvent(self::LOAD_SIMILAR_MOVIES, serialize($requeueIds));
        }

        $message = $session = $moviesIds = $movies = $allSimilarMoviesTable = $totalSuccessfullyProcessedMovies = $requeueIds = $movie = null;
        unset($message, $session, $moviesIds, $movies, $allSimilarMoviesTable, $totalSuccessfullyProcessedMovies, $requeueIds, $movie);

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

    public static function getSubscribedTopics()
    {
        return [self::LOAD_SIMILAR_MOVIES];
    }
}
