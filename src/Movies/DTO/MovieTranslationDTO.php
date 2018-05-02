<?php
declare(strict_types=1);

namespace App\Movies\DTO;

class MovieTranslationDTO
{
    private $locale, $title, $overview, $posterUrl;

    public function __construct(string $locale, string $title, ?string $overview, ?string $posterUrl)
    {
        $this->locale = $locale;
        $this->title = $title;
        $this->overview = $overview;
        $this->posterUrl = $posterUrl;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return null|string
     */
    public function getOverview(): ?string
    {
        return $this->overview;
    }

    /**
     * @return null|string
     */
    public function getPosterUrl(): ?string
    {
        return $this->posterUrl;
    }
}