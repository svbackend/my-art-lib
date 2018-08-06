<?php

namespace App\Tests\Functional\Controller\Users;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    private static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    private function getUser(string $token): array
    {
        $client = self::$client;
        $client->request('GET', "/api/users/me?api_token={$token}");
        $user = json_decode($client->getResponse()->getContent(), true);
        return $user;
    }

    public function testUserGetOwnAccountInfo()
    {
        $token = UsersFixtures::TESTER_API_TOKEN;
        $client = self::$client;
        $client->request('GET', "/api/users/me?api_token={$token}");
        $result = json_decode($client->getResponse()->getContent(), true);

        $this->assertEquals(UsersFixtures::TESTER_USERNAME, $result['username']);
    }

    public function testEditOwnProfileSuccess()
    {
        $token = UsersFixtures::TESTER_API_TOKEN;
        $user = $this->getUser($token);

        $client = self::$client;
        $client->request('POST', "/api/users/{$user['id']}?api_token={$token}", [
            'profile' => [
                'first_name' => 'New first name',
                'last_name' => 'New last name',
                'birth_date' => '2000-10-01',
                'about' => 'about',
                'public_email' => 'public@email.com',
            ]
        ]);
        $this->assertEquals(202, $client->getResponse()->getStatusCode());
        $user = $this->getUser($token);
        $profile = $user['profile'];
        $this->assertEquals($profile['first_name'], 'New first name');
        $this->assertEquals($profile['last_name'], 'New last name');
        $this->assertEquals(strtotime($profile['birth_date']), strtotime('2000-10-01'));
        $this->assertEquals($profile['about'], 'about');
        $this->assertEquals($profile['public_email'], 'public@email.com');
    }

    public function testEditOwnProfileNotLoggedIn()
    {
        $token = UsersFixtures::TESTER_API_TOKEN;
        $user = $this->getUser($token);

        $client = self::$client;
        $client->request('POST', "/api/users/{$user['id']}", [
            'profile' => [
                'first_name' => 'New first name',
                'last_name' => 'New last name',
                'birth_date' => '2000-10-01',
                'about' => 'about',
                'public_email' => 'public@email.com',
            ]
        ]);
        $this->assertEquals(401, $client->getResponse()->getStatusCode());
    }

    public function testEditProfileOfAnotherUserWithoutPermissions()
    {
        $token = UsersFixtures::TESTER_API_TOKEN;
        $user = $this->getUser(UsersFixtures::ADMIN_API_TOKEN);

        $client = self::$client;
        $client->request('POST', "/api/users/{$user['id']}?api_token={$token}", [
            'profile' => [
                'first_name' => 'New first name',
                'last_name' => 'New last name',
                'birth_date' => '2000-10-01',
                'about' => 'about',
                'public_email' => 'public@email.com',
            ]
        ]);
        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testEditProfileOfAnotherUserSuccess()
    {
        $token = UsersFixtures::ADMIN_API_TOKEN;
        $user = $this->getUser(UsersFixtures::TESTER_API_TOKEN);

        $client = self::$client;
        $client->request('POST', "/api/users/{$user['id']}?api_token={$token}", [
            'profile' => [
                'first_name' => 'New first name',
                'last_name' => 'New last name',
                'birth_date' => '2000-10-01',
                'about' => 'about',
                'public_email' => 'public@email.com',
            ]
        ]);
        $this->assertEquals(202, $client->getResponse()->getStatusCode());
        $user = $this->getUser(UsersFixtures::TESTER_API_TOKEN);
        $profile = $user['profile'];
        $this->assertEquals($profile['first_name'], 'New first name');
        $this->assertEquals($profile['last_name'], 'New last name');
        $this->assertEquals(strtotime($profile['birth_date']), strtotime('2000-10-01'));
        $this->assertEquals($profile['about'], 'about');
        $this->assertEquals($profile['public_email'], 'public@email.com');
    }
}