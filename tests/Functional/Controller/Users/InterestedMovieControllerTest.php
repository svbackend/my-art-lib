<?php

namespace App\Tests\Functional\Controller;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class InterestedMovieControllerTest extends WebTestCase
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
        if (self::$movies) {
            return self::$movies;
        }

        $client = self::$client;

        $client->request('GET', '/api/movies');
        self::$movies = json_decode($client->getResponse()->getContent(), true)['data'];

        return self::$movies;
    }

    public function testAddInterestedMovie()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;

        $movies = $this->getMovies();
        $movie = reset($movies);
        $client->request('POST', "/api/users/interestedMovies?api_token={$apiToken}", [
            'movie_id' => $movie['id'],
        ]);

        $this->assertSame(202, $client->getResponse()->getStatusCode());

        $interestedMovies = $this->getInterestedMoviesList(UsersFixtures::TESTER_ID, $apiToken);
        $addedMovie = reset($interestedMovies['data']);
        $this->assertSame($movie['id'], $addedMovie['id']);
    }

    private function getInterestedMoviesList(int $userId, string $apiToken)
    {
        $client = self::$client;
        $client->request('GET', "/api/users/{$userId}/interestedMovies?api_token={$apiToken}");

        return json_decode($client->getResponse()->getContent(), true);
    }

    public function testDeleteInterestedMovieByMovieId()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;

        $movies = $this->getMovies();
        $movie = reset($movies);
        $client->request('POST', "/api/users/interestedMovies?api_token={$apiToken}", [
            'movie_id' => $movie['id'],
        ]);

        $client->request('DELETE', "/api/users/interestedMovies/{$movie['id']}?api_token={$apiToken}");
        $interestedMovies = $this->getInterestedMoviesList(UsersFixtures::TESTER_ID, $apiToken);
        $this->assertSame(0, $interestedMovies['paging']['total']);
    }

    public function testDeleteInterestedMovieByInterestedMovieId()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;

        $movies = $this->getMovies();
        $movie = reset($movies);
        $client->request('POST', "/api/users/interestedMovies?api_token={$apiToken}", [
            'movie_id' => $movie['id'],
        ]);
        $interestedMovies = $this->getInterestedMoviesList(UsersFixtures::TESTER_ID, $apiToken);
        $addedMovie = reset($interestedMovies['data']);

        $client->request('DELETE', "/api/users/interestedMovies/{$addedMovie['id']}?api_token={$apiToken}");
        $interestedMovies = $this->getInterestedMoviesList(UsersFixtures::TESTER_ID, $apiToken);
        $this->assertSame(0, $interestedMovies['paging']['total']);
    }
}
