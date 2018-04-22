<?php

namespace App\Translation;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerInterface;

class TranslatedEntitySerializer
{
    /**
     * @var Serializer
     */
    protected $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        if ($serializer instanceof Serializer === false) {
            throw new \InvalidArgumentException(sprintf('Serializer should be instance of %s instead %s given', Serializer::class, get_class($serializer)));
        }

        $this->serializer = $serializer;
    }

    public function serialize(TranslatableInterface $entity, string $locale): array
    {
        $translation = $this->serializer->toArray($entity->getTranslation($locale));
        $entity = $this->serializer->toArray($entity);

        return array_merge($translation, $entity);
    }
}