<?php

declare(strict_types=1);

namespace App\Genres\Entity;

use App\Translation\TranslatableInterface;
use App\Translation\TranslatableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Genres\Repository\GenreRepository")
 * @UniqueEntity(fields="tmdbId", message="This TMDB ID already taken")
 * @ORM\Table(name="genres")
 *
 * @method GenreTranslations getTranslation(string $locale, bool $useFallbackLocale = true)
 */
class Genre implements TranslatableInterface
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
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"ROLE_MODER", "ROLE_ADMIN"})
     */
    private $tmdbId;

    /**
     * @var GenreTranslations[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Genres\Entity\GenreTranslations", mappedBy="genre", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Valid(traverse=true)
     * @Groups({"list", "view"})
     */
    private $translations;

    public function __construct(?int $tmdbId = null)
    {
        $this->translations = new ArrayCollection();
        $this->tmdbId = $tmdbId ?? null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTmdbId()
    {
        return $this->tmdbId;
    }
}
