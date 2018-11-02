<?php

declare(strict_types=1);

namespace App\Users\Entity;

use App\Movies\Entity\Movie;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Users\Repository\InterestedMovieRepository")
 * @ORM\Table(name="users_interested_movies",
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(name="idx_UserInterestedMovie_user_id_movie_id", columns={"user_id", "movie_id"})
 *     })
 */
class UserInterestedMovie
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"list", "view"})
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
     */
    protected $movie;

    /**
     * UserInterestedMovie constructor.
     *
     * @param User  $user
     * @param Movie $movie
     *
     * @throws \Exception
     */
    public function __construct(User $user, Movie $movie)
    {
        $this->user = $user;
        $this->movie = $movie;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getMovie(): Movie
    {
        return $this->movie;
    }
}
