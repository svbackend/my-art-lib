<?php
declare(strict_types=1);

namespace App\Entity\Translations;

use App\Entity\Genre;
use App\Translation\EntityTranslationInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\Exclude;
use JMS\Serializer\Annotation\Expose;

/**
 * @ORM\Entity
 * @ORM\Table(name="genres_translations",
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(name="idx_GenreTranslations_locale_genre_id", columns={"locale", "genre_id"})
 *     })
 */
class GenreTranslations implements EntityTranslationInterface
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
     * @ORM\ManyToOne(targetEntity="App\Entity\Genre", inversedBy="translations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $genre;

    /**
     * @Expose
     * @ORM\Column(type="string", length=50)
     */
    private $name;

    public function __construct(Genre $genre, string $locale, string $name)
    {
        $this->genre = $genre;
        $this->locale = $locale;
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function changeName(string $name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLocale():string
    {
        return $this->locale;
    }
}