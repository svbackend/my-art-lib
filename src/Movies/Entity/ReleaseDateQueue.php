<?php

declare(strict_types=1);

namespace App\Movies\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Movies\Repository\ReleaseDateQueueRepository")
 * @UniqueEntity("movie_id")
 */
class ReleaseDateQueue
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     * @Groups({"list", "view"})
     */
    private $id;

    /**
     * @ORM\OneToOne(targetEntity="App\Movies\Entity\Movie")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"list", "view"})
     */
    private $movie;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="date", nullable=true)
     */
    private $addedAt;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="integer", nullable=false, options={"default": 1})
     */
    private $isActive;

    public function __construct(Movie $movie)
    {
        $this->movie = $movie;
        $this->addedAt = new \DateTime();
        $this->isActive = 1;

        if (!$movie->getImdbId()) {
            $this->isActive = 0;
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getMovie(): Movie
    {
        return $this->movie;
    }

    public function getAddedAt(): \DateTimeInterface
    {
        return $this->addedAt;
    }

    public function activate(): void
    {
        $this->isActive = 1;
    }

    public function isActive(): bool
    {
        return $this->isActive === 1;
    }
}
