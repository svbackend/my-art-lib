<?php

declare(strict_types=1);

namespace App\Movies\DTO;

class MovieDTO
{
    private $originalTitle;
    private $originalPosterUrl;
    private $imdbId;
    private $budget;
    private $runtime;
    private $releaseDate;

    /**
     * MovieDTO constructor.
     *
     * @param null|string $originalTitle
     * @param null|string $originalPosterUrl
     * @param null|string $imdbId
     * @param int|null    $budget
     * @param int|null    $runtime
     * @param null|string $releaseDate
     *
     * @throws \Exception
     */
    public function __construct(?string $originalTitle, ?string $originalPosterUrl, ?string $imdbId, ?int $budget, ?int $runtime, ?string $releaseDate)
    {
        $this->originalTitle = $originalTitle;
        $this->originalPosterUrl = $originalPosterUrl;
        $this->imdbId = $imdbId;
        $this->budget = $budget;
        $this->runtime = $runtime;
        $this->releaseDate = $releaseDate === null ? null : new \DateTimeImmutable($releaseDate);
    }

    /**
     * @return null|string
     */
    public function getOriginalTitle(): ?string
    {
        return $this->originalTitle;
    }

    /**
     * @return null|string
     */
    public function getOriginalPosterUrl(): ?string
    {
        return $this->originalPosterUrl;
    }

    /**
     * @return null|string
     */
    public function getImdbId(): ?string
    {
        return $this->imdbId;
    }

    /**
     * @return int
     */
    public function getBudget(): int
    {
        return (int) $this->budget;
    }

    /**
     * @return int
     */
    public function getRuntime(): int
    {
        return (int) $this->runtime;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getReleaseDate(): ?\DateTimeImmutable
    {
        return $this->releaseDate;
    }
}
