<?php

namespace App\Tests\Functional\Controller\Users;

use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UserRecommendationControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    private function addRecommendation(int $originalMovieId, int $recommendedMovieId, string $userApiToken = null)
    {
        $apiToken = $userApiToken ?? UsersFixtures::TESTER_API_TOKEN;
        $client = self::$client;
        $client->request('POST', "/api/movies/{$originalMovieId}/recommendations?api_token={$apiToken}", [
            'recommendation' => [
                'movie_id' => $recommendedMovieId,
                'tmdb_id' => null,
            ],
        ]);
    }

    private function addToLibrary(int $movieId, string $userApiToken = null)
    {
        $apiToken = $userApiToken ?? UsersFixtures::TESTER_API_TOKEN;
        $client = self::$client;
        $client->request('POST', "/api/users/watchedMovies?api_token={$apiToken}", [
            'movie' => [
                'id' => $movieId,
                'tmdbId' => null,
                'vote' => 10,
                'watchedAt' => null,
            ],
        ]);
    }

    public function testUserRecommendationsAsGuest()
    {
        $client = self::$client;
        $testerId = UsersFixtures::TESTER_ID;

        $client->request('GET', "/api/users/{$testerId}/recommendations");
        $recommendations = json_decode($client->getResponse()->getContent(), true)['data'];
        $this->assertTrue(\count($recommendations) === 0);

        $client->request('GET', '/api/movies');
        $movies = json_decode($client->getResponse()->getContent(), true)['data'];
        $originalMovie = $movies[0];
        $recommendedMovie = $movies[1];

        $this->addToLibrary($originalMovie['id']);
        $this->addRecommendation($originalMovie['id'], $recommendedMovie['id']);

        $client->request('GET', "/api/users/{$testerId}/recommendations");
        $recommendations = json_decode($client->getResponse()->getContent(), true)['data'];

        $this->assertTrue(\count($recommendations) === 1);
    }

    public function testUserRecommendationsAsProfileOwner()
    {
        $client = self::$client;
        $testerId = UsersFixtures::TESTER_ID;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;

        $client->request('GET', "/api/users/{$testerId}/recommendations?api_token={$apiToken}");
        $recommendations = json_decode($client->getResponse()->getContent(), true)['data'];
        $this->assertTrue(\count($recommendations) === 0);
        /* Recommendations should be empty END*/

        $client->request('GET', '/api/movies');
        $movies = json_decode($client->getResponse()->getContent(), true)['data'];
        $originalMovie = $movies[0];
        $recommendedMovie = $movies[1];

        $this->addToLibrary($originalMovie['id']);
        $this->addRecommendation($originalMovie['id'], $recommendedMovie['id']);

        $client->request('GET', "/api/users/{$testerId}/recommendations?api_token={$apiToken}");
        $recommendations = json_decode($client->getResponse()->getContent(), true)['data'];

        $this->assertTrue(\count($recommendations) === 1);
        /* Test that recommendation will showed up on page END */

        $this->addToLibrary($recommendedMovie['id']);

        $client->request('GET', "/api/users/{$testerId}/recommendations?api_token={$apiToken}");
        $recommendations = json_decode($client->getResponse()->getContent(), true)['data'];

        $this->assertTrue(\count($recommendations) === 0);
        /** If we already watched recommended movie - dont show it in recommendations for profile owner END */
        $adminApiToken = UsersFixtures::ADMIN_API_TOKEN;
        $client->request('GET', "/api/users/{$testerId}/recommendations?api_token={$adminApiToken}");
        $recommendations = json_decode($client->getResponse()->getContent(), true)['data'];

        $this->assertTrue(\count($recommendations) === 1);
        /* Show all recommendations even if owner have watched some of them for other users END */
    }
}
