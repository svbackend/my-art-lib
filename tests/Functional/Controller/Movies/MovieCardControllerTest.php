<?php

namespace App\Tests\Functional\Controller\Movies;

use App\Movies\DataFixtures\MoviesFixtures;
use App\Movies\Entity\MovieCard;
use App\Users\DataFixtures\UsersFixtures;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MovieCardControllerTest extends WebTestCase
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected static $client;

    public static function setUpBeforeClass()
    {
        self::$client = static::createClient();
    }

    public function testCrudMovieCard()
    {
        $client = self::$client;
        $apiToken = UsersFixtures::TESTER_API_TOKEN;
        $movieId = MoviesFixtures::MOVIE_1_ID;

        $client->request('POST', "/api/movies/{$movieId}/cards?api_token={$apiToken}&language=uk", [
            'card' => [
                'title' => 'title',
                'description' => 'description',
                'url' => 'https://mykino.top',
                'type' => MovieCard::TYPE_REVIEW,
            ],
        ]);

        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', "/api/movies/{$movieId}/cards?api_token={$apiToken}&language=uk");
        $this->assertSame(200, $client->getResponse()->getStatusCode());
        $response = json_decode($client->getResponse()->getContent(), true);

        $card = current($response['data']);
        $this->assertSame(1, $response['paging']['total']);
        $this->assertSame('title', $card['title']);
        $this->assertSame('description', $card['description']);
        $this->assertSame('https://mykino.top', $card['url']);
        $this->assertSame(MovieCard::TYPE_REVIEW, $card['type']);

        $client->request('DELETE', "/api/movies/{$movieId}/cards/{$card['id']}?api_token={$apiToken}");
        $this->assertSame(200, $client->getResponse()->getStatusCode());

        $client->request('GET', "/api/movies/{$movieId}/cards?api_token={$apiToken}&language=en");
        $response = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(0, $response['paging']['total']);

        $client->request('GET', "/api/movies/{$movieId}?api_token={$apiToken}&language=uk");
        $movie = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(0, $movie['cards']);
    }
}
