<?php

namespace App\Translation;

use Doctrine\Common\Collections\ArrayCollection;

trait TranslatableTrait
{
    private $isTranslationsMappedByLocale = false;

    public function updateTranslations(array $translations, callable $add, callable $update = null)
    {
        foreach ($translations as $translation) {
            if (null === $oldTranslation = $this->getTranslation($translation['locale'], false)) {
                $add($translation);
                continue;
            }

            if ($update === null) {
                // This will called only when there's update action and $update function not defined
                // But you still can keep it as null if you creating entity
                throw new \InvalidArgumentException('Unexpected behavior: founded old translation but $update function not defined');
            }

            $update($translation, $oldTranslation);
        }
    }

    public function addTranslation(EntityTranslationInterface $translation): self
    {
        $this->translations->set($translation->getLocale(), $translation);

        return $this;
    }

    public function getTranslation(string $locale, bool $useFallbackLocale = true): ?EntityTranslationInterface
    {
        if ($this->isTranslationsMappedByLocale === false) {
            $this->mapTranslationsByLocale();
        }

        $translation = $this->translations->get($locale);

        if ($translation === null && $useFallbackLocale === true) {
            return $this->getFallbackTranslation();
        }

        return $translation;
    }

    private function getFallbackTranslation()
    {
        if (null === $translation = $this->translations->first()) {
            throw new \ErrorException(sprintf('You are trying to get translation for %s with ID %s but there\'s no translations found.', self::class, $this->id));
        }

        return $translation;
    }

    private function mapTranslationsByLocale(): void
    {
        $mappedTranslations = new ArrayCollection();

        foreach ($this->translations as $translation) {
            $mappedTranslations->set($translation->getLocale(), $translation);
        }

        $this->isTranslationsMappedByLocale = true;
        $this->translations = $mappedTranslations;
    }
}