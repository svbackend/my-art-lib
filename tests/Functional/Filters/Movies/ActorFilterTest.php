<?php

namespace App\Tests\Functional\Filters\Movies;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActorFilterTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    private static $client;

    private static $actors = [];

    public static function setUpBeforeClass()
    {
        $client = self::$client = static::createClient();
        $client->request('get', '/api/actors');
        $response = json_decode($client->getResponse()->getContent(), true);
        self::$actors = array_map(static function (array $actor) {
            return $actor['id'];
        }, $response['data']);
    }

    public function testActorOne()
    {
        $client = self::$client;
        $actorId = self::$actors[0];
        $client->request('get', "/api/movies?a[]={$actorId}");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
    }

    public function testActorTwo()
    {
        $client = self::$client;
        [$actor1Id, $actor2Id] = self::$actors;
        $client->request('get', "/api/movies?a[]={$actor1Id}&a[]={$actor2Id}");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']); // Movies with 1st OR 2nd genre
    }

    public function testActorBoth()
    {
        $client = self::$client;
        [$actor1Id, $actor2Id] = self::$actors;
        $client->request('get', "/api/movies?a[]={$actor1Id}&a[]={$actor2Id}&at=AND");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(0, $response['data']); // Movies with 1st AND 2nd actor
    }
}
