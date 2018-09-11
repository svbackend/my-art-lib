<?php

namespace App\Movies\EventListener;

use App\Movies\Utils\Poster;
use Enqueue\Client\TopicSubscriberInterface;
use Gumlet\ImageResize;
use Gumlet\ImageResizeException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class PosterResizerProcessor implements PsrProcessor, TopicSubscriberInterface
{
    const RESIZE_POSTERS = 'ResizeMoviesPosters';

    /**
     * @param PsrMessage $message
     * @param PsrContext $session
     *
     * @throws ImageResizeException
     *
     * @return object|string
     */
    public function process(PsrMessage $message, PsrContext $session)
    {
        $movieId = $message->getBody();
        $movieId = json_decode($movieId, true);

        $originalPosterPath = Poster::getPath($movieId);
        if (file_exists($originalPosterPath) === false) {
            return self::REJECT;
        }

        $resolutions = Poster::getPredefinedSizes();
        foreach ($resolutions as $resolution) {
            try {
                $resizer = new ImageResize($originalPosterPath);
            } catch (ImageResizeException $exception) {
                continue;
            }
            $resizer->crop($resolution['width'], $resolution['height']);
            $newFilePath = str_replace('.jpg', ".{$resolution['width']}x{$resolution['height']}.jpg", $originalPosterPath);
            $resizer->save($newFilePath);
        }

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::RESIZE_POSTERS];
    }
}
