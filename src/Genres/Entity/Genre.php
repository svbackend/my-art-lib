<?php
declare(strict_types=1);

namespace App\Genres\Entity;

use App\Genres\Entity\GenreTranslations;
use App\Translation\TranslatableTrait;
use App\Translation\TranslatableInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\Expose;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Genres\Repository\GenreRepository")
 * @ORM\Table(name="genres")
 * @ExclusionPolicy("all")
 * @method GenreTranslations getTranslation(string $locale, bool $useFallbackLocale = true)
 */
class Genre implements TranslatableInterface
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
     * @var $translations GenreTranslations[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Genres\Entity\GenreTranslations", mappedBy="genre", cascade={"persist", "remove"})
     * @Assert\Valid(traverse=true)
     */
    private $translations;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }
}