<?php

namespace App\Tests\Functional\Controller\Guests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GuestSessionControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testGetNewGuestSessionSuccess()
    {
        $client = self::$client;
        $client->request('POST', '/api/guests');

        self::assertSame(200, $client->getResponse()->getStatusCode());
        $guestSession = json_decode($client->getResponse()->getContent(), true);
        self::assertArrayHasKey('token', $guestSession);
        self::assertNotEmpty($guestSession['token']);
    }
}
