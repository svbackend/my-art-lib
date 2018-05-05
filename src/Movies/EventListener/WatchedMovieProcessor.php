<?php

namespace App\Movies\EventListener;

use App\Movies\Entity\Movie;
use App\Movies\Entity\WatchedMovie;
use App\Guests\Entity\GuestSession;
use App\Guests\Entity\GuestWatchedMovie;
use App\Users\Entity\UserWatchedMovie;
use App\Users\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Enqueue\Client\TopicSubscriberInterface;
use Psr\Log\LoggerInterface;

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

    public function process(PsrMessage $message, PsrContext $session)
    {
        $watchedMovies = $message->getBody();
        $watchedMovies = unserialize($watchedMovies);

        /**
         * @var $watchedMovies UserWatchedMovie[]
         */
        foreach ($watchedMovies as $watchedMovie) {
            $movie = $watchedMovie->getMovie();
            $this->persistMovie($watchedMovie, $movie);

            if ($watchedMovie instanceof UserWatchedMovie) {
                $this->persistUserWatchedMovie($watchedMovie);
            }

            if ($watchedMovie instanceof GuestWatchedMovie) {
                $this->persistGuestWatchedMovie($watchedMovie);
            }
        }

        $this->em->flush();

        return self::ACK;
    }

    private function persistUserWatchedMovie(UserWatchedMovie $userWatchedMovie)
    {
        // Because after unserialization doctrine think that User is new and trying to save it
        $user = $this->em->find(User::class, $userWatchedMovie->getUser()->getId());
        /** @var $user User */
        $userWatchedMovie->updateUser($user);

        $this->em->persist($userWatchedMovie);
    }

    private function persistGuestWatchedMovie(GuestWatchedMovie $guestWatchedMovie)
    {
        $guestSession = $this->em->find(GuestSession::class, $guestWatchedMovie->getGuestSession()->getId());
        /** @var $guestSession GuestSession */
        $guestWatchedMovie->setGuestSession($guestSession);

        $this->em->persist($guestWatchedMovie);
    }

    private function persistMovie(WatchedMovie $watchedMovie, Movie $movie)
    {
        // If movie not saved yet
        if ($movie->getId() === null) {
            $this->em->persist($movie);
        } else {
            // But if movie already saved (almost impossible) then just load it from db
            $movie = $this->em->find(Movie::class, $movie->getId());
            /** @var $movie Movie */
            $watchedMovie->updateMovie($movie);
        }
    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_WATCHED_MOVIE_TMDB];
    }
}