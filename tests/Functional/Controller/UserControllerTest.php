<?php

namespace App\Tests\Functional\Controller;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    private static $user;

    private function getUser()
    {
        if (self::$user) {
            return self::$user;
        }

        $client = self::$client;
        $api_token = UsersFixtures::TESTER_API_TOKEN;
        $client->request('GET', "/api/users?api_token={$api_token}");
        $allUsersResponse = json_decode($client->getResponse()->getContent(), true);
        self::$user = reset($allUsersResponse);

        return self::$user;
    }

    public function testGetUsersNonAuth()
    {
        $client = self::$client;

        $client->request('GET', '/api/users');

        $this->assertSame(401, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('error_description', $response);
    }

    public function testPostUsersValid()
    {
        $client = self::$client;

        $client->request('POST', '/api/users', [
            'registration' => [
                'username' => 'ControllerTester',
                'password' => 'ControllerTester',
                'email' => 'Controller@Tester.com',
            ],
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayNotHasKey('errors', $response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('profile', $response);
    }

    public function testConfirmEmailWithValidToken()
    {
        $client = self::$client;

        $client->request('POST', '/api/confirmEmail', [
            'token' => UsersFixtures::TESTER_EMAIL_CONFIRMATION_TOKEN,
        ]);

        $this->assertSame(202, $client->getResponse()->getStatusCode());
    }

    public function testConfirmEmailWithWrongToken()
    {
        $client = self::$client;

        $client->request('POST', '/api/confirmEmail', [
            'token' => str_repeat('t', 32),
        ]);

        $this->assertSame(401, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('error_description', $response);
    }

    public function testConfirmEmailWithInvalidToken()
    {
        $client = self::$client;

        $client->request('POST', '/api/confirmEmail', [
            'token' => '_invalidToken_',
        ]);

        $this->assertSame(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertGreaterThanOrEqual(1, count($response['errors']));
    }

    public function testEmailSentAfterRegistration()
    {
        $client = static::createClient();
        $client->enableProfiler();
        $client->request('POST', '/api/users', [
            'registration' => [
                'username' => 'RegistrationTester',
                'password' => 'RegistrationTester',
                'email' => 'Registration@Tester.com',
            ],
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        // Check that an e-mail was sent
        $this->assertGreaterThanOrEqual(1, $mailCollector->getMessageCount()); // at least 1 message should be sent

        $collectedMessages = $mailCollector->getMessages();
        /**
         * @var \Swift_Message
         */
        $message = $collectedMessages[0];

        // Asserting e-mail data
        $this->assertInstanceOf(\Swift_Message::class, $message);
        $this->assertSame('Registration@Tester.com', key($message->getTo()));
    }

    public function testPostUsersInvalid()
    {
        $client = self::$client;

        $client->request('POST', '/api/users', [
            'registration' => [
                'username' => '_',
                'password' => '_',
                'email' => 'InvalidEmail',
            ],
        ]);

        $this->assertSame(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertGreaterThanOrEqual(3, count($response['errors']));
    }

    public function testPostUsersWithAlreadyRegisteredUsername()
    {
        $client = self::$client;
        // change sensitivity to check that "UsErNaMe" and "username" are equal
        $username = mb_strtoupper(UsersFixtures::TESTER_USERNAME);
        $client->request('POST', '/api/users', [
            'registration' => [
                'username' => $username,
                'password' => '123456789',
                'email' => UsersFixtures::TESTER_EMAIL,
            ],
        ]);

        $this->assertSame(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertGreaterThanOrEqual(2, count($response['errors']));
    }

    public function testGetAllUsersByAuthenticatedUser()
    {
        $client = self::$client;

        $api_token = UsersFixtures::TESTER_API_TOKEN;
        $client->request('GET', "/api/users?api_token={$api_token}");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue(count($response) > 0);
    }

    public function testGetAllUsersWithWrongApiToken()
    {
        $client = self::$client;

        $client->request('GET', '/api/users?api_token=WRONG_API_TOKEN');
        $this->assertSame(401, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('error_description', $response);
    }

    public function testGetUserSuccess()
    {
        $client = self::$client;
        $user = $this->getUser();
        $api_token = UsersFixtures::TESTER_API_TOKEN;

        $client->request('GET', "/api/users/{$user['id']}?api_token={$api_token}");
        self::assertSame(200, $client->getResponse()->getStatusCode());
        $user = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('id', $user);
        self::assertArrayHasKey('profile', $user);
        self::assertArrayHasKey('username', $user);
    }

    public function testGetUserWithoutAuth()
    {
        $client = self::$client;
        $user = $this->getUser();
        $client->request('GET', "/api/users/{$user['id']}");
        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testGetUserNotFound()
    {
        $api_token = UsersFixtures::TESTER_API_TOKEN;
        $client = self::$client;
        $client->request('GET', "/api/users/0?api_token={$api_token}");
        self::assertSame(404, $client->getResponse()->getStatusCode());
    }
}
