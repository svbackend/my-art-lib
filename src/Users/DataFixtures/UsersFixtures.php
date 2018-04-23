<?php

namespace App\Users\DataFixtures;

use App\Users\Entity\ConfirmationToken;
use App\Users\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class UsersFixtures extends Fixture
{
    const TESTER_EMAIL = 'tester@fixture.com';
    const TESTER_USERNAME = 'tester_fixture';
    const TESTER_PASSWORD = '123456';
    const TESTER_API_TOKEN = 'tester_api_token';
    const TESTER_EMAIL_CONFIRMATION_TOKEN = '11kYJ3ut7aOISPQN0RSqceYDasNnb690';

    const ADMIN_EMAIL = 'admin@fixture.com';
    const ADMIN_USERNAME = 'admin_fixture';
    const ADMIN_PASSWORD = '12345678';
    const ADMIN_API_TOKEN = 'admin_api_token';

    public function load(ObjectManager $manager): void
    {
        $user = $this->createUser();
        $admin = $this->createAdmin();

        $manager->persist($user);
        $manager->persist($admin);
        $manager->flush();

        // Tester
        $this->createTestApiToken($user, self::TESTER_API_TOKEN, $manager);
        $this->createEmailConfirmationToken($user, self::TESTER_EMAIL_CONFIRMATION_TOKEN, $manager);
        // Admin
        $this->createTestApiToken($admin, self::ADMIN_API_TOKEN, $manager);
    }

    private function createUser()
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

        return $user;
    }

    private function createAdmin()
    {
        $user = new User();
        $user->username = self::ADMIN_USERNAME;
        $user->email = self::ADMIN_PASSWORD;
        $user->setPlainPassword(self::ADMIN_PASSWORD);
        $user->addRole(User::ROLE_ADMIN);

        $profile = $user->getProfile();
        $profile->first_name = 'Admin';
        $profile->last_name = 'Admin';

        for ($i = 3; $i--> 0;) {
            $profile->addContacts("TestProvider #{$i}", "https://test.com/{$i}/info");
        }

        return $user;
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