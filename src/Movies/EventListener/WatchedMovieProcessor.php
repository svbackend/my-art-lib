<?php

namespace App\Movies\EventListener;

use App\Movies\Entity\Movie;
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

            // Because after unserialization doctrine think that User is new and trying to save it
            $user = $this->em->find(User::class, $watchedMovie->getUser()->getId());
            $watchedMovie->updateUser($user);

            // If movie not saved yet
            if ($movie->getId() === null) {
                $this->em->persist($movie);
            } else {
                $movie = $this->em->find(Movie::class, $watchedMovie->getMovie()->getId());
                $watchedMovie->updateMovie($movie);
            }

            $this->em->persist($watchedMovie);
        }

        $this->em->flush();

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_WATCHED_MOVIE_TMDB];
    }
}