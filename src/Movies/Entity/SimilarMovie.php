<?php

declare(strict_types=1);

namespace App\Movies\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Movies\Repository\SimilarMovieRepository")
 * @ORM\Table(name="similar_movies",
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(name="idx_SimilarMovie_original_movie_id_similar_movie_id", columns={"original_movie_id", "similar_movie_id"})
 *     })
 */
class SimilarMovie
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
    private $similarMovie;

    /**
     * SimilarMovie constructor.
     *
     * @param Movie $originalMovie
     * @param Movie $similarMovie
     */
    public function __construct(Movie $originalMovie, Movie $similarMovie)
    {
        $this->originalMovie = $originalMovie;
        $this->similarMovie = $similarMovie;
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
    public function getSimilarMovie(): Movie
    {
        return $this->similarMovie;
    }
}
