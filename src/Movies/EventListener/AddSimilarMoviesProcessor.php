<?php

namespace App\Movies\EventListener;

use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbNormalizerService;
use App\Movies\Service\TmdbSearchService;
use App\Movies\Service\TmdbSyncService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class AddSimilarMoviesProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const ADD_SIMILAR_MOVIES = 'AddSimilarMovies';

    /** @var EntityManager */
    private $em;
    private $searchService;
    private $movieRepository;

    public function __construct(EntityManagerInterface $em, MovieRepository $movieRepository, TmdbSearchService $searchService)
    {
        if ($em instanceof EntityManager === false) {
            throw new \InvalidArgumentException(
                sprintf(
                    'AddSimilarMoviesProcessor expects %s as %s realization',
                    EntityManager::class,
                    EntityManagerInterface::class
                )
            );
        }

        $this->em = $em;
        $this->movieRepository = $movieRepository;
        $this->searchService = $searchService;
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        echo "AddSimilarMoviesProcessor processing...";
        $moviesTable = $message->getBody();
        $moviesTable = json_decode($moviesTable, true);

        if ($this->em->isOpen() === false) {
            $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
        }

        $originalMoviesIds = array_keys($moviesTable);
        $movies = $this->movieRepository->findAllByIds($originalMoviesIds);

        foreach ($movies as $movie) {
            $similarMovies = $this->movieRepository->findAllByTmdbIds($moviesTable[$movie->getId()]);
            foreach ($similarMovies as $similarMovie) {
                echo "add {$similarMovie->getOriginalTitle()} as similar movie to {$movie->getOriginalTitle()}";
                echo "\r\n";
                $movie->addSimilarMovie($similarMovie);
            }

            $this->em->persist($movie);
        }

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
            // do nothing, it's ok
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_SIMILAR_MOVIES];
    }
}
