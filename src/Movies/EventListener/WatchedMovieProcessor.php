<?php

namespace App\Movies\EventListener;

use App\Genres\Entity\Genre;
use App\Guests\Entity\GuestSession;
use App\Guests\Entity\GuestWatchedMovie;
use App\Movies\Entity\Movie;
use App\Movies\Entity\WatchedMovie;
use App\Users\Entity\User;
use App\Users\Entity\UserWatchedMovie;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

// Looks like here some logic problem
// todo construct entities by params from message here instead of create each of them with correct associations
// TODO IMPORTANT
class WatchedMovieProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const ADD_WATCHED_MOVIE_TMDB = 'addWatchedMovieTMDB';

    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * This method called when user or guest trying to add movie to own list of watched movies but we do not have this movie in our db yet.
     *
     * @param PsrMessage $message
     * @param PsrContext $session
     *
     * @throws \Doctrine\ORM\ORMException|\Exception
     *
     * @return object|string
     */
    public function process(PsrMessage $message, PsrContext $session)
    {
        $watchedMovies = $message->getBody();
        $watchedMovies = unserialize($watchedMovies);
        $validClasses = [UserWatchedMovie::class, GuestWatchedMovie::class];

        if ($this->em->isOpen() === false) {
            throw new \ErrorException('em is closed');
        }

        /** @var $watchedMovies UserWatchedMovie[]|GuestWatchedMovie[] */
        foreach ($watchedMovies as $watchedMovie) {
            if (in_array(get_class($watchedMovie), $validClasses, true) === false) {
                $this->logger->error('Unexpected behavior: $watchedMovie not in range of valid classes', [
                    'actualClass' => get_class($watchedMovie),
                ]);
                continue;
            }

            $movie = $watchedMovie->getMovie(); // Not saved movie
            $movie = $this->refreshGenresAssociations($movie);
            $newWatchedMovie = $this->recreateWatchedMovie($watchedMovie, $movie);

            $this->em->persist($movie);
            $this->em->persist($newWatchedMovie);
        }

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $exception) {
            $this->logger->error('UniqueConstraintViolationException when we are trying to save watched movie', [
                'message' => $exception->getMessage(),
            ]);
        } finally {
            $this->em->clear();
        }

        return self::ACK;
    }

    /**
     * @param WatchedMovie $watchedMovie
     * @param Movie        $movie
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return WatchedMovie
     */
    private function recreateWatchedMovie(WatchedMovie $watchedMovie, Movie $movie): WatchedMovie
    {
        // Recreate *WatchedMovie entity with new managed by doctrine associations
        if ($watchedMovie instanceof UserWatchedMovie) {
            $newWatchedMovie = $this->recreateUserWatchedMovie($watchedMovie, $movie);
        }

        if ($watchedMovie instanceof GuestWatchedMovie) {
            $newWatchedMovie = $this->recreateGuestWatchedMovie($watchedMovie, $movie);
        }

        if (!isset($newWatchedMovie)) {
            throw new \LogicException('Watched movie not a valid child class of WatchedMovie');
        }

        return $newWatchedMovie;
    }

    /**
     * @param UserWatchedMovie $userWatchedMovie
     * @param Movie            $movie
     *
     * @throws \Doctrine\ORM\ORMException|\Exception
     *
     * @return UserWatchedMovie
     */
    private function recreateUserWatchedMovie(UserWatchedMovie $userWatchedMovie, Movie $movie): UserWatchedMovie
    {
        /** @var $userReference User */
        $userReference = $this->em->getReference(User::class, $userWatchedMovie->getUser()->getId());

        return new UserWatchedMovie($userReference, $movie, $userWatchedMovie->getVote(), $userWatchedMovie->getWatchedAt());
    }

    /**
     * @param GuestWatchedMovie $guestWatchedMovie
     * @param Movie             $movie
     *
     * @throws \Doctrine\ORM\ORMException|\Exception
     *
     * @return GuestWatchedMovie
     */
    private function recreateGuestWatchedMovie(GuestWatchedMovie $guestWatchedMovie, Movie $movie): GuestWatchedMovie
    {
        /** @var $guestSessionReference GuestSession */
        $guestSessionReference = $this->em->getReference(GuestSession::class, $guestWatchedMovie->getGuestSession()->getId());

        return new GuestWatchedMovie($guestSessionReference, $movie, $guestWatchedMovie->getVote(), $guestWatchedMovie->getWatchedAt());
    }

    /**
     * @param Movie $movie
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return Movie
     */
    private function refreshGenresAssociations(Movie $movie): Movie
    {
        $genres = $movie->getGenres();
        $movie->removeAllGenres(); // Because doctrine doesn't know about these genres due unserialization

        foreach ($genres as $genre) {
            /** @var $genreReference Genre */
            $genreReference = $this->em->getReference(Genre::class, $genre->getId()); // so we need to re-add 'em
            $movie->addGenre($genreReference);
        }

        return $movie;
    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_WATCHED_MOVIE_TMDB];
    }
}
