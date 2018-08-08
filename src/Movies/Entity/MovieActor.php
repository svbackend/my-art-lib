<?php

declare(strict_types=1);

namespace App\Movies\Entity;

use App\Actors\Entity\Actor;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Movies\Repository\MovieActorRepository")
 * @ORM\Table(name="movies_actors",
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(name="Movie_id_Actor_id", columns={"movie_id", "actor_id"})
 *     })
 */
class MovieActor
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"list", "view"})
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Movies\Entity\Movie")
     * @ORM\JoinColumn(nullable=false)
     */
    private $movie;

    /**
     * @ORM\ManyToOne(targetEntity="App\Actors\Entity\Actor")
     * @ORM\JoinColumn(nullable=false)
     */
    private $actor;

    public function __construct(Movie $movie, Actor $actor)
    {
        $this->movie = $movie;
        $this->actor = $actor;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Movie
     */
    public function getMovie(): Movie
    {
        return $this->movie;
    }

    /**
     * @return Actor
     */
    public function getActor(): Actor
    {
        return $this->actor;
    }
}
