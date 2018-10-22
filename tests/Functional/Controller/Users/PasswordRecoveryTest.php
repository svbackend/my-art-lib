<?php

namespace App\Tests\Functional\Controller\Users;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PasswordRecoveryTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    private static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testPasswordLostRequestSuccess()
    {
        $client = self::$client;

        // To remove test token
        $client->request('POST', '/api/passwordRecovery', [
            'token' => UsersFixtures::TESTER_PASSWORD_RECOVERY_TOKEN,
            'password' => 'newPassword123',
        ]);

        $client->enableProfiler();
        $email = UsersFixtures::TESTER_EMAIL;
        $client->request('GET', "/api/users/{$email}/recoverPassword");
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        $this->assertGreaterThanOrEqual(1, $mailCollector->getMessageCount());

        $collectedMessages = $mailCollector->getMessages();
        /** @var $message \Swift_Message */
        $message = reset($collectedMessages);

        $this->assertInstanceOf(\Swift_Message::class, $message);
        $this->assertSame(UsersFixtures::TESTER_EMAIL, key($message->getTo()));
    }

    public function testPasswordLostRequestWithoutAccess()
    {
        $client = self::$client;
        $email = UsersFixtures::TESTER_EMAIL;
        $token = UsersFixtures::TESTER_API_TOKEN;
        $client->request('GET', "/api/users/{$email}/recoverPassword?api_token={$token}");
        $this->assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testPasswordLostRequestNotFoundUser()
    {
        $client = self::$client;
        $client->request('GET', "/api/users/not@existing.email/recoverPassword");
        $this->assertSame(404, $client->getResponse()->getStatusCode());
    }

    public function testPasswordRecoverySuccess()
    {
        $client = self::$client;
        $client->request('POST', '/api/passwordRecovery', [
            'token' => UsersFixtures::TESTER_PASSWORD_RECOVERY_TOKEN,
            'password' => 'newPassword123',
        ]);
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // lets check that we can sign-in with new password
        $client->request('POST', '/api/auth/login', [
            'credentials' => [
                'username' => UsersFixtures::TESTER_USERNAME,
                'password' => 'newPassword123',
            ],
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
    }
}
