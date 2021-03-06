<?php

namespace App\Movies\EventListener;

use App\Movies\Entity\Movie;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbSearchService;
use App\Users\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\Context;
use Interop\Queue\Message as QMessage;
use Interop\Queue\Processor;

class AddSimilarMoviesProcessor implements Processor, TopicSubscriberInterface
{
    const ADD_SIMILAR_MOVIES = 'AddSimilarMovies';

    /** @var EntityManager */
    private $em;
    private $searchService;
    private $movieRepository;

    public function __construct(EntityManagerInterface $em, MovieRepository $movieRepository, TmdbSearchService $searchService)
    {
        $this->em = $em;
        $this->movieRepository = $movieRepository;
        $this->searchService = $searchService;
    }

    public function process(QMessage $message, Context $session)
    {
        $moviesTable = $message->getBody();
        $moviesTable = json_decode($moviesTable, true);

        if ($this->em->isOpen() === false) {
            throw new \ErrorException('em is closed');
        }

        $originalMoviesIds = array_keys($moviesTable);
        $movies = $this->movieRepository->findAllByIds($originalMoviesIds);
        $supportAcc = $this->em->getReference(User::class, 1);

        //todo add tests
        foreach ($movies as $movie) {
            $similarMovies = $this->movieRepository->findAllIdsByTmdbIds($moviesTable[$movie->getId()]);
            foreach ($similarMovies as $similarMovie) {
                $similarMovieRef = $this->em->getReference(Movie::class, $similarMovie['id']);
                $movie->addSimilarMovie($similarMovieRef);
                if (is_numeric($similarMovie['tmdb.voteAverage']) && $similarMovie['tmdb.voteAverage'] >= 7) {
                    if ($similarMovie['releaseDate'] !== null) {
                        $releaseDate = new \DateTimeImmutable($similarMovie['releaseDate']);
                        // some kind of discrimination of old movies
                        if ($releaseDate->format('Y') <= 1980 && $similarMovie['tmdb.voteAverage'] <= 8) {
                            continue;
                        }
                        if ($releaseDate->format('Y') <= 1990 && $similarMovie['tmdb.voteAverage'] <= 7) {
                            continue;
                        }
                    }

                    $movie->addRecommendation($supportAcc, $similarMovieRef);
                }
            }

            $this->em->persist($movie);
        }

        $this->em->flush();
        $this->em->clear();

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_SIMILAR_MOVIES];
    }
}
