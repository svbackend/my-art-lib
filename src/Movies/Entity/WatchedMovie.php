<?php
declare(strict_types=1);

namespace App\Movies\Entity;

use App\Movies\Entity\Movie;
use App\Users\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Mapping\MappedSuperclass;

/** @MappedSuperclass */
class WatchedMovie
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Movies\Entity\Movie")
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"list"})
     */
    protected $movie;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="decimal", nullable=true)
     */
    protected $vote;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="datetime", nullable=false)
     */
    protected $addedAt;

    /**
     * @Groups({"list", "view"})
     * @ORM\Column(type="date", nullable=true)
     */
    protected $watchedAt;

    /**
     * WatchedMovie constructor.
     * @param Movie $movie
     * @param float|null $vote
     * @param \DateTimeInterface|null $watchedAt
     * @throws \Exception
     */
    public function __construct(Movie $movie, ?float $vote, ?\DateTimeInterface $watchedAt)
    {
        $this->movie = $movie;
        $this->addedAt = new \DateTimeImmutable();
        $this->watchedAt = $watchedAt;

        if ($vote !== null) {
            $this->vote = $vote > 0.0 ? $vote : null;
        }
    }

    public function updateMovie(Movie $movie)
    {
        $this->movie = $movie;
    }

    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    public function getVote(): ?float
    {
        return $this->vote;
    }

    public function getAddedAt(): ?\DateTimeInterface
    {
        return $this->addedAt;
    }

    public function getWatchedAt(): ?\DateTimeInterface
    {
        return $this->watchedAt;
    }
}