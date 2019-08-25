<?php

namespace App\Movies\Entity;

use App\Users\Entity\User;
use App\Users\Entity\UserWatchedMovie;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Movies\Repository\MovieReviewRepository")
 */
class MovieReview
{
    /**
     * Important note here - this property (userWatchedMovie) MUST be first,
     * because otherwise Doctrine will fill (override) user_id and movie_id with values from UserWatchedMovie
     * which usually just nulls.
     *
     * @ORM\OneToOne(targetEntity="App\Users\Entity\UserWatchedMovie")
     * @ORM\JoinColumns({
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="user_id"),
     *     @ORM\JoinColumn(name="movie_id", referencedColumnName="movie_id")
     *   })
     * @Groups({"list", "view"})
     */
    private $userWatchedMovie;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"list", "view"})
     */
    private $id;

    /**
     * @ORM\Column(type="text")
     * @Groups({"list", "view"})
     */
    private $text;

    /**
     * @ORM\Column(type="string", length=2)
     */
    private $locale;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isReview;

    /**
     * @ORM\ManyToOne(targetEntity="MovieReview")
     */
    private $answerTo;

    /**
     * @ORM\OneToMany(targetEntity="MovieReview", mappedBy="answerTo", cascade={"persist"})
     */
    private $answers;

    /**
     * @ORM\ManyToOne(targetEntity="App\Movies\Entity\Movie")
     */
    private $movie;

    /**
     * @ORM\ManyToOne(targetEntity="App\Users\Entity\User")
     * @Groups({"list", "view"})
     */
    private $user;

    public function __construct(Movie $movie, User $user, string $locale, string $text)
    {
        $this->answers = new ArrayCollection();
        $this->movie = $movie;
        $this->user = $user;
        $this->locale = $locale;
        $this->text = $text;
        $this->isReview = true;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setText($text): void
    {
        $this->text = $text;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function setLocale($locale): void
    {
        $this->locale = $locale;
    }

    public function setMovie(Movie $movie): void
    {
        $this->movie = $movie;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getMovie(): Movie
    {
        return $this->movie;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAnswers()
    {
        return $this->answers;
    }

    public function setAnswerTo(MovieReview $movieReview)
    {
        $this->isReview = false;
        $this->answerTo = $movieReview;
    }

    public function getUserWatchedMovie()
    {
        return $this->userWatchedMovie;
    }
}
