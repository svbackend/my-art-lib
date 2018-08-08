<?php

namespace App\Translation;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Trait TranslatableTrait.
 *
 * @property ArrayCollection $translations
 * @property int|null        $id
 */
trait TranslatableTrait
{
    private $isTranslationsMappedByLocale = false;

    /**
     * @param array         $translations
     * @param callable      $add
     * @param callable|null $update
     *
     * @throws \ErrorException
     */
    public function updateTranslations(array $translations, callable $add, callable $update = null, $localeField = 'locale')
    {
        foreach ($translations as $translation) {
            $locale = is_object($translation) ? $translation->getLocale() : $translation[$localeField];
            if (null === $oldTranslation = $this->getTranslation($locale, false)) {
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

    /**
     * @return array
     */
    public function getTranslations(): array
    {
        if ($this->isTranslationsMappedByLocale === true) {
            return $this->translations->toArray();
        }

        $this->mapTranslationsByLocale();

        return $this->translations->toArray();
    }

    /**
     * @param string $locale
     * @param bool   $useFallbackLocale
     *
     * @throws \ErrorException
     *
     * @return EntityTranslationInterface|null
     */
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

    /**
     * @throws \ErrorException
     *
     * @return mixed
     */
    private function getFallbackTranslation(): EntityTranslationInterface
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
