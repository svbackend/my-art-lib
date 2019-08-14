<?php


namespace App\Tests\Functional\Filters\Movies;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class YearFilterTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testYearEqual()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?y=2019');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $response['data']);
    }

    public function testYearGreaterOrEqual()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?yf=2009');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
    }

    public function testYearLessOrEqual()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?yt=2019');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
    }

    public function testYearRangeSuccess()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?yf=2000&yt=2020');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
    }

    public function testYearRangeEmpty()
    {
        $client = self::$client;
        $client->request('get', '/api/movies?yf=2010&yt=2011');
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(0, $response['data']);
    }
}