<?php

declare(strict_types=1);

namespace App\Movies\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Symfony\Component\Serializer\Annotation\Groups;

/** @MappedSuperclass */
class WatchedMovie
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Movies\Entity\Movie")
     * @ORM\JoinColumn(nullable=false)
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
     *
     * @param Movie                   $movie
     * @param float|null              $vote
     * @param \DateTimeInterface|null $watchedAt
     *
     * @throws \Exception
     */
    public function __construct(Movie $movie, ?float $vote, ?\DateTimeInterface $watchedAt)
    {
        $this->movie = $movie;
        $this->addedAt = new \DateTimeImmutable();
        $this->watchedAt = $watchedAt;
        $this->changeVote($vote);
    }

    public function updateMovie(Movie $movie)
    {
        $this->movie = $movie;
    }

    public function changeVote(?float $vote)
    {
        if ($vote === null) {
            $this->vote = $vote;
        }

        if ($vote < 0.0 || $vote > 10.0) {
            throw new \InvalidArgumentException('Vote can\'t be less than 0 or greater than 10');
        }

        $this->vote = (float) $vote;
    }

    public function changeWatchedAt(?\DateTimeInterface $watchedAt)
    {
        $this->watchedAt = $watchedAt;
    }

    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    public function getVote(): ?float
    {
        return (float) $this->vote;
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
