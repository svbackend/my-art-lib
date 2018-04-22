<?php

namespace App\Translation;

class TranslatedEntityHelper
{
    public function updateTranslations(TranslatableInterface $entity, array $translations, callable $add, callable $update = null)
    {
        foreach ($translations as $translation) {
            if (null === $oldTranslation = $entity->getTranslation($translation['locale'], false)) {
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
}