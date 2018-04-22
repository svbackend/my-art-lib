<?php

namespace App\Translation;

interface TranslatableInterface {
    public function getTranslation(string $locale, bool $useFallbackLocale = true): ?EntityTranslationInterface;
}