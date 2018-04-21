<?php

namespace App\Tests\Functional\Controller;

use App\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class PostControllerTest extends WebTestCase
{
    public function testGetUsersNonAuth()
    {
        $client = static::createClient();

        $client->request('GET', '/api/users');

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('error_description', $response);
    }

    public function testPostUsersValid()
    {
        $client = static::createClient();

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
        $client = static::createClient();

        $client->request('POST', '/api/confirmEmail', [
            'token' => UsersFixtures::TESTER_EMAIL_CONFIRMATION_TOKEN
        ]);

        $this->assertEquals(202, $client->getResponse()->getStatusCode());
    }

    public function testConfirmEmailWithWrongToken()
    {
        $client = static::createClient();

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
        $client = static::createClient();

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
        $client = static::createClient();

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
        $client = static::createClient();

        $api_token = UsersFixtures::TESTER_API_TOKEN;
        $client->request('GET', "/api/users?api_token={$api_token}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testGetAllUsersWithWrongApiToken()
    {
        $client = static::createClient();

        $client->request('GET', "/api/users?api_token=WRONG_API_TOKEN");
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('error_description', $response);
    }
}