<?php

declare(strict_types=1);

namespace App\Actors\Entity;

use App\Translation\EntityTranslationInterface;
use App\Translation\TranslatableTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity
 * @ORM\Table(name="actors_translations",
 *     uniqueConstraints={
 *      @ORM\UniqueConstraint(name="idx_ActorTranslations_locale_actor_id", columns={"locale", "actor_id"})
 *     })
 */
class ActorTranslations implements EntityTranslationInterface
{
    use TranslatableTrait;

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
     * @ORM\ManyToOne(targetEntity="App\Actors\Entity\Actor", inversedBy="translations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $actor;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $placeOfBirth;

    /**
     * @Groups({"view"})
     * @ORM\Column(type="text", nullable=true)
     */
    private $biography;

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function __construct(Actor $actor, string $locale, string $name)
    {
        $this->actor = $actor;
        $this->locale = $locale;
        $this->name = $name;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPlaceOfBirth()
    {
        return $this->placeOfBirth;
    }

    public function setPlaceOfBirth(string $placeOfBirth): void
    {
        $this->placeOfBirth = $placeOfBirth;
    }

    public function getBiography()
    {
        return $this->biography;
    }

    public function setBiography(string $biography): void
    {
        $this->biography = $biography;
    }
}
