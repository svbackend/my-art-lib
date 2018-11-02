<?php

declare(strict_types=1);

namespace App\Movies\Entity;

use App\Countries\Entity\Country;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Movies\Repository\MovieReleaseDateRepository")
 * @ORM\Table(name="movies_release_dates",
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(name="Movie_id_Country_id", columns={"movie_id", "country_id"})
 *     })
 */
class MovieReleaseDate
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
     * @Groups({"list", "view"})
     */
    private $movie;

    /**
     * @ORM\ManyToOne(targetEntity="App\Countries\Entity\Country")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"list", "view"})
     */
    private $country;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="date", nullable=true)
     */
    private $date;

    public function __construct(Movie $movie, Country $country, \DateTimeInterface $date)
    {
        $this->movie = $movie;
        $this->country = $country;
        $this->date = $date;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMovie(): Movie
    {
        return $this->movie;
    }

    public function getCountry(): Country
    {
        return $this->country;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }
}
