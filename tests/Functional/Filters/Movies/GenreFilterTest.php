<?php

namespace App\Tests\Functional\Filters\Movies;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class GenreFilterTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testGenreOne()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?g[]=1');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
    }

    public function testGenreTwo()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?g[]=1&g[]=2');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']); // Movies with 1st OR 2nd genre
    }

    public function testGenreBoth()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?g[]=1&g[]=2&gt=AND');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $response['data']); // Movies with 1st AND 2nd genre
    }
}