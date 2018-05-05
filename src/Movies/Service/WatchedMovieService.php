<?php
declare(strict_types=1);

namespace App\Movies\Service;

use App\Movies\DTO\WatchedMovieDTO;
use App\Movies\Entity\Movie;
use App\Guests\Entity\GuestSession;
use App\Guests\Entity\GuestWatchedMovie;
use App\Users\Entity\UserWatchedMovie;
use App\Movies\EventListener\WatchedMovieProcessor;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\SearchService;
use App\Users\Entity\User;
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
     * @param User $user
     * @param WatchedMovieDTO $watchedMovieDTO
     * @param string $locale
     * @return bool
     * @throws \Exception
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
            $watchedMovieSerialized = serialize([$newWatchedMovie]);
            $this->producer->sendEvent(WatchedMovieProcessor::ADD_WATCHED_MOVIE_TMDB, $watchedMovieSerialized);
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
     * @param WatchedMovieDTO $watchedMovieDTO
     * @param string $locale
     * @return bool
     * @throws \Exception
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
            $watchedMovieSerialized = serialize([$newWatchedMovie]);
            $this->producer->sendEvent(WatchedMovieProcessor::ADD_WATCHED_MOVIE_TMDB, $watchedMovieSerialized);
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
}