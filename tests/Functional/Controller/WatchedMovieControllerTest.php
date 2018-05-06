<?php

namespace App\Tests\Functional\Controller;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WatchedMovieControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;
    protected static $movies;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public static function getMovies()
    {
        if (self::$movies) return self::$movies;

        $client = self::$client;

        $client->request('GET', '/api/movies');
        self::$movies = json_decode($client->getResponse()->getContent(), true);
        return self::$movies;
    }
    
    public function testAddWatchedMovieWithoutId()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;

        $client->request('POST', "/api/users/watchedMovies?api_token={$apiToken}", [
            'movie' => [
                'id' => null,
                'tmdbId' => null,
                'vote' => 9.5,
                'watchedAt' => '2010-05-01',
            ]
        ]);


        $this->assertEquals(400, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $response);
        $this->assertArrayHasKey('errors', $response);
        $this->assertTrue(count($response['errors']) >= 2);

    }

    public function testAddWatchedMovieWithId()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;
        $movies = self::getMovies();
        $movie = reset($movies);

        $client->request('POST', "/api/users/watchedMovies?api_token={$apiToken}", [
            'movie' => [
                'id' => (int)$movie['id'],
                'tmdbId' => null,
                'vote' => 9.5,
                'watchedAt' => '2010-05-01',
            ]
        ]);

        $this->assertEquals(202, $client->getResponse()->getStatusCode());
    }

    public function testAddWatchedMovieWithTmdbId()
    {
        $client = self::$client;
        $movies = self::getMovies();
        $movie = reset($movies);
        $apiToken = UsersFixtures::TESTER_API_TOKEN;

        $client->request('POST', "/api/users/watchedMovies?api_token={$apiToken}", [
            'movie' => [
                'id' => null,
                'tmdbId' => (int)$movie['tmdb']['id'],
                'vote' => null,
                'watchedAt' => null,
            ]
        ]);

        $this->assertEquals(202, $client->getResponse()->getStatusCode());
    }
}