<?php

namespace App\Tests\Functional\Controller;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class WatchedMovieControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    private static $client;
    private static $movies;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public static function getMovies()
    {
        if (self::$movies) return self::$movies;

        $client = self::$client;

        $client->request('GET', '/api/movies');
        self::$movies = json_decode($client->getResponse()->getContent(), true)['data'];
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

    public function testUpdateWatchedMovieByWatchedMovieId()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;
        $username = UsersFixtures::TESTER_USERNAME;
        $movies = self::getMovies();
        $movie = reset($movies);

        // First of all we need to add movie to our library
        $client->request('POST', "/api/users/watchedMovies?api_token={$apiToken}", [
            'movie' => [
                'id' => (int)$movie['id'],
                'tmdbId' => null,
                'vote' => null,
                'watchedAt' => null,
            ]
        ]);
        //then get id of this movie in our library
        $watchedMoviesList = $this->getWatchedMoviesList($username, $apiToken);
        $watchedMovie = reset($watchedMoviesList['data']);
        $this->assertEquals($movie['id'], $watchedMovie['id']);
        $this->assertNotEmpty($watchedMovie['userWatchedMovie']['id']);
        $this->assertEquals(0, $watchedMovie['userWatchedMovie']['vote']);
        $this->assertEquals(null, $watchedMovie['userWatchedMovie']['watchedAt']);

        $watchedMovieId = $watchedMovie['userWatchedMovie']['id'];
        $client->request('PATCH', "/api/users/{$username}/watchedMovies/{$watchedMovieId}?api_token={$apiToken}", [
            'movie' => [
                'vote' => 8,
                'watchedAt' => '2010-05-01',
            ]
        ]);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $watchedMoviesList = $this->getWatchedMoviesList($username, $apiToken);
        $updatedWatchedMovie = reset($watchedMoviesList['data']);
        $this->assertEquals(8, $updatedWatchedMovie['userWatchedMovie']['vote']);
        $this->assertNotEquals(null, $updatedWatchedMovie['userWatchedMovie']['watchedAt']);
        $this->assertContains('2010-05-01', $updatedWatchedMovie['userWatchedMovie']['watchedAt']);
    }

    public function testUpdateWatchedMovieByMovieId()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;
        $username = UsersFixtures::TESTER_USERNAME;
        $movies = self::getMovies();
        $movie = reset($movies);

        // First of all we need to add movie to our library
        $client->request('POST', "/api/users/watchedMovies?api_token={$apiToken}", [
            'movie' => [
                'id' => (int)$movie['id'],
                'tmdbId' => null,
                'vote' => null,
                'watchedAt' => null,
            ]
        ]);

        $client->request('PATCH', "/api/users/{$username}/watchedMovies/movie/{$movie['id']}?api_token={$apiToken}", [
            'movie' => [
                'vote' => 7,
                'watchedAt' => '2010-05-02',
            ]
        ]);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $watchedMoviesList = $this->getWatchedMoviesList($username, $apiToken);
        $updatedWatchedMovie = reset($watchedMoviesList['data']);
        $this->assertEquals(7, $updatedWatchedMovie['userWatchedMovie']['vote']);
        $this->assertNotEquals(null, $updatedWatchedMovie['userWatchedMovie']['watchedAt']);
        $this->assertContains('2010-05-02', $updatedWatchedMovie['userWatchedMovie']['watchedAt']);
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

    private function getWatchedMoviesList(string $username, string $apiToken)
    {
        $client = self::$client;
        $client->request('GET', "/api/users/{$username}/watchedMovies?api_token={$apiToken}");
        return json_decode($client->getResponse()->getContent(), true);
    }
}