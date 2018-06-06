<?php

declare(strict_types=1);

namespace App\Movies\DTO;

class WatchedMovieDTO
{
    private $movieId;
    private $tmdbId;
    private $vote;
    private $watchedAt;

    public function __construct(?int $movieId, ?int $tmdbId, ?float $vote, ?\DateTimeInterface $watchedAt)
    {
        $this->movieId = $movieId;
        $this->tmdbId = $tmdbId;
        $this->vote = $vote;
        $this->watchedAt = $watchedAt;
    }

    /**
     * @return int|null
     */
    public function getMovieId(): ?int
    {
        return $this->movieId;
    }

    /**
     * @return int|null
     */
    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    /**
     * @return float|null
     */
    public function getVote(): ?float
    {
        return $this->vote;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getWatchedAt(): ?\DateTimeInterface
    {
        return $this->watchedAt;
    }
}
