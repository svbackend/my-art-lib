<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\UserProfileContacts;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UsersFixtures extends Fixture
{
    const TESTER_EMAIL = 'tester@fixture.com';
    const TESTER_USERNAME = 'tester_fixture';
    const TESTER_PASSWORD = '123456';
    const TESTER_API_TOKEN = 'tester_api_token';

    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->username = 'tester_fixture';
        $user->email = 'tester@fixture.com';
        $user->setPassword('123456', $this->encoder);

        $profile = $user->getProfile();
        $profile->first_name = 'First';
        $profile->last_name = 'Last';

        for ($i = 3; $i--> 0;) {
            $profile->addContacts("TestProvider #{$i}", "https://test.com/{$i}/info");
        }

        $manager->persist($user);
        $manager->flush();

        $this->createTestApiToken($user, self::TESTER_API_TOKEN, $manager);
    }

    private function createTestApiToken(User $user, string $token, ObjectManager $manager): void
    {
        $manager->getConnection()->exec("INSERT INTO users_api_tokens (id, user_id, token) VALUES (NEXTVAL('users_api_tokens_id_seq'), {$user->getId()}, '{$token}');");
    }
}