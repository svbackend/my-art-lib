<?php
declare(strict_types=1);

namespace App\Movies\Entity;

use App\Genres\Entity\Genre;
use App\Translation\TranslatableTrait;
use App\Translation\TranslatableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

//todo production_countries, production_companies, actors

/**
 * @ORM\Entity(repositoryClass="App\Movies\Repository\MovieRepository")
 * @ORM\Table(name="movies")
 * @method MovieTranslations getTranslation(string $locale, bool $useFallbackLocale = true)
 */
class Movie implements TranslatableInterface
{
    use TranslatableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"list", "view"})
     */
    private $id;

    /**
     * @var $translations MovieTranslations[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Movies\Entity\MovieTranslations", mappedBy="movie", cascade={"persist", "remove"})
     * @Assert\Valid(traverse=true)
     * @Groups({"list", "view"})
     */
    private $translations;

    /**
     * @var $genres Genre[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="App\Genres\Entity\Genre", cascade={"persist"})
     * @ORM\JoinTable(name="movies_genres",
     *      joinColumns={@ORM\JoinColumn(name="movie_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="genre_id", referencedColumnName="id")}
     *      )
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Valid(traverse=true)
     * @Groups({"list", "view"})
     */
    private $genres;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="string", length=100)
     */
    private $originalTitle;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="string", length=255)
     */
    private $originalPosterUrl;

    /**
     * @ORM\Embedded(class="App\Movies\Entity\MovieTMDB", columnPrefix="tmdb_")
     * @Assert\Valid(traverse=true)
     * @Groups({"list", "view"})
     */
    private $tmdb;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Groups({"list", "view"})
     */
    private $imdbId;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    private $runtime;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    private $budget;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="date", nullable=true)
     */
    private $releaseDate;

    public function __construct(string $originalTitle, string $posterUrl, MovieTMDB $tmdb)
    {
        $this->translations = new ArrayCollection();
        $this->genres = new ArrayCollection();

        $this->originalTitle = $originalTitle;
        $this->originalPosterUrl = $posterUrl;
        $this->tmdb = $tmdb;
    }

    public function getId()
    {
        return $this->id;
    }

    public function addGenre(Genre $genre)
    {
        $this->genres->add($genre);
        return $this;
    }

    public function getGenres()
    {
        return $this->genres->toArray();
    }

    public function updateTmdb(MovieTMDB $tmdb)
    {
        $this->tmdb = $tmdb;
    }

    /**
     * @param string $imdbId
     * @return Movie
     */
    public function setImdbId(string $imdbId)
    {
        $this->imdbId = $imdbId;
        return $this;
    }

    /**
     * @param int $runtime
     * @return Movie
     */
    public function setRuntime(int $runtime)
    {
        $this->runtime = $runtime;
        return $this;
    }

    /**
     * @param int $budget
     * @return Movie
     */
    public function setBudget(int $budget)
    {
        $this->budget = $budget;
        return $this;
    }

    /**
     * @param \DateTimeInterface $releaseDate
     * @return Movie
     */
    public function setReleaseDate(\DateTimeInterface $releaseDate)
    {
        $this->releaseDate = $releaseDate;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginalTitle()
    {
        return $this->originalTitle;
    }

    /**
     * @return mixed
     */
    public function getOriginalPosterUrl()
    {
        return $this->originalPosterUrl;
    }

    /**
     * @return MovieTMDB
     */
    public function getTmdb()
    {
        return $this->tmdb;
    }

    /**
     * @return mixed
     */
    public function getImdbId()
    {
        return $this->imdbId;
    }

    /**
     * @return mixed
     */
    public function getRuntime()
    {
        return $this->runtime;
    }

    /**
     * @return mixed
     */
    public function getBudget()
    {
        return $this->budget;
    }

    /**
     * @return mixed
     */
    public function getReleaseDate()
    {
        return $this->releaseDate;
    }
}