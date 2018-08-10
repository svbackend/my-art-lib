<?php

namespace App\Tests\Functional\Controller\Movies;

use App\Movies\DataFixtures\MoviesFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MovieActorControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testGetMovieActors()
    {
        $client = self::$client;

        $client->request('GET', '/api/movies');
        $movie = json_decode($client->getResponse()->getContent(), true)['data'][0];

        $client->request('GET', "/api/movies/{$movie['id']}/actors");
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, count($response['data']));
        $movieActor = $response['data'][0];
        $this->assertArrayHasKey('actor', $movieActor);
        $actor = $movieActor['actor'];
        $this->assertSame(MoviesFixtures::MOVIE_ACTOR_TMDB_ID, $actor['tmdb']['id']);
        $this->assertArrayHasKey('photo', $actor);
        $this->assertArrayHasKey('name', $actor);
    }
}
