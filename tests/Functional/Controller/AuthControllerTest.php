<?php

namespace App\Tests\Functional\Controller;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testGetAuthTokenWithIncorrectCredentials()
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/login', [
            'credentials' => [
                'username' => 'IncorrectUsername',
                'password' => 'IncorrectPassword',
            ]
        ]);

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('error_description', $response);
    }

    public function testGetAuthTokenWithIncorrectPassword()
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/login', [
            'credentials' => [
                'username' => UsersFixtures::TESTER_USERNAME,
                'password' => 'IncorrectPassword',
            ]
        ]);

        $this->assertEquals(401, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('error', $response);
        $this->assertArrayHasKey('error_description', $response);
    }

    public function testGetAuthTokenValid()
    {
        $client = static::createClient();

        $client->request('POST', '/api/auth/login', [
            'credentials' => [
                'username' => UsersFixtures::TESTER_USERNAME,
                'password' => UsersFixtures::TESTER_PASSWORD,
            ]
        ]);

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('api_token', $response);
        $this->assertNotEmpty($response['api_token']);
    }
}