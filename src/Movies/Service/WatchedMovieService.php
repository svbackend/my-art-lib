<?php

declare(strict_types=1);

namespace App\Movies\Service;

use App\Guests\Entity\GuestSession;
use App\Guests\Entity\GuestWatchedMovie;
use App\Guests\Repository\WatchedMovieRepository;
use App\Movies\DTO\WatchedMovieDTO;
use App\Movies\Entity\Movie;
use App\Movies\EventListener\WatchedMovieProcessor;
use App\Movies\Repository\MovieRepository;
use App\Users\Entity\User;
use App\Users\Entity\UserWatchedMovie;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;

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

        if (null === $movie) {
            // Lets try to find it in TMDB library
            $movie = $this->searchService->findByTmdbId($watchedMovieDTO->getTmdbId(), $locale);
        }

        if (null === $movie) {
            return false;
        }

        $newWatchedMovie = new UserWatchedMovie($user, $movie, $watchedMovieDTO->getVote(), $watchedMovieDTO->getWatchedAt());

        if (null === $movie->getId()) {
            $this->saveWatchedMovies([$newWatchedMovie]);

            return true;
        }

        try {
            $this->em->persist($newWatchedMovie);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $exception) {
            // You can throw new BadRequestHttpException('This movie already in your library of watched movies');
            // But I think its nice to return 202 like operation was successful
            return true;
        }

        return true;
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

        if (null === $movie) {
            // Lets try to find it in TMDB library
            $movie = $this->searchService->findByTmdbId($watchedMovieDTO->getTmdbId(), $locale);
        }

        if (null === $movie) {
            return false;
        }

        $newWatchedMovie = new GuestWatchedMovie($guestSession, $movie, $watchedMovieDTO->getVote(), $watchedMovieDTO->getWatchedAt());

        if (null === $movie->getId()) {
            $this->saveWatchedMovies([$newWatchedMovie]);

            return true;
        }

        try {
            $this->em->persist($newWatchedMovie);
            $this->em->flush();
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
}
