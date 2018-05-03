<?php
declare(strict_types=1);

namespace App\Genres\Entity;

use App\Genres\Entity\GenreTranslations;
use App\Translation\TranslatableTrait;
use App\Translation\TranslatableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Genres\Repository\GenreRepository")
 * @ORM\Table(name="genres")
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
     * @ORM\Column(type="integer", options={"default": 0})
     * @Groups({"ROLE_MODER", "ROLE_ADMIN"})
     */
    private $tmdb_id;

    /**
     * @var $translations GenreTranslations[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Genres\Entity\GenreTranslations", mappedBy="genre", cascade={"persist", "remove"})
     * @Assert\Valid(traverse=true)
     * @Groups({"list", "view"})
     */
    private $translations;

    public function __construct(?int $tmdb_id = 0)
    {
        $this->translations = new ArrayCollection();
        $this->tmdb_id = $tmdb_id ?? 0;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getTmdbId()
    {
        return $this->tmdb_id;
    }
}