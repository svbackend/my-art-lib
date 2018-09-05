<?php

namespace App\Users\DataFixtures;

use App\Users\Entity\ConfirmationToken;
use App\Users\Entity\User;
use App\Users\Entity\UserRoles;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

class UsersFixtures extends Fixture
{
    const TESTER_EMAIL = 'tester@fixture.com';
    const TESTER_USERNAME = 'tester_fixture';
    const TESTER_PASSWORD = '123456';
    const TESTER_API_TOKEN = 'tester_api_token';
    const TESTER_EMAIL_CONFIRMATION_TOKEN = '11kYJ3ut7aOISPQN0RSqceYDasNnb690';
    const TESTER_PASSWORD_RECOVERY_TOKEN = '11kYJ3ut7aOISPQN0RSqceYDasNnb691';

    const ADMIN_EMAIL = 'admin@fixture.com';
    const ADMIN_USERNAME = 'admin_fixture';
    const ADMIN_PASSWORD = '12345678';
    const ADMIN_API_TOKEN = 'admin_api_token';

    /**
     * @param ObjectManager $manager
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\ORM\ORMException
     */
    public function load(ObjectManager $manager): void
    {
        if ($manager instanceof EntityManager === false) {
            throw new \InvalidArgumentException('UsersFixtures $manager should be instance of EntityManager');
        }
        /* @var $manager EntityManager */

        // user will get id = 1, and admin id = 2
        $manager->getConnection()->exec("ALTER SEQUENCE users_id_seq RESTART WITH 1; UPDATE users SET id=nextval('users_id_seq');");

        $user = $this->createUser();
        $admin = $this->createAdmin();

        $manager->persist($user);
        $manager->persist($admin);
        $manager->flush();

        // Tester
        $this->createTestApiToken($user, self::TESTER_API_TOKEN, $manager);
        $this->createEmailConfirmationToken($user, self::TESTER_EMAIL_CONFIRMATION_TOKEN, $manager);
        $this->createPasswordRecoveryToken($user, self::TESTER_PASSWORD_RECOVERY_TOKEN, $manager);
        // Admin
        $this->createTestApiToken($admin, self::ADMIN_API_TOKEN, $manager);
    }

    private function createUser()
    {
        $user = new User(self::TESTER_EMAIL, self::TESTER_USERNAME, self::TESTER_PASSWORD);
        $profile = $user->getProfile();
        $profile->setFirstName('First')->setLastName('Last');

        for ($i = 3; $i-- > 0;) {
            $profile->addContacts("TestProvider #{$i}", "https://test.com/{$i}/info");
        }

        return $user;
    }

    private function createAdmin()
    {
        $user = new User(self::ADMIN_EMAIL, self::ADMIN_USERNAME, self::ADMIN_PASSWORD);
        $user->getRolesObject()->addRole(UserRoles::ROLE_ADMIN);

        $profile = $user->getProfile();
        $profile->setFirstName('Admin')->setLastName('Admin');

        for ($i = 3; $i-- > 0;) {
            $profile->addContacts("TestProvider #{$i}", "https://test.com/{$i}/info");
        }

        return $user;
    }

    /**
     * @param User          $user
     * @param string        $token
     * @param EntityManager $manager
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createTestApiToken(User $user, string $token, EntityManager $manager): void
    {
        $manager->getConnection()->exec("INSERT INTO users_api_tokens (id, user_id, token) VALUES (NEXTVAL('users_api_tokens_id_seq'), {$user->getId()}, '{$token}');");
    }

    /**
     * @param User          $user
     * @param string        $token
     * @param EntityManager $manager
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createEmailConfirmationToken(User $user, string $token, EntityManager $manager): void
    {
        $type = ConfirmationToken::TYPE_CONFIRM_EMAIL;
        $manager->getConnection()->exec("INSERT INTO users_confirmation_tokens (id, user_id, token, type) VALUES (NEXTVAL('users_confirmation_tokens_id_seq'), {$user->getId()}, '{$token}', '{$type}');");
    }

    /**
     * @param User          $user
     * @param string        $token
     * @param EntityManager $manager
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    private function createPasswordRecoveryToken(User $user, string $token, EntityManager $manager): void
    {
        $type = ConfirmationToken::TYPE_PASSWORD_RECOVERY;
        $manager->getConnection()->exec("INSERT INTO users_confirmation_tokens (id, user_id, token, type) VALUES (NEXTVAL('users_confirmation_tokens_id_seq'), {$user->getId()}, '{$token}', '{$type}');");
    }
}
