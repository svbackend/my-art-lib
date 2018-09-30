<?php

declare(strict_types=1);

namespace App\Movies\Entity;

use App\Actors\Entity\Actor;
use App\Genres\Entity\Genre;
use App\Guests\Entity\GuestWatchedMovie;
use App\Movies\DTO\MovieDTO;
use App\Translation\TranslatableInterface;
use App\Translation\TranslatableTrait;
use App\Users\Entity\User;
use App\Users\Entity\UserInterestedMovie;
use App\Users\Entity\UserWatchedMovie;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

//todo production_countries, production_companies

/**
 * @ORM\Entity(repositoryClass="App\Movies\Repository\MovieRepository")
 * @ORM\Table(name="movies")
 *
 * @method MovieTranslations getTranslation(string $locale, bool $useFallbackLocale = true)
 * @UniqueEntity("tmdb.id")
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
     * @var MovieTranslations[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Movies\Entity\MovieTranslations", mappedBy="movie", cascade={"persist", "remove"})
     * @Assert\Valid(traverse=true)
     * @Groups({"list", "view"})
     */
    private $translations;

    /**
     * @var Actor[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Movies\Entity\MovieActor", mappedBy="movie", cascade={"persist", "remove"})
     * @Assert\Valid(traverse=true)
     * @Groups({"view"})
     */
    private $actors;

    /**
     * @var Genre[]|ArrayCollection
     * @ORM\ManyToMany(targetEntity="App\Genres\Entity\Genre")
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
     * @ORM\Column(type="string", length=255, nullable=true)
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
     * @Groups({"view"})
     */
    private $imdbId;

    /**
     * @Groups({"view"})
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    private $runtime;

    /**
     * @Groups({"view"})
     * @ORM\Column(type="integer", nullable=true, options={"default": 0})
     */
    private $budget;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="date", nullable=true)
     */
    private $releaseDate;

    /**
     * @var GuestWatchedMovie
     * @ORM\OneToOne(targetEntity="App\Guests\Entity\GuestWatchedMovie", mappedBy="movie")
     * @Groups({"list", "view"})
     */
    private $guestWatchedMovie;

    /**
     * @var UserWatchedMovie
     * @ORM\OneToOne(targetEntity="App\Users\Entity\UserWatchedMovie", mappedBy="movie")
     * @Groups({"ROLE_USER"})
     */
    private $userWatchedMovie;

    /**
     * @var UserWatchedMovie
     * @ORM\OneToOne(targetEntity="App\Users\Entity\UserWatchedMovie", mappedBy="movie")
     * @Groups({"userWatchedMovies"})
     */
    private $ownerWatchedMovie;

    /**
     * @var UserInterestedMovie
     * @ORM\OneToOne(targetEntity="App\Users\Entity\UserInterestedMovie", mappedBy="movie")
     * @Groups({"ROLE_USER"})
     */
    private $userInterestedMovie;

    /**
     * @Groups({"list", "view"})
     */
    private $isWatched;

    /**
     * @var MovieRecommendation
     * @ORM\OneToOne(targetEntity="App\Movies\Entity\MovieRecommendation", mappedBy="recommendedMovie")
     * @Groups({"ROLE_USER"})
     */
    private $userRecommendedMovie;

    /**
     * @ORM\OneToMany(targetEntity="App\Movies\Entity\SimilarMovie", mappedBy="originalMovie", cascade={"persist", "remove"})
     */
    private $similarMovies;

    /**
     * @ORM\OneToMany(targetEntity="App\Movies\Entity\MovieRecommendation", mappedBy="originalMovie", cascade={"persist", "remove"})
     */
    private $recommendations;

    public function __construct(MovieDTO $movieDTO, MovieTMDB $tmdb)
    {
        $this->translations = new ArrayCollection();
        $this->genres = new ArrayCollection();
        $this->similarMovies = new ArrayCollection();
        $this->recommendations = new ArrayCollection();
        $this->actors = new ArrayCollection();

        $this->originalTitle = $movieDTO->getOriginalTitle();
        $this->originalPosterUrl = $movieDTO->getOriginalPosterUrl();
        $this->setImdbId($movieDTO->getImdbId());
        $this->setBudget($movieDTO->getBudget());
        $this->setRuntime($movieDTO->getRuntime());
        $this->setReleaseDate($movieDTO->getReleaseDate());
        $this->tmdb = $tmdb;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function addGenre(Genre $genre)
    {
        $this->genres->add($genre);

        return $this;
    }

    public function removeAllGenres()
    {
        $this->genres->clear();

        return $this;
    }

    /**
     * @return Genre[]|array
     */
    public function getGenres()
    {
        return $this->genres->toArray();
    }

    public function addActor(Actor $actor)
    {
        $movieActor = new MovieActor($this, $actor);
        $this->actors->add($movieActor);

        return $this;
    }

    /**
     * todo return MovieActor[] because sometimes we need to remove entities like this.
     *
     * @return Actor[]|array
     */
    public function getActors(): array
    {
        $movieActors = $this->actors->toArray();

        return array_map(function (MovieActor $movieActor) {
            return $movieActor->getActor();
        }, $movieActors);
    }

    public function addSimilarMovie(self $similarMovie)
    {
        $similarMovie = new SimilarMovie($this, $similarMovie);
        $this->similarMovies->add($similarMovie);

        return $this;
    }

    public function removeAllSimilarMovies()
    {
        $this->similarMovies->clear();

        return $this;
    }

    /**
     * @return SimilarMovie[]|array
     */
    public function getSimilarMovies()
    {
        return $this->similarMovies->toArray();
    }

    /**
     * @return MovieRecommendation[]|array
     */
    public function getRecommendations()
    {
        return $this->recommendations->toArray();
    }

    public function addRecommendation(User $user, self $recommendedMovie)
    {
        $recommendedMovie = new MovieRecommendation($user, $this, $recommendedMovie);
        $this->recommendations->add($recommendedMovie);

        return $this;
    }

    public function removeAllRecommendations()
    {
        $this->recommendations->clear();

        return $this;
    }

    public function updateTmdb(MovieTMDB $tmdb)
    {
        $this->tmdb = $tmdb;
    }

    /**
     * @param string $imdbId
     *
     * @return Movie
     */
    public function setImdbId(?string $imdbId)
    {
        $this->imdbId = $imdbId;

        return $this;
    }

    /**
     * @param int $runtime
     *
     * @return Movie
     */
    public function setRuntime(int $runtime)
    {
        $this->runtime = $runtime;

        return $this;
    }

    /**
     * @param int $budget
     *
     * @return Movie
     */
    public function setBudget(int $budget)
    {
        $this->budget = $budget;

        return $this;
    }

    /**
     * @param \DateTimeInterface $releaseDate
     *
     * @return Movie
     */
    public function setReleaseDate(\DateTimeInterface $releaseDate)
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getOriginalTitle()
    {
        return $this->originalTitle;
    }

    /**
     * @param string $title
     *
     * @return $this
     */
    public function changeOriginalTitle(string $title)
    {
        $this->originalTitle = $title;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getOriginalPosterUrl()
    {
        return $this->originalPosterUrl;
    }

    public function setOriginalPosterUrl(string $url)
    {
        return $this->originalPosterUrl = $url;
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

    public function getUserWatchedMovie()
    {
        return $this->userWatchedMovie;
    }

    public function getOwnerWatchedMovie()
    {
        return $this->ownerWatchedMovie;
    }

    public function getGuestWatchedMovie()
    {
        return $this->guestWatchedMovie;
    }

    public function isWatched()
    {
        $this->isWatched = ($this->userWatchedMovie || $this->guestWatchedMovie) ? true : false;

        return $this->isWatched;
    }

    public function getUserInterestedMovie()
    {
        return $this->userInterestedMovie;
    }

    public function getUserRecommendedMovie()
    {
        return $this->userRecommendedMovie;
    }

    /**
     * @param string $originalTitle
     */
    public function setOriginalTitle(string $originalTitle): void
    {
        $this->originalTitle = $originalTitle;
    }

    public function __toString()
    {
        return $this->getId().' | '.$this->getOriginalTitle().' | TMDB: '.$this->getTmdb()->getId();
    }
}
