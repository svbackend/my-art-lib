<?php

namespace App\Actors\DataFixtures;

use App\Actors\Entity\Actor;
use App\Actors\Entity\ActorTMDB;
use App\Actors\Entity\ActorTranslations;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class ActorsFixtures extends Fixture
{
    public const ACTOR_ORIGINAL_NAME = 'Original Name';
    public const ACTOR_TMDB_ID = 1;

    /**
     * @param ObjectManager $manager
     *
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $actorTmdb = new ActorTMDB(self::ACTOR_TMDB_ID);
        $actor = new Actor(self::ACTOR_ORIGINAL_NAME, $actorTmdb);
        $actor->addTranslation(new ActorTranslations($actor, 'en', self::ACTOR_ORIGINAL_NAME));
        $actor->addTranslation(new ActorTranslations($actor, 'pl', 'Name'));

        $manager->persist($actor);
        $manager->flush();
    }
}
