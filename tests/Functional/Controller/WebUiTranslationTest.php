<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WebUiTranslationTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testIsTranslationsPageLoads()
    {
        $client = self::$client;
        $client->request('get', '/admin/_trans');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }
}