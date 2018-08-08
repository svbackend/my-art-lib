<?php

namespace App\Actors\EventListener;

use App\Actors\Repository\ActorRepository;
use App\Movies\Repository\MovieRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Psr\Log\LoggerInterface;

class ActorAddToMovieProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const ADD_TO_MOVIE = 'addActorToMovie';

    private $em;
    private $logger;
    private $movieRepository;
    private $actorRepository;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger, MovieRepository $movieRepository, ActorRepository $actorRepository)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->movieRepository = $movieRepository;
        $this->actorRepository = $actorRepository;
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        $this->logger->info('ActorAddToMovieProcessor start with memory usage: ', [memory_get_usage()]);

        $messageBody = $message->getBody();
        $data = json_decode($messageBody, true);
        $movieId = $data['movieId'];
        $actorTmdbId = $data['actorTmdbId'];

        if (null === $movie = $this->movieRepository->find($movieId)) {
            return self::REJECT;
        }

        if (null === $actor = $this->actorRepository->findByTmdbId($actorTmdbId)) {
            return self::REJECT;
        }

        $movie->addActor($actor);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueException) {
            // its ok
        }

        $this->em->clear();
        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_TO_MOVIE];
    }
}
