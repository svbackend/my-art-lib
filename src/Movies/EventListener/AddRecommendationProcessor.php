<?php

namespace App\Movies\EventListener;

use App\Movies\Repository\MovieRepository;
use App\Users\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\Context;
use Interop\Queue\Message as QMessage;
use Interop\Queue\Processor;

class AddRecommendationProcessor implements Processor, TopicSubscriberInterface
{
    public const ADD_RECOMMENDATION = 'AddRecommendation';

    private $em;
    private $movieRepository;

    public function __construct(EntityManagerInterface $em, MovieRepository $movieRepository)
    {
        $this->em = $em;
        $this->movieRepository = $movieRepository;
    }

    public function process(QMessage $message, Context $session)
    {
        $movie = $message->getBody();
        $movie = json_decode($movie, true);

        $originalMovie = $this->movieRepository->findOneByIdOrTmdbId($movie['movie_id']);

        if ($originalMovie === null) {
            return self::REJECT;
        }

        $recommendedMovie = $this->movieRepository->findOneByIdOrTmdbId(null, $movie['tmdb_id']);

        if ($recommendedMovie === null) {
            return self::REJECT;
        }

        $user = $this->em->getReference(User::class, $movie['user_id']);

        $originalMovie->addRecommendation($user, $recommendedMovie);
        $this->em->persist($originalMovie);

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
            // do nothing, it's ok
        }

        $this->em->clear();

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_RECOMMENDATION];
    }
}
