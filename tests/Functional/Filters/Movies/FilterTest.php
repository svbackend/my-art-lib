<?php

namespace App\Tests\Functional\Filters\Movies;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class FilterTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testUseAllFilters()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?g[]=1&yf=2005&yt=2020&rf=6');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
    }
}
