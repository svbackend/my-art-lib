<?php

namespace App\Translation\EventListener;

use App\Translation\TranslatableInterface;
use App\Translation\TranslatedEntitySerializer;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TranslatedEntityResponseListener implements EventSubscriberInterface
{
    private $serializer;

    public function __construct(TranslatedEntitySerializer $serializer)
    {
        $this->serializer = $serializer;
    }

    // todo what if I need to return something like {items: {..translated entities..}, pagination: {page: 1: items: 500}}
    // How to translate this type of response? Think about it..
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();

        if (is_array($result) === true) {
            $probablyEntity = reset($result);
            if (is_object($probablyEntity) && $result[0] instanceof TranslatableInterface) {
                // If its array of translated entities
                $response = $this->translateArrayOfEntities($result, $event->getRequest()->getLocale());
                $event->setControllerResult($response);
                return;
            }
        }

        if (is_object($result) === true && $result instanceof TranslatableInterface) {
            // If its single entity response
            $response = $this->serializer->serialize($result, $event->getRequest()->getLocale());
            $event->setControllerResult($response);
            return;
        }
    }

    private function translateArrayOfEntities(array $entities, string $locale): array
    {
        $translatedEntities = [];
        foreach ($entities as $entity) {
            $translatedEntities[] = $this->serializer->serialize($entity, $locale);
        }

        return $translatedEntities;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 60],
        ];
    }
}