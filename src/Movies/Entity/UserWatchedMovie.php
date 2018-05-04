<?php
declare(strict_types=1);

namespace App\Movies\Entity;

use App\Users\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Pivot table: user => (userWatchedMovie) <= movies
 * @ORM\Entity(repositoryClass="App\Movies\Repository\WatchedMoviesRepository")
 * @ORM\Table(name="users_watched_movies",
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(name="idx_UserWatchedMovies_user_id_movie_id", columns={"user_id", "movie_id"})
 *     })
 */
class UserWatchedMovie
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"list"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Users\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Movies\Entity\Movie")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"list"})
     */
    private $movie;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="decimal", nullable=true)
     */
    private $vote;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $addedAt;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="date", nullable=true)
     */
    private $watchedAt;

    /**
     * UserWatchedMovies constructor.
     * @param User $user
     * @param Movie $movie
     * @param float|null $vote
     * @param \DateTimeInterface|null $watchedAt
     * @throws \Exception
     */
    public function __construct(User $user, Movie $movie, ?float $vote, ?\DateTimeInterface $watchedAt)
    {
        $this->user = $user;
        $this->movie = $movie;
        $this->addedAt = new \DateTimeImmutable();
        $this->watchedAt = $watchedAt;

        if ($vote !== null) {
            $this->vote = $vote > 0.0 ? $vote : null;
        }
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }

    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    public function getVote(): ?float
    {
        return $this->vote;
    }

    public function getAddedAt(): ?\DateTimeInterface
    {
        return $this->addedAt;
    }

    public function getWatchedAt(): ?\DateTimeInterface
    {
        return $this->watchedAt;
    }
}