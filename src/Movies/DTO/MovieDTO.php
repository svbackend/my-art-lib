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
     * @param string|null $originalTitle
     * @param string|null $originalPosterUrl
     * @param string|null $imdbId
     * @param int|null    $budget
     * @param int|null    $runtime
     * @param string|null $releaseDate
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
     * @return string|null
     */
    public function getOriginalTitle(): ?string
    {
        return $this->originalTitle;
    }

    /**
     * @return string|null
     */
    public function getOriginalPosterUrl(): ?string
    {
        return $this->originalPosterUrl;
    }

    /**
     * @return string|null
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
