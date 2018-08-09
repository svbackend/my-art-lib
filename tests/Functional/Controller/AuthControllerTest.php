<?php

namespace App\Tests\Functional\Controller;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testGetAuthTokenWithIncorrectCredentials()
    {
        $client = self::$client;

        $client->request('POST', '/api/auth/login', [
            'credentials' => [
                'username' => 'IncorrectUsername',
                'password' => 'IncorrectPassword',
            ],
        ]);

        $this->assertSame(401, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('error_description', $response);
    }

    public function testGetAuthTokenWithIncorrectPassword()
    {
        $client = self::$client;

        $client->request('POST', '/api/auth/login', [
            'credentials' => [
                'username' => UsersFixtures::TESTER_USERNAME,
                'password' => 'IncorrectPassword',
            ],
        ]);

        $this->assertSame(401, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('error_description', $response);
    }

    public function testGetAuthTokenValid()
    {
        $client = self::$client;

        $client->request('POST', '/api/auth/login', [
            'credentials' => [
                'username' => UsersFixtures::TESTER_USERNAME,
                'password' => UsersFixtures::TESTER_PASSWORD,
            ],
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('api_token', $response);
        $this->assertNotEmpty($response['api_token']);
    }
}
