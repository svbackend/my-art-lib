<?php

namespace App\Tests\Functional\Controller\Movies;

use App\Movies\EventListener\AddRecommendationProcessor;
use App\Users\DataFixtures\UsersFixtures;
use Enqueue\Client\ProducerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MovieRecommendationControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testAddMovieRecommendationWithTmdbId()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;

        $client->request('GET', '/api/movies');
        $movies = json_decode($client->getResponse()->getContent(), true)['data'];

        $originalMovie = $movies[0];
        $recommendedMovie = $movies[1];

        $client->request('POST', "/api/movies/{$originalMovie['id']}/recommendations?api_token={$apiToken}", [
            'recommendation' => [
                'movie_id' => null,
                'tmdb_id' => $recommendedMovie['tmdb']['id'],
            ],
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        // Test that movies will be saved in background
        $traces = $this->getProducer($client)->getTopicTraces(AddRecommendationProcessor::ADD_RECOMMENDATION);
        $this->assertCount(1, $traces);
        $messageData = json_decode($traces[0]['body'], true);

        $this->assertEquals($originalMovie['id'], $messageData['movie_id']);
        $this->assertEquals($recommendedMovie['tmdb']['id'], $messageData['tmdb_id']);
        $this->assertNotEmpty($messageData['user_id']);
    }

    /**
     * @param $client
     *
     * @return ProducerInterface
     */
    private function getProducer($client)
    {
        return $client->getContainer()->get(ProducerInterface::class);
    }

    public function testAddMovieRecommendation()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;

        $client->request('GET', '/api/movies');
        $movies = json_decode($client->getResponse()->getContent(), true)['data'];

        $originalMovie = $movies[0];
        $recommendedMovie = $movies[1];

        $client->request('POST', "/api/movies/{$originalMovie['id']}/recommendations?api_token={$apiToken}", [
            'recommendation' => [
                'movie_id' => $recommendedMovie['id'],
                'tmdb_id' => null,
            ],
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', "/api/movies/{$originalMovie['id']}/recommendations?api_token={$apiToken}");
        $response = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $response);
        $this->assertArrayHasKey('paging', $response);
        $this->assertTrue($response['paging']['total'] > 0);
    }

    public function testGetMovieRecommendations()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;
        $moderApiToken = UsersFixtures::MODER_API_TOKEN;
        $adminApiToken = UsersFixtures::ADMIN_API_TOKEN;

        $client->request('GET', '/api/movies');
        $movies = json_decode($client->getResponse()->getContent(), true)['data'];

        $originalMovie = $movies[0];
        $recommendedMovie = $movies[1];

        // Add recommendation as tester
        $client->request('POST', "/api/movies/{$originalMovie['id']}/recommendations?api_token={$apiToken}", [
            'recommendation' => [
                'movie_id' => $recommendedMovie['id'],
                'tmdb_id' => null,
            ],
        ]);

        // Add same recommendation as moder
        $client->request('POST', "/api/movies/{$originalMovie['id']}/recommendations?api_token={$moderApiToken}", [
            'recommendation' => [
                'movie_id' => $recommendedMovie['id'],
                'tmdb_id' => null,
            ],
        ]);

        // Add original movie as recommendation as admin
        $client->request('POST', "/api/movies/{$originalMovie['id']}/recommendations?api_token={$adminApiToken}", [
            'recommendation' => [
                'movie_id' => $originalMovie['id'], // yes its possible :)
                'tmdb_id' => null,
            ],
        ]);

        $client->request('GET', "/api/movies/{$originalMovie['id']}/recommendations?api_token={$adminApiToken}");
        $response = json_decode($client->getResponse()->getContent(), true);

        $recommendations = $response['data'];

        $this->assertSame(2, $response['paging']['total']);
        $this->assertSame(null, $recommendations[0]['userRecommendedMovie']);
        $this->assertArrayHasKey('id', $recommendations[1]['userRecommendedMovie']);
    }

    public function testGetMovieRecommendations2()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;
        $moderApiToken = UsersFixtures::MODER_API_TOKEN;

        $client->request('GET', '/api/movies');
        $movies = json_decode($client->getResponse()->getContent(), true)['data'];

        $originalMovie = $movies[0];
        $recommendedMovie = $movies[1];

        // Add recommendation as tester
        $client->request('POST', "/api/movies/{$originalMovie['id']}/recommendations?api_token={$apiToken}", [
            'recommendation' => [
                'movie_id' => $recommendedMovie['id'],
                'tmdb_id' => null,
            ],
        ]);

        // Add same recommendation as moder
        $client->request('POST', "/api/movies/{$recommendedMovie['id']}/recommendations?api_token={$moderApiToken}", [
            'recommendation' => [
                'movie_id' => $recommendedMovie['id'],
                'tmdb_id' => null,
            ],
        ]);

        $client->request('GET', "/api/movies/{$originalMovie['id']}/recommendations?api_token={$moderApiToken}");
        $response = json_decode($client->getResponse()->getContent(), true);

        $recommendations = $response['data'];

        $this->assertSame(1, $response['paging']['total']);
        $this->assertSame(null, $recommendations[0]['userRecommendedMovie']);
    }
}
