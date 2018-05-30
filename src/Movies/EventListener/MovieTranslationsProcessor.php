<?php

namespace App\Movies\EventListener;

use App\Genres\Entity\Genre;
use App\Movies\Entity\Movie;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbSearchService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\ProducerInterface;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Enqueue\Client\TopicSubscriberInterface;

class MovieTranslationsProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const LOAD_TRANSLATIONS = 'LoadMoviesTranslationsFromTMDB';

    private $em;
    private $searchService;
    private $movieRepository;

    public function __construct(EntityManagerInterface $em, MovieRepository $movieRepository, TmdbSearchService $searchService)
    {
        $this->em = $em;
        $this->movieRepository = $movieRepository;
        $this->searchService = $searchService;
    }

    /**
     * @param PsrMessage $message
     * @param PsrContext $session
     * @return object|string
     * @throws \Doctrine\ORM\ORMException
     */
    public function process(PsrMessage $message, PsrContext $session)
    {
        $moviesIds = $message->getBody();
        $moviesIds = unserialize($moviesIds);

        if ($this->em->isOpen() === false) {
            $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
        }

        $movies = $this->movieRepository->findAllByIds($moviesIds);

        foreach ($movies as $movie) {
            
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
        return [self::LOAD_TRANSLATIONS];
    }
}