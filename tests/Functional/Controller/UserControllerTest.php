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

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
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
            ]
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayNotHasKey('errors', $response);
        $this->assertArrayHasKey('id', $response);
        $this->assertArrayHasKey('profile', $response);
    }

    public function testConfirmEmailWithValidToken()
    {
        $client = self::$client;

        $client->request('POST', '/api/confirmEmail', [
            'token' => UsersFixtures::TESTER_EMAIL_CONFIRMATION_TOKEN
        ]);

        $this->assertEquals(202, $client->getResponse()->getStatusCode());
    }

    public function testConfirmEmailWithWrongToken()
    {
        $client = self::$client;

        $client->request('POST', '/api/confirmEmail', [
            'token' => str_repeat('t', 32)
        ]);

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('error_description', $response);
    }

    public function testConfirmEmailWithInvalidToken()
    {
        $client = self::$client;

        $client->request('POST', '/api/confirmEmail', [
            'token' => '_invalidToken_'
        ]);

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
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
            ]
        ]);

        $mailCollector = $client->getProfile()->getCollector('swiftmailer');

        // Check that an e-mail was sent
        $this->assertGreaterThanOrEqual(1, $mailCollector->getMessageCount()); // at least 1 message should be sent

        $collectedMessages = $mailCollector->getMessages();
        /**
         * @var $message \Swift_Message
         */
        $message = $collectedMessages[0];

        // Asserting e-mail data
        $this->assertInstanceOf(\Swift_Message::class, $message);
        $this->assertEquals('Registration@Tester.com', key($message->getTo()));
        $this->assertContains('?token', $message->getBody());
    }

    public function testPostUsersInvalid()
    {
        $client = self::$client;

        $client->request('POST', '/api/users', [
            'registration' => [
                'username' => '_',
                'password' => '_',
                'email' => 'InvalidEmail',
            ]
        ]);

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('errors', $response);
        $this->assertArrayHasKey('message', $response);
        $this->assertGreaterThanOrEqual(3, count($response['errors']));
    }

    public function testGetAllUsersByAuthenticatedUser()
    {
        $client = self::$client;

        $api_token = UsersFixtures::TESTER_API_TOKEN;
        $client->request('GET', "/api/users?api_token={$api_token}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue(count($response) > 0);
    }

    public function testGetAllUsersWithWrongApiToken()
    {
        $client = self::$client;

        $client->request('GET', "/api/users?api_token=WRONG_API_TOKEN");
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('error_description', $response);
    }

    public function testGetUserSuccess()
    {
        $user = $this->getUser();
        $client = self::$client;
        $api_token = UsersFixtures::TESTER_API_TOKEN;

        $client->request('GET', "/api/users/{$user['id']}?api_token={$api_token}");
        self::assertEquals(200, $client->getResponse()->getStatusCode());
        $user = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('id', $user);
        self::assertArrayHasKey('profile', $user);
        self::assertArrayHasKey('username', $user);
    }

    public function testGetUserWithoutAuth()
    {
        $user = $this->getUser();
        $client = self::$client;
        $client->request('GET', "/api/users/{$user['id']}");
        self::assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testGetUserNotFound()
    {
        $api_token = UsersFixtures::TESTER_API_TOKEN;
        $client = self::$client;
        $client->request('GET', "/api/users/0?api_token={$api_token}");
        self::assertEquals(404, $client->getResponse()->getStatusCode());
    }
}