<?php

namespace App\Tests\Functional\Controller\Movies;

use App\Movies\DataFixtures\MoviesFixtures;
use App\Movies\Entity\MovieCard;
use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MovieReviewControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testCrudMovieReview()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;
        $movieId = MoviesFixtures::MOVIE_1_ID;

        $client->request('POST', "/api/movies/{$movieId}/reviews?api_token={$apiToken}&language=uk", [
            'review' => [
                'text' => 'VERY SHORT REVIEW!!!',
            ],
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', "/api/movies/{$movieId}/reviews?api_token={$apiToken}&language=uk");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $review = current($response['data']);
        $this->assertSame(1, $response['paging']['total']);
        $this->assertSame('VERY SHORT REVIEW!!!', $review['text']);

        /*
        $client->request('DELETE', "/api/movies/{$movieId}/reviews/{$review['id']}?api_token={$apiToken}");
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', "/api/movies/{$movieId}/reviews?api_token={$apiToken}&language=en");
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(0, $response['paging']['total']);*/
    }
}
