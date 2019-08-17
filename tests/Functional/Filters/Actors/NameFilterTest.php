<?php


namespace App\Tests\Functional\Filters\Movies;


use App\Actors\DataFixtures\ActorsFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class NameFilterTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testNameEqualSuccess()
    {
        $actorName = ActorsFixtures::ACTOR_ORIGINAL_NAME;
        $client = self::$client;
        $client->request('get', "/api/actors/search?n={$actorName}");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(1, $response['data']);
    }

    public function testNameEqualNotFound()
    {
        $actorName = 'ActorWithSuchNameNotExists';
        $client = self::$client;
        $client->request('get', "/api/actors/search?n={$actorName}");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(0, $response['data']);
    }

    public function testNameTooShortToApplyFilter()
    {
        $actorName = 'a';
        $client = self::$client;
        $client->request('get', "/api/actors/search?n={$actorName}");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertCount(2, $response['data']);
    }
}