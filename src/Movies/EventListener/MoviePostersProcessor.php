<?php

namespace App\Movies\EventListener;

use App\Movies\Repository\MovieRepository;
use App\Movies\Utils\Poster;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class MoviePostersProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const LOAD_POSTERS = 'LoadMoviesPosters';

    private $em;
    private $movieRepository;

    public function __construct(EntityManagerInterface $em, MovieRepository $movieRepository)
    {
        $this->em = $em;
        $this->movieRepository = $movieRepository;
    }

    public function process(PsrMessage $message, PsrContext $session)
    {
        $movieId = $message->getBody();
        $movieId = json_decode($movieId, true);

        $movie = $this->movieRepository->find($movieId);

        if ($movie === null) {
            return self::REJECT;
        }

        $posterUrl = $movie->getOriginalPosterUrl();
        // $posterName = str_replace('https://image.tmdb.org/t/p/original', '', $posterUrl);
        if ($posterUrl === 'https://image.tmdb.org/t/p/original') {
            return self::REJECT;
        }

        $posterPath = Poster::savePoster($movie->getId(), $movie->getOriginalPosterUrl());
        if ($posterPath === null) {
            return self::REJECT;
        }

        $movie->setOriginalPosterUrl(Poster::getUrl($movie->getId()));

        $this->em->flush();
        $this->em->clear();

        $message = $session = $movieId = $movie = null;
        unset($message, $session, $movieId, $movie);

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::LOAD_POSTERS];
    }
}
