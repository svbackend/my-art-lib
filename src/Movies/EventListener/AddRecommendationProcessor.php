<?php

namespace App\Movies\EventListener;

use App\Movies\Repository\MovieRepository;
use App\Users\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class AddRecommendationProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const ADD_RECOMMENDATION = 'AddRecommendation';

    private $em;
    private $movieRepository;

    public function __construct(EntityManagerInterface $em, MovieRepository $movieRepository)
    {
        $this->em = $em;
        $this->movieRepository = $movieRepository;
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        $movie = $message->getBody();
        $movie = json_decode($movie, true);

        if ($this->em->isOpen() === false) {
            throw new \ErrorException('em is closed');
        }

        $originalMovie = $this->movieRepository->findOneByIdOrTmdbId($movie['movie_id']);

        if ($originalMovie === null) {
            return self::ACK;
        }

        $movies = $this->movieRepository->findAllByTmdbIds([$movie['tmdb_id']]);

        if (!count($movies)) {
            return self::ACK;
        }

        $recommendedMovie = reset($movies);
        $user = $this->em->getReference(User::class, $movie['user_id']);

        if ($user === null) {
            return self::ACK;
        }

        $originalMovie->addRecommendation($user, $recommendedMovie);

        try {
            $this->em->persist($originalMovie);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
            echo $uniqueConstraintViolationException->getMessage();
            // do nothing, it's ok
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        } finally {
            $this->em->clear();
        }

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_RECOMMENDATION];
    }
}
