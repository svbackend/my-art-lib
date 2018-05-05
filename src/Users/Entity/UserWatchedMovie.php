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
 * @ORM\Entity(repositoryClass="App\Movies\Repository\WatchedMoviesRepository")
 * @ORM\Table(name="users_watched_movies",
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(name="idx_UserWatchedMovies_user_id_movie_id", columns={"user_id", "movie_id"})
 *     })
 */
class UserWatchedMovie extends WatchedMovie
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
        parent::__construct($movie, $vote, $watchedAt);
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function updateUser(User $user)
    {
        $this->user = $user;
    }
}