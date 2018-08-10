<?php

namespace App\Tests\Functional\Controller\Movies;

use App\Movies\EventListener\AddRecommendationProcessor;
use App\Users\DataFixtures\UsersFixtures;
use Enqueue\Client\TraceableProducer;
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
     * @return TraceableProducer
     */
    private function getProducer($client)
    {
        return $client->getContainer()->get(TraceableProducer::class);
    }
}
