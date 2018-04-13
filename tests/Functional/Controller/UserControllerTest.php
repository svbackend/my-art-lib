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
}