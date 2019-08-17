<?php

namespace App\Tests\Functional\Filters\Movies;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RatingFilterTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testRatingEqual()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?r=8');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $response['data']);
    }

    public function testRatingGreaterOrEqual()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?rf=8');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
    }

    public function testRatingLessOrEqual()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?rt=10');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
    }

    public function testRatingRangeSuccess()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?rf=8&rt=9');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $response['data']);
    }

    public function testRatingRangeEmpty()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?rf=3&rt=6');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(0, $response['data']);
    }
}
