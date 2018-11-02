<?php

declare(strict_types=1);

namespace App\Actors\Entity;

use App\Translation\TranslatableInterface;
use App\Translation\TranslatableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Actors\Repository\ActorRepository")
 * @ORM\Table(name="actors")
 *
 * @method ActorTranslations getTranslation(string $locale, bool $useFallbackLocale = true)
 * @UniqueEntity("tmdb.id")
 */
class Actor implements TranslatableInterface
{
    use TranslatableTrait;

    const GENDER_MALE = 2;
    const GENDER_FEMALE = 1;

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"list", "view"})
     */
    private $id;

    /**
     * @var ActorTranslations[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Actors\Entity\ActorTranslations", mappedBy="actor", cascade={"persist", "remove"})
     * @Assert\Valid(traverse=true)
     * @Groups({"list", "view"})
     */
    private $translations;

    /**
     * @var ActorContacts[]|ArrayCollection
     * @ORM\OneToMany(targetEntity="App\Actors\Entity\ActorContacts", mappedBy="actor", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     * @Groups({"view"})
     */
    private $contacts;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="string", length=100)
     */
    private $originalName;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $photo;

    /**
     * @ORM\Embedded(class="App\Actors\Entity\ActorTMDB", columnPrefix="tmdb_")
     * @Assert\Valid(traverse=true)
     * @Groups({"list", "view"})
     */
    private $tmdb;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Groups({"view"})
     */
    private $imdbId;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="date", nullable=true)
     */
    private $birthday;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="integer", length=1, nullable=false)
     */
    private $gender;

    public function __construct(string $originalName, ActorTMDB $actorTMDB)
    {
        $this->translations = new ArrayCollection();
        $this->contacts = new ArrayCollection();

        $this->originalName = $originalName;
        $this->tmdb = $actorTMDB;
        $this->gender = self::GENDER_MALE;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function setOriginalName(string $originalName): void
    {
        $this->originalName = $originalName;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(string $photo): void
    {
        $this->photo = $photo;
    }

    public function getTmdb(): ActorTMDB
    {
        return $this->tmdb;
    }

    public function setTmdb(ActorTMDB $tmdb): void
    {
        $this->tmdb = $tmdb;
    }

    public function getImdbId(): ?string
    {
        return $this->imdbId;
    }

    public function setImdbId(string $imdbId): void
    {
        $this->imdbId = $imdbId;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(\DateTimeInterface $birthday): void
    {
        $this->birthday = $birthday;
    }

    public function getGender(): int
    {
        return $this->gender;
    }

    /**
     * @param int $gender
     *
     * @throws \InvalidArgumentException
     */
    public function setGender(int $gender): void
    {
        if (false === \in_array($gender, [self::GENDER_FEMALE, self::GENDER_MALE], true)) {
            throw new \InvalidArgumentException('Invalid gender value');
        }
        $this->gender = $gender;
    }

    public function getContacts()
    {
        return $this->contacts->toArray();
    }
}
