<?php

namespace App\Tests\Functional\Controller\Users;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserCheckControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testIsUsernameExistsShouldBeTrue()
    {
        $client = self::$client;
        $username = UsersFixtures::TESTER_USERNAME;
        $client->request('get', "/api/users/username/{$username}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testIsUsernameExistsShouldBeFalse()
    {
        $client = self::$client;
        $username = 'NotExistingUsername_p9qZsIu';
        $client->request('get', "/api/users/username/{$username}");
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    public function testIsEmailExistsShouldBeTrue()
    {
        $client = self::$client;
        $email = UsersFixtures::TESTER_EMAIL;
        $client->request('get', "/api/users/email/{$email}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    public function testIsEmailExistsShouldBeFalse()
    {
        $client = self::$client;
        $email = 'NotExistingEmail_p9qZsIu@domain.xyz';
        $client->request('get', "/api/users/email/{$email}");
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }
}