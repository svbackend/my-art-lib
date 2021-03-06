<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Guests\Entity\GuestSession;
use App\Guests\Entity\GuestWatchedMovie;
use App\Guests\Repository\WatchedMovieRepository;
use App\Movies\DTO\WatchedMovieDTO;
use App\Movies\Entity\Movie;
use App\Movies\Entity\WatchedMovie;
use App\Movies\EventListener\SimilarMoviesProcessor;
use App\Movies\EventListener\WatchedMovieProcessor;
use App\Movies\Repository\MovieRepository;
use App\Users\Entity\User;
use App\Users\Entity\UserWatchedMovie;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;

// todo event for new added movie and if so - remove this movie from wishlist (if its added there)
class WatchedMovieService
{
    private $em;
    /**
     * @var MovieRepository
     */
    private $repository;

    private $searchService;

    private $producer;

    public function __construct(EntityManagerInterface $entityManager, SearchService $searchService, ProducerInterface $producer)
    {
        $this->em = $entityManager;
        $this->repository = $entityManager->getRepository(Movie::class);
        $this->searchService = $searchService;
        $this->producer = $producer;
    }

    /**
     * @param User            $user
     * @param WatchedMovieDTO $watchedMovieDTO
     * @param string          $locale
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function addUserWatchedMovie(User $user, WatchedMovieDTO $watchedMovieDTO, string $locale): bool
    {
        $movie = $this->repository->findOneByIdOrTmdbId($watchedMovieDTO->getMovieId(), $watchedMovieDTO->getTmdbId());

        if ($movie === null) {
            // Lets try to find it in TMDB library
            $movie = $this->searchService->findByTmdbId($watchedMovieDTO->getTmdbId(), $locale);
        }

        if ($movie === null) {
            return false;
        }

        $newWatchedMovie = new UserWatchedMovie($user, $movie, $watchedMovieDTO->getVote(), $watchedMovieDTO->getWatchedAt());

        if ($movie->getId() === null) {
            $this->saveWatchedMovies([$newWatchedMovie]);

            return true;
        }

        try {
            $this->em->persist($newWatchedMovie);
            $this->em->flush();
            $this->onMovieAdded($newWatchedMovie);
        } catch (UniqueConstraintViolationException $exception) {
            // You can throw new BadRequestHttpException('This movie already in your library of watched movies');
            // But I think its nice to return 202 like operation was successful
            return true;
        }

        return true;
    }

    public function updateUserWatchedMovie(WatchedMovie $watchedMovie, WatchedMovieDTO $watchedMovieDTO)
    {
        $watchedMovie->changeVote($watchedMovieDTO->getVote());
        $watchedMovie->changeWatchedAt($watchedMovieDTO->getWatchedAt());
    }

    /**
     * @param GuestSession    $guestSession
     * @param WatchedMovieDTO $watchedMovieDTO
     * @param string          $locale
     *
     * @throws \Exception
     *
     * @return bool
     */
    public function addGuestWatchedMovie(GuestSession $guestSession, WatchedMovieDTO $watchedMovieDTO, string $locale): bool
    {
        $movie = $this->repository->findOneByIdOrTmdbId($watchedMovieDTO->getMovieId(), $watchedMovieDTO->getTmdbId());

        if ($movie === null) {
            // Lets try to find it in TMDB library
            $movie = $this->searchService->findByTmdbId($watchedMovieDTO->getTmdbId(), $locale);
        }

        if ($movie === null) {
            return false;
        }

        $newWatchedMovie = new GuestWatchedMovie($guestSession, $movie, $watchedMovieDTO->getVote(), $watchedMovieDTO->getWatchedAt());

        if ($movie->getId() === null) {
            $this->saveWatchedMovies([$newWatchedMovie]);

            return true;
        }

        try {
            $this->em->persist($newWatchedMovie);
            $this->em->flush();
            $this->onMovieAdded($newWatchedMovie);
        } catch (UniqueConstraintViolationException $exception) {
            // You can throw new BadRequestHttpException('This movie already in your library of watched movies');
            // But I think its nice to return 202 like operation was successful
            return true;
        }

        return true;
    }

    /**
     * @param GuestSession $guestSession
     * @param User         $user
     *
     * @throws \Exception
     */
    public function mergeWatchedMovies(GuestSession $guestSession, User $user): void
    {
        /** @var $guestWatchedMoviesRepository WatchedMovieRepository */
        $guestWatchedMoviesRepository = $this->em->getRepository(GuestWatchedMovie::class);
        $guestWatchedMovies = $guestWatchedMoviesRepository->findBy([
            'guestSession' => $guestSession->getId(),
        ]);

        if (!reset($guestWatchedMovies)) {
            return;
        }

        $userWatchedMovies = [];
        foreach ($guestWatchedMovies as $guestWatchedMovie) {
            $movie = $guestWatchedMovie->getMovie();
            $vote = $guestWatchedMovie->getVote();
            $watchedAt = $guestWatchedMovie->getWatchedAt();
            $userWatchedMovies[] = new UserWatchedMovie($user, $movie, $vote, $watchedAt);
        }

        $this->saveWatchedMovies($userWatchedMovies);
    }

    /**
     * @param array|UserWatchedMovie[]|GuestWatchedMovie[] $watchedMovies
     */
    private function saveWatchedMovies(array $watchedMovies): void
    {
        $watchedMoviesSerialized = serialize($watchedMovies);
        $this->producer->sendEvent(WatchedMovieProcessor::ADD_WATCHED_MOVIE_TMDB, $watchedMoviesSerialized);
    }

    private function onMovieAdded(WatchedMovie $watchedMovie): void
    {
        $movie = $watchedMovie->getMovie();
        if (\count($movie->getSimilarMovies()) === 0) {
            $this->producer->sendEvent(SimilarMoviesProcessor::LOAD_SIMILAR_MOVIES, json_encode($movie->getId()));
        }
    }
}
