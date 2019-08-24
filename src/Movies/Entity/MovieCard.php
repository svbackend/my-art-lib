<?php

namespace App\Movies\Entity;

use App\Users\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Movies\Repository\MovieCardRepository")
 */
class MovieCard
{
    public const TYPE_WATCH = 'watch';
    public const TYPE_WATCH_FREE = 'watch_free';
    public const TYPE_DOWNLOAD = 'download';
    public const TYPE_REVIEW = 'review';
    public const TYPE_TRAILER = 'trailer';

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"list", "view"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"list", "view"})
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"list", "view"})
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"list", "view"})
     */
    private $type;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"list", "view"})
     */
    private $url;

    /**
     * @ORM\Column(type="string", length=2)
     */
    private $locale;

    /**
     * @ORM\ManyToOne(targetEntity="App\Movies\Entity\Movie", inversedBy="cards")
     */
    private $movie;

    /**
     * @ORM\ManyToOne(targetEntity="App\Users\Entity\User")
     * @Groups({"list", "view"})
     */
    private $user;

    public function __construct(Movie $movie, User $user, string $locale, string $title, string $description, string $type, string $url)
    {
        $this->movie = $movie;
        $this->user = $user;
        $this->locale = $locale;
        $this->title = $title;
        $this->description = $description;
        $this->type = $type;
        $this->url = $url;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
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
}
