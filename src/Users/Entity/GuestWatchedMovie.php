<?php
declare(strict_types=1);

namespace App\Users\Entity;

use App\Movies\Entity\Movie;
use App\Movies\Entity\WatchedMovie;
use App\Users\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Movies\Repository\GuestWatchedMovieRepository")
 * @ORM\Table(name="guests_watched_movies",
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(name="idx_GuestWatchedMovie_guest_session_id_movie_id", columns={"guest_session_id", "movie_id"})
 *     })
 */
class GuestWatchedMovie extends WatchedMovie
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"list"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Users\Entity\GuestSession")
     * @ORM\JoinColumn(nullable=false)
     */
    private $guestSession;

    /**
     * UserWatchedMovies constructor.
     * @param GuestSession $guestSession
     * @param Movie $movie
     * @param float|null $vote
     * @param \DateTimeInterface|null $watchedAt
     * @throws \Exception
     */
    public function __construct(GuestSession $guestSession, Movie $movie, ?float $vote, ?\DateTimeInterface $watchedAt)
    {
        $this->guestSession = $guestSession;
        parent::__construct($movie, $vote, $watchedAt);
    }

    public function setGuestSession(GuestSession $guestSession): void
    {
        $this->guestSession = $guestSession;
    }

    public function getGuestSession(): ?GuestSession
    {
        return $this->guestSession;
    }
}