<?php

declare(strict_types=1);

namespace App\Movies\Entity;

use App\Movies\DTO\MovieTranslationDTO;
use App\Translation\EntityTranslationInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="movies_translations",
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(name="idx_MovieTranslations_locale_movie_id", columns={"locale", "movie_id"})
 *     })
 */
class MovieTranslations implements EntityTranslationInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="string", length=5)
     */
    private $locale;

    /**
     * @ORM\ManyToOne(targetEntity="App\Movies\Entity\Movie", inversedBy="translations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $movie;

    /**
     * @ORM\Column(type="string", length=100)
     * @Groups({"list", "view"})
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"list", "view"})
     */
    private $posterUrl;

    /**
     * @ORM\Column(type="text")
     * @Groups({"view"})
     */
    private $overview;

    public function __construct(Movie $movie, MovieTranslationDTO $movieTranslationDTO)
    {
        $this->movie = $movie;
        $this->locale = $movieTranslationDTO->getLocale();
        $this->title = $movieTranslationDTO->getTitle();
        $this->posterUrl = $movieTranslationDTO->getPosterUrl();
        $this->overview = $movieTranslationDTO->getOverview();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return mixed
     */
    public function getPosterUrl()
    {
        return $this->posterUrl;
    }

    /**
     * @return mixed
     */
    public function getOverview()
    {
        return $this->overview;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @param string $overview
     */
    public function setOverview(string $overview): void
    {
        $this->overview = $overview;
    }
}
