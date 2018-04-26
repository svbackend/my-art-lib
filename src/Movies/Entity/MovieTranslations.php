<?php
declare(strict_types=1);

namespace App\Movies\Entity;

use App\Translation\EntityTranslationInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Expose;

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
     * @Exclude
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=5)
     */
    private $locale;

    /**
     * @Exclude
     * @ORM\ManyToOne(targetEntity="App\Movies\Entity\Movie", inversedBy="translations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $movie;

    /**
     * @Expose
     * @ORM\Column(type="string", length=100)
     */
    private $title;

    /**
     * @Expose
     * @ORM\Column(type="string", length=255)
     */
    private $posterUrl;

    /**
     * @Expose
     * @ORM\Column(type="text")
     */
    private $overview;

    public function __construct(Movie $movie, string $locale, string $title, string $posterUrl, string $overview)
    {
        $this->movie = $movie;
        $this->locale = $locale;
        $this->title = $title;
        $this->posterUrl = $posterUrl;
        $this->overview = $overview;
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
}