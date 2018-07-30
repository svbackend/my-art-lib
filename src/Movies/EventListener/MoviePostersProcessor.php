<?php

namespace App\Movies\EventListener;

use App\Movies\DTO\MovieTranslationDTO;
use App\Movies\Entity\Movie;
use App\Movies\Entity\MovieTranslations;
use App\Movies\Exception\TmdbMovieNotFoundException;
use App\Movies\Exception\TmdbRequestLimitException;
use App\Movies\Repository\MovieRepository;
use App\Movies\Service\TmdbSearchService;
use App\Movies\Utils\Poster;
use App\Service\LocaleService;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class MoviePostersProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const LOAD_POSTERS = 'LoadMoviesPosters';

    /** @var EntityManager */
    private $em;
    private $movieRepository;
    private $producer;

    public function __construct(EntityManagerInterface $em, ProducerInterface $producer, MovieRepository $movieRepository)
    {
        if ($em instanceof EntityManager === false) {
            throw new \InvalidArgumentException(
                sprintf(
                    'MovieTranslationsProcessor expects %s as %s realization',
                    EntityManager::class,
                    EntityManagerInterface::class
                )
            );
        }

        $this->em = $em;
        $this->movieRepository = $movieRepository;
        $this->producer = $producer;
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        $moviesIds = $message->getBody();
        $moviesIds = unserialize($moviesIds);

        if ($this->em->isOpen() === false) {
            $this->em = $this->em->create($this->em->getConnection(), $this->em->getConfiguration());
        }

        $movies = $this->movieRepository->findAllByIds($moviesIds);
        // $total = count($movies);
        $processed = 0;
        foreach ($movies as $movie) {
            $posterUrl = $movie->getOriginalPosterUrl();
            // $posterName = str_replace('https://image.tmdb.org/t/p/original', '', $posterUrl);
            if ($posterUrl === 'https://image.tmdb.org/t/p/original') {
                $processed++;
                continue;
            }

            $posterPath = Poster::savePoster($movie->getId(), $movie->getOriginalPosterUrl());
            if ($posterPath === null) {
                $processed++;
                continue;
            }

            $movie->setOriginalPosterUrl(Poster::getUrl($movie->getId()));
        }

        try {
            $this->em->flush();
        } catch (\Throwable $exception) {
            echo $exception->getMessage();
        }
    }

    public static function getSubscribedTopics()
    {
        return [self::LOAD_POSTERS];
    }
}
