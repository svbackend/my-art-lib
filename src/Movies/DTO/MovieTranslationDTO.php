<?php

declare(strict_types=1);

namespace App\Movies\DTO;

class MovieTranslationDTO
{
    private $locale;
    private $title;
    private $overview;
    private $posterUrl;

    public function __construct(string $locale, string $title, ?string $overview, ?string $posterUrl)
    {
        if (mb_strlen($title) >= 100) {
            $title = mb_substr($title, 0, 96) . '...';
        }

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
     * @return string|null
     */
    public function getOverview(): ?string
    {
        return $this->overview;
    }

    /**
     * @return string|null
     */
    public function getPosterUrl(): ?string
    {
        return $this->posterUrl;
    }
}
