<?php

namespace App\Movies\EventListener;

use App\Genres\Entity\Genre;
use App\Movies\Entity\Movie;
use App\Movies\Entity\WatchedMovie;
use App\Guests\Entity\GuestSession;
use App\Guests\Entity\GuestWatchedMovie;
use App\Users\Entity\UserWatchedMovie;
use App\Users\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Enqueue\Client\TopicSubscriberInterface;
use Psr\Log\LoggerInterface;

class WatchedMovieProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const ADD_WATCHED_MOVIE_TMDB = 'addWatchedMovieTMDB';

    private $em;
    private $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @param PsrMessage $message
     * @param PsrContext $session
     * @return object|string
     * @throws \Doctrine\ORM\ORMException|\Exception
     */
    public function process(PsrMessage $message, PsrContext $session)
    {
        $watchedMovies = $message->getBody();
        $watchedMovies = unserialize($watchedMovies);

        /** @var $watchedMovies UserWatchedMovie[]|GuestWatchedMovie[] */
        foreach ($watchedMovies as $watchedMovie) {
            $movie = $watchedMovie->getMovie();
            $genres = $movie->getGenres();
            $movie->removeAllGenres(); // Because doctrine doesn't know about these genres
            foreach ($genres as $genre) {
                /** @var $genreReference Genre */
                $genreReference = $this->em->getReference(Genre::class, $genre->getId()); // so we need to re-add 'em
                $movie->addGenre($genreReference);
            }

            // Recreate *WatchedMovie entity with new managed by doctrine associations
            if ($watchedMovie instanceof UserWatchedMovie) {
                /** @var $userReference User */
                $userReference = $this->em->getReference(User::class, $watchedMovie->getUser()->getId());
                $newWatchedMovie = new UserWatchedMovie($userReference, $movie, $watchedMovie->getVote(), $watchedMovie->getWatchedAt());
            }

            if ($watchedMovie instanceof GuestWatchedMovie) {
                /** @var $guestSessionReference GuestSession */
                $guestSessionReference = $this->em->getReference(GuestSession::class, $watchedMovie->getGuestSession()->getId());
                $newWatchedMovie = new GuestWatchedMovie($guestSessionReference, $movie, $watchedMovie->getVote(), $watchedMovie->getWatchedAt());
            }

            $this->em->persist($movie);

            if (isset($newWatchedMovie)) {
                $this->em->persist($newWatchedMovie);
            }
        }

        $this->em->flush();

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::ADD_WATCHED_MOVIE_TMDB];
    }
}