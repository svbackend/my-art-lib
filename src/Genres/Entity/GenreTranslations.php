<?php

declare(strict_types=1);

namespace App\Genres\Entity;

use App\Translation\EntityTranslationInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

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
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=5)
     * @Groups({"list", "view"})
     */
    private $locale;

    /**
     * @ORM\ManyToOne(targetEntity="App\Genres\Entity\Genre", inversedBy="translations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $genre;

    /**
     * @Groups({"list", "view"})
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

    public function getLocale(): string
    {
        return $this->locale;
    }
}
