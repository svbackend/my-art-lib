<?php
declare(strict_types=1);

namespace App\Movies\Entity;

use App\Genres\Entity\Genre;
use App\Translation\TranslatableTrait;
use App\Translation\TranslatableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Component\Validator\Constraints as Assert;
use JMS\Serializer\Annotation\Type;

//todo production_countries, production_companies, actors

/**
 * @ORM\Entity(repositoryClass="App\Movies\Repository\MovieRepository")
 * @ORM\Table(name="movies")
 * @ExclusionPolicy("all")
 * @method MovieTranslations getTranslation(string $locale, bool $useFallbackLocale = true)
 */
class Movie implements TranslatableInterface
{
    use TranslatableTrait;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Expose
     */
    private $id;

    /**
     * @var $translations MovieTranslations[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Movies\Entity\MovieTranslations", mappedBy="movie", cascade={"persist", "remove"})
     * @Assert\Valid(traverse=true)
     * @Expose
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
     * @Expose
     */
    private $genres;

    /**
     * @Expose
     * @ORM\Column(type="string", length=100)
     */
    private $originalTitle;

    /**
     * @Expose
     * @ORM\Column(type="string", length=255)
     */
    private $originalPosterUrl;

    /**
     * @ORM\Embedded(class="App\Movies\Entity\MovieTMDB", columnPrefix="tmdb_")
     * @Assert\Valid(traverse=true)
     */
    private $tmdb;

    /**
     * @Expose
     * @ORM\Column(type="string", length=20, nullable=true)
     */
    private $imdbId;

    /**
     * @Expose
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    private $runtime;

    /**
     * @Expose
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    private $budget;

    /**
     * @Type("DateTimeImmutable<'Y-m-d'>")
     * @Expose
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
}