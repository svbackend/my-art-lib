<?php

declare(strict_types=1);

namespace App\Movies\Entity;

use App\Users\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Movies\Repository\MovieRecommendationRepository")
 * @ORM\Table(name="movies_recommendations",
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(name="MovieRecommendation_original_movie_recommended_movie_user", columns={"original_movie_id", "recommended_movie_id", "user_id"})
 *     })
 */
class MovieRecommendation
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
    private $originalMovie;

    /**
     * @ORM\ManyToOne(targetEntity="App\Movies\Entity\Movie")
     * @ORM\JoinColumn(nullable=false)
     */
    private $recommendedMovie;

    /**
     * @ORM\ManyToOne(targetEntity="App\Users\Entity\User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * MovieRecommendation constructor.
     *
     * @param User $user
     * @param Movie $originalMovie
     * @param Movie $recommendedMovie
     */
    public function __construct(User $user, Movie $originalMovie, Movie $recommendedMovie)
    {
        $this->user = $user;
        $this->originalMovie = $originalMovie;
        $this->recommendedMovie = $recommendedMovie;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return Movie
     */
    public function getOriginalMovie(): Movie
    {
        return $this->originalMovie;
    }

    /**
     * @return Movie
     */
    public function getRecommendedMovie(): Movie
    {
        return $this->recommendedMovie;
    }
}
