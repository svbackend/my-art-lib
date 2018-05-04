<?php

namespace App\Movies\EventListener;

use App\Movies\Entity\UserWatchedMovie;
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
            #$user = $this->em->find(User::class, $watchedMovie->getUser()->getId());
            #$watchedMovie->setUser($user);
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