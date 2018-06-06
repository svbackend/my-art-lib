<?php

namespace App\Movies\EventListener;

use App\Genres\Entity\Genre;
use App\Movies\Entity\Movie;
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

    public function __construct(EntityManagerInterface $em, ProducerInterface $producer)
    {
        if (false === $em instanceof EntityManager) {
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
    }

    /**
     * @param PsrMessage $message
     * @param PsrContext $session
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return object|string
     */
    public function process(PsrMessage $message, PsrContext $session)
    {
        $movies = $message->getBody();
        $movies = unserialize($movies);
        $moviesIdsToLoadTranslations = [];
        $moviesCount = 0;

        if (false === $this->em->isOpen()) {
            $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
        }

        foreach ($movies as $movie) {
            $movie = $this->refreshGenresAssociations($movie);
            $this->em->persist($movie);
            $moviesIdsToLoadTranslations[] = $movie->getId();
            ++$moviesCount;
        }

        try {
            $this->em->flush();
        } catch (UniqueConstraintViolationException $uniqueConstraintViolationException) {
            // do nothing, it's ok
        } catch (\Exception $exception) {
            echo $exception->getMessage();
        }

        $this->loadTranslations($moviesIdsToLoadTranslations);

        return self::ACK;
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

    public static function getSubscribedTopics()
    {
        return [self::ADD_MOVIES_TMDB];
    }
}
