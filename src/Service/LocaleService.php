<?php

declare(strict_types=1);

namespace App\Service;

class LocaleService
{
    private $locale;
    private $locales;

    public function __construct(string $defaultLocale, array $locales)
    {
        $this->locale = $defaultLocale;
        $this->locales = $locales;
    }

    public function getDefaultLocale(): string
    {
        return $this->locale;
    }

    public function getLocales(): array
    {
        return $this->locales;
    }
}
