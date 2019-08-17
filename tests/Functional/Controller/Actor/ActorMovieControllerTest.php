<?php

namespace App\Tests\Functional\Controller\Actor;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ActorMovieControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testActorMoviesListAsGuest()
    {
        $client = self::$client;

        // movies
        $client->request('GET', '/api/movies');
        $response = json_decode($client->getResponse()->getContent(), true);
        $movies = $response['data'];
        $movie = reset($movies);
        // actors
        $client->request('GET', "/api/movies/{$movie['id']}/actors");
        $response = json_decode($client->getResponse()->getContent(), true);
        $actors = $response['data'];
        $actor = reset($actors)['actor'];

        $client->request('GET', "/api/actors/{$actor['id']}/movies");
        $response = json_decode($client->getResponse()->getContent(), true);
        $actorMovies = $response['data'];
        $actorMovie = reset($actorMovies);
        $this->assertArrayHasKey('id', $actorMovie);
    }

    public function testActorMoviesListAsUser()
    {
        $client = self::$client;

        // movies
        $client->request('GET', '/api/movies');
        $response = json_decode($client->getResponse()->getContent(), true);
        $movies = $response['data'];
        $movie = reset($movies);
        // actors
        $client->request('GET', "/api/movies/{$movie['id']}/actors");
        $response = json_decode($client->getResponse()->getContent(), true);
        $actors = $response['data'];
        $actor = reset($actors)['actor'];

        $apiToken = UsersFixtures::TESTER_API_TOKEN;
        $client->request('GET', "/api/actors/{$actor['id']}/movies?api_token={$apiToken}");
        $response = json_decode($client->getResponse()->getContent(), true);
        $actorMovies = $response['data'];
        $actorMovie = reset($actorMovies);
        $this->assertArrayHasKey('id', $actorMovie);
        $this->assertArrayHasKey('userWatchedMovie', $actorMovie);
    }
}
