<?php

namespace App\Actors\EventListener;

use App\Actors\Repository\ActorRepository;
use App\Actors\Utils\ActorPhoto;
use Doctrine\ORM\EntityManagerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Interop\Queue\Context;
use Interop\Queue\Message as QMessage;
use Interop\Queue\Processor;

class ActorPhotoProcessor implements Processor, TopicSubscriberInterface
{
    const LOAD_PHOTO = 'LoadActorPhoto';

    private $em;
    private $actorRepository;

    public function __construct(EntityManagerInterface $em, ActorRepository $actorRepository)
    {
        $this->em = $em;
        $this->actorRepository = $actorRepository;
    }

    public function process(QMessage $message, Context $session)
    {
        $actorId = $message->getBody();
        $actorId = json_decode($actorId, true);

        $actor = $this->actorRepository->find($actorId);

        if ($actor === null) {
            return self::REJECT;
        }

        $photoUrl = $actor->getPhoto();
        // $posterName = str_replace('https://image.tmdb.org/t/p/original', '', $posterUrl);
        if ($photoUrl === 'https://image.tmdb.org/t/p/original') {
            return self::REJECT;
        }

        $photoPath = ActorPhoto::savePhoto($actorId, $photoUrl);
        if ($photoPath === null) {
            return self::REJECT;
        }

        $actor->setPhoto(ActorPhoto::getUrl($actorId));

        $this->em->flush();
        $this->em->clear();

        $message = $session = $actorId = $actor = null;
        unset($message, $session, $actorId, $actor);

        return self::ACK;
    }

    public static function getSubscribedTopics()
    {
        return [self::LOAD_PHOTO];
    }
}
