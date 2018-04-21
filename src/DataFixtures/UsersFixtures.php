<?php

namespace App\DataFixtures;

use App\Entity\ConfirmationToken;
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
    const TESTER_EMAIL_CONFIRMATION_TOKEN = '11kYJ3ut7aOISPQN0RSqceYDasNnb690';

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->username = self::TESTER_USERNAME;
        $user->email = self::TESTER_EMAIL;
        $user->setPlainPassword(self::TESTER_PASSWORD);

        $profile = $user->getProfile();
        $profile->first_name = 'First';
        $profile->last_name = 'Last';

        for ($i = 3; $i--> 0;) {
            $profile->addContacts("TestProvider #{$i}", "https://test.com/{$i}/info");
        }

        $manager->persist($user);
        $manager->flush();

        $this->createTestApiToken($user, self::TESTER_API_TOKEN, $manager);
        $this->createEmailConfirmationToken($user, self::TESTER_EMAIL_CONFIRMATION_TOKEN, $manager);
    }

    private function createTestApiToken(User $user, string $token, ObjectManager $manager): void
    {
        $manager->getConnection()->exec("INSERT INTO users_api_tokens (id, user_id, token) VALUES (NEXTVAL('users_api_tokens_id_seq'), {$user->getId()}, '{$token}');");
    }

    private function createEmailConfirmationToken(User $user, string $token, ObjectManager $manager): void
    {
        $type = ConfirmationToken::TYPE_CONFIRM_EMAIl;
        $manager->getConnection()->exec("INSERT INTO users_confirmation_tokens (id, user_id, token, type) VALUES (NEXTVAL('users_confirmation_tokens_id_seq'), {$user->getId()}, '{$token}', '{$type}');");
    }
}