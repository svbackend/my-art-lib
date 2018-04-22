<?php

namespace App\EventListener;

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
    
    public function onKernelView(GetResponseForControllerResultEvent $event)
    {
        $result = $event->getControllerResult();

        if (is_object($result) === false) return;

        if ($result instanceof TranslatableInterface === false) return;

        $response = $this->serializer->serialize($result, $event->getRequest()->getLocale());
        $event->setControllerResult($response);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 60],
        ];
    }
}