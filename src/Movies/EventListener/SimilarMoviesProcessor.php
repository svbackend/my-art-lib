<?php

namespace App\Movies\EventListener;

use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbNormalizerService;
use App\Movies\Service\TmdbSearchService;
use App\Movies\Service\TmdbSyncService;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
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
        $movieId = $message->getBody();
        $movieId = json_decode($movieId, true);

        $movies = $this->movieRepository->findAllByIdsWithSimilarMovies([$movieId]);

        if (count($movies) === 0) {
            return self::REJECT;
        }

        $allSimilarMoviesTable = [];
        $movie = reset($movies);

        if ($movie['sm_id'] !== null) {
            return self::ACK;
        }

        try {
            $similarMovies = $this->loadSimilarMoviesFromTMDB($movie['m_tmdb.id']);
        } catch (TmdbRequestLimitException $requestLimitException) {
            sleep(5);

            return self::REQUEUE;
        } catch (TmdbMovieNotFoundException $movieNotFoundException) {
            return self::ACK;
        }

        $allSimilarMoviesTable[$movie['m_id']] = array_map(function (array $newSimilarMovie) {
            return $newSimilarMovie['id'];
        }, $similarMovies);

        $this->sync->syncMovies($similarMovies);

        $this->addSimilarMovies($allSimilarMoviesTable);

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
            $similarMoviesResponse['total_pages'] = $similarMoviesResponse['total_pages'] > 5 ? 5 : $similarMoviesResponse['total_pages'];
            for ($i = 2; $i <= $similarMoviesResponse['total_pages']; ++$i) {
                $moviesOnPage = $this->searchService->findSimilarMoviesById($tmdbId, $i);
                $movies = array_merge($movies, $moviesOnPage['results']);
            }
        }

        return $movies;
    }

    private function addSimilarMovies(array $allSimilarMoviesTable)
    {
        $message = new Message(json_encode($allSimilarMoviesTable));
        $message->setPriority(MessagePriority::VERY_LOW);
        $this->producer->sendEvent(AddSimilarMoviesProcessor::ADD_SIMILAR_MOVIES, $message);
    }

    public static function getSubscribedTopics()
    {
        return [self::LOAD_SIMILAR_MOVIES];
    }
}
