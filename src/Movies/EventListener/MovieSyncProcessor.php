<?php

namespace App\Movies\EventListener;

use App\Genres\Entity\Genre;
use App\Movies\Entity\Movie;
use App\Movies\Repository\MovieRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class MovieSyncProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const ADD_MOVIES_TMDB = 'addMoviesTMDB';

    private $em;
    private $producer;
    private $repository;

    public function __construct(EntityManagerInterface $em, ProducerInterface $producer, MovieRepository $repository)
    {
        if ($em instanceof EntityManager === false) {
            throw new \InvalidArgumentException(
                sprintf(
                    'MovieSyncProcessor expects %s as %s realization',
                    EntityManager::class,
                    EntityManagerInterface::class
                )
            );
        }

        $this->em = $em;
        $this->producer = $producer;
        $this->repository = $repository;
    }

    /**
     * @param PsrMessage $message
     * @param PsrContext $session
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return string
     */
    public function process(PsrMessage $message, PsrContext $session)
    {
        echo "MovieSyncProcessor processing...\r\n";
        $movies = $message->getBody();
        $movies = unserialize($movies);
        $savedMoviesIds = [];
        $savedMoviesTmdbIds = []; // tmdbId => Movie Object
        $moviesCount = 0;

        if ($this->em->isOpen() === false) {
            $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
        }

        foreach ($movies as $movie) {
            $movie = $this->refreshGenresAssociations($movie);
            $this->em->persist($movie);
            echo "{$movie->getOriginalTitle()} saved with id {$movie->getId()} \r\n";
            $savedMoviesIds[] = $movie->getId();
            $savedMoviesTmdbIds[$movie->getTmdb()->getId()] = $movie;
            ++$moviesCount;
        }

        //echo "adding similar movies from similar_movies_table...\r\n";
        //echo var_export($message->getProperty('similar_movies_table', []));
        //$this->addSimilarMovies($message->getProperty('similar_movies_table', []), $savedMoviesTmdbIds);

        try {
            echo "Flushed with {$moviesCount} movies persisted.\r\n";
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
            echo $uniqueConstraintViolationException->getMessage();
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        echo "Loading translations... \r\n";
        $this->loadTranslations($savedMoviesIds);

        if ($message->getProperty('load_similar', true) === true) {
            echo "Loading similar movies... \r\n";
            $this->loadSimilarMovies($savedMoviesIds);
        }

        return self::ACK;
    }

    private function addSimilarMovies(array $similarMoviesTable, array $savedMoviesTmdbIds)
    {
        if (count($similarMoviesTable)) {
            echo "trying to find all movies by ids: \r\n";
            echo var_export(array_values($similarMoviesTable));
            $originalMovies = $this->repository->findAllByIds(array_keys($similarMoviesTable));
            foreach ($originalMovies as $originalMovie) {
                $similarMoviesTmdbIds = $similarMoviesTable[$originalMovie->getId()];
                foreach ($similarMoviesTmdbIds as $tmdbId) {
                    if (isset($savedMoviesTmdbIds[$tmdbId])) {
                        echo "Added {$savedMoviesTmdbIds[$tmdbId]->getOriginalTitle()} as similar movie to {$originalMovie->getOriginalTitle()}\r\n";
                        $originalMovie->addSimilarMovie($savedMoviesTmdbIds[$tmdbId]);
                        $this->em->persist($savedMoviesTmdbIds[$tmdbId]);
                        $this->em->persist($originalMovie); // actually this line probably useless?
                    }
                }
            }
        }
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
            if ($genre->getId()) {
                // if genre already saved then just add it as reference
                $movie->addGenre($this->getGenreReference($genre->getId()));
            } else {
                // Otherwise we need to persist $genre to save it
                $this->em->persist($genre);
                $movie->addGenre($genre);
            }
        }

        return $movie;
    }

    /**
     * @param int $id
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return null|Genre
     */
    private function getGenreReference(int $id): ?object
    {
        return $this->em->getReference(Genre::class, $id);
    }

    private function loadTranslations(array $moviesIds)
    {
        $this->producer->sendEvent(MovieTranslationsProcessor::LOAD_TRANSLATIONS, serialize($moviesIds));
    }

    private function loadSimilarMovies(array $moviesIds)
    {
        $this->producer->sendEvent(SimilarMoviesProcessor::LOAD_SIMILAR_MOVIES, serialize($moviesIds));
    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_MOVIES_TMDB];
    }
}
