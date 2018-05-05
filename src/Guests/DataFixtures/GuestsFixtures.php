<?php

namespace App\Guests\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class GuestsFixtures extends Fixture
{
    const GUEST_SESSION_TOKEN = 'pqSDo1qw2nXZtyPqEz3x9ds';

    /**
     * @param ObjectManager $manager
     * @throws \Doctrine\DBAL\DBALException
     */
    public function load(ObjectManager $manager): void
    {
        if ($manager instanceof EntityManager === false) {
            throw new \InvalidArgumentException('UsersFixtures $manager should be instance of EntityManager');
        }

        /** @var $manager EntityManager */
        $this->createGuestSession(self::GUEST_SESSION_TOKEN, $manager);
    }

    /**
     * @param string $token
     * @param EntityManager $manager
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createGuestSession(string $token, EntityManager $manager): void
    {
        $date = new \DateTimeImmutable('+1 week');
        $manager->getConnection()->exec("INSERT INTO guest_sessions (id, token, expires_at) VALUES (NEXTVAL('guest_sessions_id_seq'), '{$token}', '{$date->format('d-m-Y')}');");
    }
}