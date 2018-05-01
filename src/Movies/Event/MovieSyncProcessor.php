<?php

namespace App\Movies\Event;

use Doctrine\ORM\EntityManagerInterface;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Enqueue\Client\TopicSubscriberInterface;
use Psr\Log\LoggerInterface;

class MovieSyncProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const ADD_MOVIES_TMDB = 'addMoviesTMDB';
    const UPDATE_MOVIES_TMDB = 'updateMoviesTMDB';

    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        $movies = $message->getBody();
        $movies = unserialize($movies);
        $moviesCount = 0;

        foreach ($movies as $movie) {
            $this->em->persist($movie);
            $moviesCount++;
        }

        $this->em->flush();

        $this->logger->debug("Successfully saved {$moviesCount} movies!\r\n");
        $this->logger->debug("Properties:\r\n", $message->getProperties());

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_MOVIES_TMDB, self::UPDATE_MOVIES_TMDB];
    }
}